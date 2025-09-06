<?php
header('Content-Type: application/json');

// Função para gerar um código de compra único
function generateCompraCode()
{
    return uniqid('payment_');  // Gera um código único com prefixo 'compra_'
}

// Conexão com banco de dados
$host = 'localhost';
$dbname = 'sistema_ru';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Falha ao conectar no banco de dados', 'detalhes' => $e->getMessage()]);
    exit;
}

// Roteamento
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = "/sistemaru/api"; // Caminho base da API
$endpoint = str_replace($base, '', $uri);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST' && preg_match('#^/users/([^/]+)/compra$#', $endpoint, $matches)) {
    $rfid = $matches[1];  // RFID do usuário
    $codigoCompra = generateCompraCode();  // Gera o código de compra único

    // Verificar se o corpo da requisição contém dados JSON
    $data = json_decode(file_get_contents('php://input'), true);

    // Verificar se o RFID do usuário existe
    $stmt = $pdo->prepare("SELECT * FROM alunos WHERE rfid = ?");
    $stmt->execute([$rfid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(["error" => "Usuario nao encontrado"]);
        exit;
    }

    $refeicao = "";
    $valorCompra = "";
    $horaAtual = date('H');

    $horaAtual = 7;

    if ($horaAtual >= 6 && $horaAtual < 8) {
        $refeicao = 1;
        $valorCompra = 2.25;  // Valor entre 6h e 8h
    } elseif ($horaAtual >= 11 && $horaAtual < 14) {
        $refeicao = 2;
        $valorCompra = 3.25;  // Valor entre 11h e 14h
    } elseif ($horaAtual >= 18 && $horaAtual < 21) {
        $refeicao = 3;
        $valorCompra = 3.13;  // Valor entre 18h e 21h
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Horario fora da faixa permitida para comer."]);
        exit;
    }

    // Verificar se o saldo do usuário é suficiente
    if ($user['saldo'] < $valorCompra) {
        http_response_code(400);
        echo json_encode(["error" => "Saldo insuficiente."]);
        exit;
    }

    // Atualizar o saldo do usuário
    $novoSaldo = $user['saldo'] - $valorCompra;

    try {
        // Iniciar a transação para evitar problemas de concorrência
        $pdo->beginTransaction();

        // Atualizar o saldo do usuário
        $stmt = $pdo->prepare("UPDATE alunos SET saldo = ? WHERE rfid = ?");
        $stmt->execute([$novoSaldo, $rfid]);

        // Inserir a compra na tabela 'compras'
        $stmt = $pdo->prepare("INSERT INTO transacoes (codigo, rfid, nome, valor) VALUES (?, ?, ?, ?)");
        $stmt->execute([$codigoCompra, $rfid, $user['nome'], $valorCompra]);

        // Confirmar a transação
        $pdo->commit();

        // Retornar sucesso
        echo json_encode([
            "status" => "ok",
            "message" => "Compra realizada com sucesso!",
            "codigo" => $codigoCompra,
            "refeicao" => $refeicao,
            "rfid" => $rfid,
            "nome" => $user['nome'],
            "valor" => $valorCompra,
            "saldo" => $novoSaldo
        ]);
    } catch (PDOException $e) {
        // Reverter a transação em caso de erro
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Erro ao processar a compra: " . $e->getMessage()]);
    }
    exit;
}

if ($method === 'GET' && preg_match('#^/users/([^/]+)/compra/([^/]+)$#', $endpoint, $matches)) {
    $codigoCompra = $matches[2];  // Código da compra

    // Consultar as informações da compra
    $stmt = $pdo->prepare("SELECT * FROM transacoes WHERE codigo = ?");
    $stmt->execute([$codigoCompra]);
    $compra = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$compra) {
        http_response_code(404);
        echo json_encode(["error" => "Compra não encontrada"]);
        exit;
    }

    // Retornar os dados da compra
    echo json_encode([
        "status" => "ok",
        "message" => "Compra encontrada com sucesso.",
        "codigo" => $compra['codigo'],
        "rfid" => $compra['rfid'],
        "nome" => $compra['nome'],
        "valor" => $compra['valor'],
        "data" => $compra['data']
    ]);
    exit;
}

// Endpoint para retornar usuários com paginação: /users?limit=10&offset=0
if ($method === 'GET' && $endpoint === '/users') {
    // Garantir que os valores de limit e offset sejam inteiros
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;

    // Corrigir a consulta para garantir que LIMIT e OFFSET sejam passados como números inteiros
    // Ordena os usuários por ID de forma crescente (numérica)
    $stmt = $pdo->prepare("SELECT * FROM alunos ORDER BY id ASC LIMIT :limit OFFSET :offset");

    // Passar os valores como parâmetros inteiros
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($usuarios);
    exit;
}

