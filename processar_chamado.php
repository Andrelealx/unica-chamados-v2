<?php
session_start();
require_once 'inc/conexao.php';

// Define o timezone para Brasília
date_default_timezone_set('America/Sao_Paulo');

// Verifica se o formulário foi submetido via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Recebe e trata os dados enviados pelo formulário
$nome      = trim($_POST['nome'] ?? '');
$celular   = trim($_POST['celular'] ?? '');
$local     = trim($_POST['local'] ?? '');
$setor     = trim($_POST['setor'] ?? '');
$urgencia  = trim($_POST['urgencia'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');

// Validação dos campos obrigatórios
if (empty($nome) || empty($celular) || empty($local) || empty($setor) || empty($urgencia) || empty($descricao)) {
    $_SESSION['erro'] = "Por favor, preencha todos os campos obrigatórios.";
    header("Location: index.php");
    exit;
}

// Tratamento do upload do anexo, se existir
$anexo_path = null;
if (isset($_FILES['anexo']) && $_FILES['anexo']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $arquivo_nome = time() . "_" . basename($_FILES['anexo']['name']);
    $target_file = $upload_dir . $arquivo_nome;
    if (!move_uploaded_file($_FILES['anexo']['tmp_name'], $target_file)) {
        $_SESSION['erro'] = "Erro no upload do arquivo.";
        header("Location: index.php");
        exit;
    }
    $anexo_path = $target_file;
}

// Gera um protocolo mais curto, com o prefixo "UC-"
$protocolo = 'UC-' . strtoupper(substr(uniqid(), -6));

// Captura a data/hora atual no fuso horário de Brasília
$now = date('Y-m-d H:i:s');

// Insere os dados no banco de dados
$sql = "INSERT INTO tickets (nome, celular, local, setor, urgencia, descricao, anexo, status, protocolo, data_criacao, data_atualizacao)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Aberto', ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$result = $stmt->execute([$nome, $celular, $local, $setor, $urgencia, $descricao, $anexo_path, $protocolo, $now, $now]);

if ($result) {
    $_SESSION['sucesso'] = "Chamado criado com sucesso! Protocolo: " . $protocolo;
} else {
    $_SESSION['erro'] = "Erro ao criar o chamado. Tente novamente.";
}

header("Location: index.php");
exit;
?>
