<?php include 'includes/header.php'; ?>

<style>
    /* Estilos gerais */
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f7fa;
        margin: 0;
        padding: 0;
    }

    /* Contêiner principal */
    .container {
        width: 90%;
        margin: 0 auto;
        padding-top: 30px;
    }

    /* Filtros e busca */
    .filtros,
    .search-form {
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .filtros a,
    .limpar {
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        color: white;
        font-weight: bold;
        transition: background-color 0.3s;
    }

    .filtros a:hover,
    .limpar:hover {
        background-color: #ddd;
        color: #333;
    }

    .ativo {
        background-color: #4CAF50;
    }

    .bloqueado {
        background-color: #f44336;
    }

    .pendente {
        background-color: #ff9800;
    }

    .limpar {
        background-color: #ddd;
        color: #333;
    }

    /* Campo de busca */
    .search-form input[type="text"] {
        width: 70%;
        padding: 10px;
        font-size: 16px;
        border: 2px solid #ddd;
        border-radius: 5px;
    }

    .search-form button {
        padding: 10px 15px;
        font-size: 16px;
        background-color: #007BFF;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .search-form button:hover {
        background-color: #0056b3;
    }

    /* Layout de usuários (usando flexbox) */
    .usuarios {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .usuario-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s;
    }

    .usuario-row:hover {
        transform: translateY(-10px);
    }

    /* Estilo das informações dos usuários */
    .usuario-row div {
        flex: 1;
        padding: 10px;
    }

    .usuario-row .status {
        font-size: 16px;
        font-weight: bold;
        padding: 5px;
        border-radius: 4px;
    }

    .ativo-status {
        background-color: #4CAF50;
        color: white;
    }

    .bloqueado-status {
        background-color: #f44336;
        color: white;
    }

    .pendente-status {
        background-color: #ff9800;
        color: white;
    }

    /* Estilo do ícone de edição */
    .edit-icon {
        font-size: 20px;
        color: #007BFF;
        cursor: pointer;
        transition: color 0.3s;
    }

    .edit-icon:hover {
        color: #0056b3;
    }

    /* Paginação */
    .pagination {
        text-align: center;
        margin-top: 30px;
    }

    .pagination a {
        padding: 10px 20px;
        margin: 0 5px;
        text-decoration: none;
        background-color: #007BFF;
        color: white;
        border-radius: 5px;
        transition: background-color 0.3s;
    }

    .pagination a:hover {
        background-color: #0056b3;
    }

</style>

<!-- Incluir o Font Awesome -->
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
</head>

<div class="container">
    <h1>Usuários</h1>

    <!-- 🔍 Campo de pesquisa -->
    <div class="search-form">
        <form method="GET" action="cadastros.php">
            <input type="text" name="busca" placeholder="Buscar por nome, RFID ou matrícula"
                value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>">
            <button type="submit">Buscar</button>
        </form>
    </div>

    <!-- ✅ Filtros de status -->
    <div class="filtros">
        <div>
            <a href="cadastros.php?status=ativo" class="ativo">Ativos</a>
            <a href="cadastros.php?status=bloqueado" class="bloqueado">Bloqueados</a>
            <a href="cadastros.php?status=pendente" class="pendente">Pendentes</a>
        </div>

        <!-- Mostrar 'Limpar filtro' somente quando um filtro de status específico for selecionado -->
        <?php
        $status = $_GET['status'] ?? null;
        if (in_array($status, ['ativo', 'bloqueado', 'pendente'])): ?>
            <a href="cadastros.php" class="limpar">Limpar filtro</a>
        <?php endif; ?>
    </div>

    <?php
    // 📥 Entrada via GET
    $busca = $_GET['busca'] ?? null;
    $status = $_GET['status'] ?? null;
    $page = $_GET['page'] ?? 1; // Página atual
    $limit = 10; // Limite de usuários por página
    $offset = ($page - 1) * $limit;

    // Define a URL da API
    if ($busca) {
        $url = "http://localhost/sistemaru/api/busca?termo=" . urlencode($busca) . "&limit=$limit&offset=$offset";
    } elseif ($status) {
        $url = "http://localhost/sistemaru/api/status/$status?limit=$limit&offset=$offset";
    } else {
        $url = "http://localhost/sistemaru/api/users?limit=$limit&offset=$offset";
    }

    // Faz requisição à API
    $response = @file_get_contents($url);

    // Verifica se a resposta é válida
    if ($response === false) {
        echo "<p>Erro ao carregar usuários. Tente novamente mais tarde.</p>";
        exit;
    }

    // Decodifica a resposta JSON da API
    $usuarios = json_decode($response, true);

    // Verifica se a resposta decodificada é um array válido
    if (is_array($usuarios) && count($usuarios) > 0) {
        echo "<div class='usuarios'>";
        foreach ($usuarios as $user) {
            echo "<div class='usuario-row'>
                    <div><strong>{$user['nome']}</strong><br><small>Matrícula: {$user['matricula']}</small><br><small>RFID: {$user['rfid']}</small></div>
                    <div class='status ";
            // Adiciona a classe de status correta
            echo $user['status'] === 'ativo' ? 'ativo-status' :
                ($user['status'] === 'bloqueado' ? 'bloqueado-status' : 'pendente-status');
            echo "'>{$user['status']}</div>
                    <div><a href='editar.php?rfid={$user['rfid']}' class='edit-icon' title='Editar'><i class='fas fa-pencil-alt'></i></a></div>
                  </div>";
        }
        echo "</div>";
    } else {
        echo "<p>Nenhum usuário encontrado.</p>";
    }

    // Paginacao - verifica se há mais de 10 usuários para navegação
    $nextPageAvailable = count($usuarios) == $limit;

    echo "<div class='pagination'>";
    if ($page > 1) {
        echo "<a href='cadastros.php?page=" . ($page - 1) . "&busca=$busca&status=$status'>&laquo; Voltar</a>";
    }

    if ($nextPageAvailable) {
        echo "<a href='cadastros.php?page=" . ($page + 1) . "&busca=$busca&status=$status'>Próxima &raquo;</a>";
    }
    echo "</div>";
    ?>

</div>
</main>
</body>

</html>