// Endpoint GET e PUT para buscar e editar dados de um usuário pelo RFID
if (preg_match('#^/users/([^/]+)$#', $endpoint, $matches)) {
    $rfid = $matches[1];  // RFID do usuário que será buscado ou editado

    // 1. Caso seja um GET, busca os dados do usuário
    if ($method === 'GET') {
        // Prepare a consulta SQL para buscar o usuário com o RFID fornecido
        $stmt = $pdo->prepare("SELECT * FROM alunos WHERE rfid = ?");
        $stmt->execute([$rfid]);

        // Busca o usuário
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifique se o usuário foi encontrado
        if ($user) {
            echo json_encode($user);
        } else {
            // Caso não encontrado, retorne um erro
            http_response_code(404);
            echo json_encode(["error" => "Usuário não encontrado"]);
        }
    }

    // 2. Caso seja um PUT, atualiza os dados do usuário (não permite editar o RFID)
    elseif ($method === 'PUT') {
        // Verifica se o corpo da requisição contém dados JSON
        $data = json_decode(file_get_contents('php://input'), true);

        // Localizar usuário e verificar se ele está com status pendente
        $stmt = $pdo->prepare("SELECT * FROM alunos WHERE rfid = ?");
        $stmt->execute([$rfid]);

        // Busca o usuário
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            echo json_encode(["error" => "Usuário não encontrado"]);
            exit;
        }

        $nome = $data['nome'];
        $matricula = $data['matricula'];

        // if (is_null($user['nome']) && !isset($data['nome'])) {
        //     http_response_code(400);
        //     echo json_encode(["error" => "O usuário não possui nome, defina."]);
        //     exit;
        // }
        // elseif(is_null($user['nome']) && isset($data['nome'])) {
        //     $nome = $data['nome'];
        // } 
        // else {
        //     $nome = $user['nome'];
        // }

        // if (is_null($user['matricula']) && !isset($data['matricula'])) {
        //     http_response_code(400);
        //     echo json_encode(["error" => "O usuário não possui matricula, defina."]);
        //     exit;
        // }
        // elseif(is_null($user['matricula']) && isset($data['matricula'])) {
        //     $matricula = $data['matricula'];
        // } 
        // else {
        //     $matricula = $user['matricula'];
        // }

        // Verificar se os dados foram recebidos
        // if (!isset($data['nome']) || !isset($data['matricula'])) {
        //     http_response_code(400);
        //     echo json_encode(["error" => "Nome e matrícula são obrigatórios."]);
        //     exit;
        // }

        if (isset($data['status']) || isset($data['rfid'])) {
            http_response_code(404);
            echo json_encode(["error" => "Requisição recusada."]);

            exit;
        }

        //Busca o status do usuário.
        $status = $user['status'];

        //Se saldo for fornecido ele altera se não permanece o mesmo.
        $saldo = isset($data['saldo']) ? $data['saldo'] : $user['saldo'];

        // Se o status enviado for 'pendente', altere para 'ativo'
        if ($status == 'pendente') {
            $status = 'ativo';
        }

        // Preparar a consulta para atualizar os dados do usuário
        $stmt = $pdo->prepare("UPDATE alunos SET nome = ?, matricula = ?, status = ?, saldo = ? WHERE rfid = ?");
        $stmt->execute([$nome, $matricula, $status, $saldo, $rfid]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => "ok"]);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "O usuário não recebeu nenhuma alteração."]);
        }
    } elseif ($method === 'POST') {

        // Extrair o RFID da URL
        $rfid = $matches[1];  // O RFID estará na variável $matches[1]

        // Verificar se o RFID não está vazio
        if (empty($rfid)) {
            http_response_code(400);
            echo json_encode(["error" => "RFID é obrigatório."]);
            exit;
        }

        // Verificar se o RFID já existe no banco
        $stmt = $pdo->prepare("SELECT * FROM alunos WHERE rfid = ?");
        $stmt->execute([$rfid]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            http_response_code(400);
            echo json_encode(["error" => "RFID já cadastrado no sistema."]);
            exit;
        }

        // Inserir novo usuário com o status "pendente"
        $status = 'pendente';  // Status é definido automaticamente como "pendente"

        try {
            $stmt = $pdo->prepare("INSERT INTO alunos (rfid, status) VALUES (?, ?)");
            $stmt->execute([$rfid, $status]);

            // Retornar sucesso
            echo json_encode(value: ["status" => "ok", "message" => "Usuário criado com sucesso!"]);

            exit;
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "Erro ao criar usuário: " . $e->getMessage()]);
        }
    } else {
        // Método não permitido
        http_response_code(405);
        echo json_encode(value: ["error" => "Método não permitido."]);
    }

    exit;
}

// Buscar usuários por status: /status/{status}
if ($method === 'GET' && preg_match('#^/status/([^/]+)$#', $endpoint, $matches)) {
    $status = $matches[1];

    // Verifique se o status é válido
    if (in_array($status, ['pendente', 'ativo', 'bloqueado'])) {
        // 📥 Paginacao
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;  // Limite de usuários por página
        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0; // Offset de página

        // Prepare a consulta SQL para buscar usuários com o status específico, com ordenação e paginacao
        $stmt = $pdo->prepare("SELECT * FROM alunos WHERE status = :status ORDER BY id ASC LIMIT :limit OFFSET :offset");

        // Usando bindParam para os parâmetros nomeados
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

        // Execute a consulta
        $stmt->execute();

        // Obter os resultados da consulta
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Retornar os dados dos usuários em formato JSON
        echo json_encode($usuarios);
    } else {
        // Caso o status seja inválido
        http_response_code(400);
        echo json_encode(["error" => "Status inválido"]);
    }
    exit;
}

// Endpoint para buscar usuários por termo: /busca?termo={termo}
if ($method === 'GET' && $endpoint === '/busca') {
    // Recupera o termo de busca
    $termo = isset($_GET['termo']) ? '%' . $_GET['termo'] . '%' : '%';

    // Consulta para buscar usuários com base no nome, RFID ou matrícula
    $stmt = $pdo->prepare("SELECT * FROM alunos WHERE nome LIKE :termo OR rfid LIKE :termo OR matricula LIKE :termo ORDER BY id ASC");
    $stmt->bindParam(':termo', $termo, PDO::PARAM_STR);
    $stmt->execute();

    // Obtém os usuários encontrados
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($usuarios);
    exit;
}

http_response_code(404);
echo json_encode(["error" => "Recurso não encontrado"]);
exit;