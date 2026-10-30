<?php
$raiz = "../";
require "../conexao.php";

$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo    = trim($_POST['codigo'] ?? "");
    $nome      = trim($_POST['nome'] ?? "");
    $descricao = trim($_POST['descricao'] ?? "");
    $peso      = str_replace(",", ".", $_POST['peso'] ?? "0");
    $valor     = str_replace(",", ".", $_POST['valor'] ?? "0");

    if ($codigo === "" || $nome === "") {
        $erro = "Os campos Codigo e Nome sao obrigatorios.";
    } else {
        $stmt = $conn->prepare("INSERT INTO produtos (codigo, nome, descricao, peso, valor) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdd", $codigo, $nome, $descricao, $peso, $valor);

        if ($stmt->execute()) {
            header("Location: listar.php?msg=" . urlencode("Produto cadastrado com sucesso."));
            exit;
        }
        $erro = "Erro ao cadastrar: " . $conn->error;
    }
}

require "../includes/cabecalho.php";
?>

<h2 class="titulo-pagina mb-3">Cadastrar produto</h2>

<?php if ($erro): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Codigo *</label>
                <input type="text" name="codigo" class="form-control" required
                       value="<?php echo htmlspecialchars($_POST['codigo'] ?? ''); ?>">
            </div>

            <div class="col-md-9">
                <label class="form-label">Nome *</label>
                <input type="text" name="nome" class="form-control" required
                       value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
            </div>

            <div class="col-12">
                <label class="form-label">Descricao</label>
                <input type="text" name="descricao" class="form-control" maxlength="255"
                       value="<?php echo htmlspecialchars($_POST['descricao'] ?? ''); ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Peso (kg)</label>
                <input type="text" name="peso" class="form-control" placeholder="0.00"
                       value="<?php echo htmlspecialchars($_POST['peso'] ?? ''); ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Valor (R$)</label>
                <input type="text" name="valor" class="form-control" placeholder="0.00"
                       value="<?php echo htmlspecialchars($_POST['valor'] ?? ''); ?>">
            </div>

            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
                <a href="listar.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require "../includes/rodape.php"; ?>
