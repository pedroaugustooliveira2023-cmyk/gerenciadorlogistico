<?php
$raiz = "../";
require "../conexao.php";
require "../includes/cabecalho.php";

$busca = isset($_GET['busca']) ? trim($_GET['busca']) : "";

if ($busca !== "") {
    $stmt = $conn->prepare("SELECT * FROM produtos WHERE nome LIKE ? OR codigo LIKE ? ORDER BY id DESC");
    $like = "%" . $busca . "%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $lista = $stmt->get_result();
} else {
    $lista = $conn->query("SELECT * FROM produtos ORDER BY id DESC");
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="titulo-pagina mb-0">Produtos</h2>
    <a href="cadastrar.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Novo produto
    </a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo htmlspecialchars($_GET['msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form class="row g-2 mb-3" method="get">
    <div class="col-md-5">
        <input type="text" name="busca" class="form-control" placeholder="Buscar por nome ou codigo"
               value="<?php echo htmlspecialchars($busca); ?>">
    </div>
    <div class="col-auto">
        <button class="btn btn-outline-secondary">Buscar</button>
    </div>
    <div class="col-auto">
        <a href="listar.php" class="btn btn-link">Limpar</a>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Codigo</th>
                    <th>Nome</th>
                    <th class="text-end">Peso (kg)</th>
                    <th class="text-end">Valor (R$)</th>
                    <th class="text-center">Acoes</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($lista && $lista->num_rows > 0): ?>
                <?php while ($p = $lista->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $p['id']; ?></td>
                        <td><?php echo htmlspecialchars($p['codigo']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($p['nome']); ?>
                            <?php if (!empty($p['descricao'])): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($p['descricao']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><?php echo number_format($p['peso'], 2, ',', '.'); ?></td>
                        <td class="text-end"><?php echo number_format($p['valor'], 2, ',', '.'); ?></td>
                        <td class="text-center text-nowrap">
                            <a href="editar.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="excluir.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-danger btn-excluir">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Nenhum produto encontrado.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require "../includes/rodape.php"; ?>
