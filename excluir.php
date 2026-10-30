<?php
$raiz = "../";
require "../conexao.php";

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header("Location: listar.php");
    exit;
}

/* Verifica se o produto esta em algum frete antes de excluir */
$stmt = $conn->prepare("SELECT COUNT(*) AS t FROM frete_produtos WHERE produto_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$usos = $stmt->get_result()->fetch_assoc()['t'];

if ($usos > 0) {
    header("Location: listar.php?msg=" . urlencode("Este produto nao pode ser excluido: ele esta vinculado a um frete."));
    exit;
}

$stmt = $conn->prepare("DELETE FROM produtos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: listar.php?msg=" . urlencode("Produto excluido com sucesso."));
exit;
