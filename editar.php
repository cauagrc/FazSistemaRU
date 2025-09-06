<?php
// Definir a URL da API para edição
$apiUrl = "http://localhost/sistemaru/api/users/";

// Verificar se o RFID foi fornecido na URL
$rfid = $_GET['rfid'] ?? null;

if (!$rfid) {
    echo "RFID não fornecido!";
    exit;
}

// Função para buscar dados do usuário da API
function getUserData($rfid, $apiUrl)
{
    $url = $apiUrl . $rfid; // A URL final será http://localhost/sistemaru/api/users/{rfid}
    
    // Usando file_get_contents com a função @ para silenciar o erro e tratá-lo manualmente
    $response = @file_get_contents($url);

    if ($response === false) {
        // Caso o response seja false, pode indicar erro na URL ou recurso não encontrado
        return null;  // Retorna null para indicar erro na requisição
    }

    return json_decode($response, true); // Retorna os dados decodificados em array
}

// Carregar dados do usuário
$user = getUserData($rfid, $apiUrl);

// Verificar se o usuário foi encontrado ou se houve erro
if (!$user) {
    // Caso o usuário não tenha sido encontrado, exibe a mensagem
    echo "Erro ao carregar dados do usuário! O RFID não existe.";
    exit;
}

// Função para atualizar dados do usuário via API
function updateUserData($rfid, $data, $apiUrl)
{
    $url = $apiUrl . $rfid;
    $options = [
        'http' => [
            'method'  => 'PUT',
            'header'  => 'Content-type: application/json',
            'content' => json_encode($data)
        ]
    ];
    $contexto  = stream_context_create($options);

    return @file_get_contents($url, false, $contexto);
}

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $matricula = $_POST['matricula'];
    $saldo = $_POST['saldo'];

    // Criar dados para atualização
    $data = [
        'nome' => $nome,
        'matricula' => $matricula,
        'saldo' => $saldo,
    ];

    // Atualizar os dados do usuário
    $response = updateUserData($rfid, $data, $apiUrl);

    // Verificar se a resposta da API indica sucesso
    $responseData = json_decode($response, true);

    if (isset($responseData['status']) && $responseData['status'] == 'ok') {
        echo "<p style='color: green;'>Usuário atualizado com sucesso!</p>";
        // Recarregar os dados atualizados
        $user = getUserData($rfid, $apiUrl);
    } else {
        echo "<p style='color: red;'>Erro ao atualizar o usuário.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 50%;
            margin: 0 auto;
            padding-top: 30px;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
        }

        label {
            display: block;
            margin: 10px 0 5px;
        }

        input, select, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 2px solid #ddd;
        }

        button {
            background-color: #007BFF;
            color: white;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        .error {
            color: red;
            text-align: center;
        }

        .success {
            color: green;
            text-align: center;
        }

        /* Estilo para tornar os campos readonly meio opacos */
        .readonly-field {
            background-color: #f0f0f0;
            opacity: 0.7;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Editar Usuário</h2>
        <form method="POST">
            <!-- RFID como somente leitura (meio opaco) -->
            <label for="rfid">RFID</label>
            <input type="text" id="rfid" name="rfid" value="<?= htmlspecialchars($user['rfid']) ?>" readonly class="readonly-field">

            <!-- Nome do usuário -->
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($user['nome']) ?>" required>

            <!-- Matrícula do usuário -->
            <label for="matricula">Matrícula</label>
            <input type="text" id="matricula" name="matricula" value="<?= htmlspecialchars($user['matricula']) ?>" required>


            <!-- Saldo do usuário -->
            <label for="saldo">Saldo</label>
            <input type="text" id="saldo" name="saldo" value="<?= htmlspecialchars($user['saldo']) ?>" required>

            <!-- Status (somente leitura e meio opaco) -->
            <label for="status">Status</label>
            <input type="text" id="status" name="status" value="<?= htmlspecialchars($user['status']) ?>" readonly class="readonly-field">

            <button type="submit">Salvar Alterações</button>
        </form>
    </div>
</body>
</html>
