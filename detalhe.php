<?php
$raiz = "../";
require "../conexao.php";
require "../includes/cabecalho.php";

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $conn->prepare("
    SELECT f.*, t.nome AS transportador, t.cnpj, t.modal,
           o.logradouro AS o_logr, o.numero AS o_num, o.bairro AS o_bairro, o.cidade AS o_cidade, o.uf AS o_uf, o.cep AS o_cep,
           d.logradouro AS d_logr, d.numero AS d_num, d.bairro AS d_bairro, d.cidade AS d_cidade, d.uf AS d_uf, d.cep AS d_cep
    FROM fretes f
    INNER JOIN transportadores t ON t.id = f.transportador_id
    INNER JOIN enderecos o ON o.id = f.origem_id
    INNER JOIN enderecos d ON d.id = f.destino_id
    WHERE f.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$frete = $stmt->get_result()->fetch_assoc();

if (!$frete) {
    echo '<div class="alert alert-warning">Frete nao encontrado.</div>';
    require "../includes/rodape.php";
    exit;
}

$stmt = $conn->prepare("
    SELECT p.codigo, p.nome, p.peso, p.valor, fp.quantidade
    FROM frete_produtos fp
    INNER JOIN produtos p ON p.id = fp.produto_id
    WHERE fp.frete_id = ?
    ORDER BY p.nome
");
$stmt->bind_param("i", $id);
$stmt->execute();
$itens = $stmt->get_result();

$pesoTotal = 0;
$valorProdutos = 0;
$linhas = [];
while ($i = $itens->fetch_assoc()) {
    $linhas[] = $i;
    $pesoTotal += $i['peso'] * $i['quantidade'];
    $valorProdutos += $i['valor'] * $i['quantidade'];
}

function enderecoCompleto($logr, $num, $bairro, $cidade, $uf, $cep) {
    return $logr . ', ' . $num . ' - ' . $bairro . ', ' . $cidade . '/' . $uf . ' - CEP ' . $cep;
}
?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo htmlspecialchars($_GET['msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="titulo-pagina mb-0">Frete <?php echo htmlspecialchars($frete['codigo']); ?></h2>
    <div class="d-flex gap-2">
        <a href="relatorio.php?data=<?php echo $frete['data_frete']; ?>" class="btn btn-outline-secondary">
            <i class="bi bi-file-earmark-text"></i> Relatorio do dia
        </a>
        <a href="listar.php" class="btn btn-outline-secondary">Voltar</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">Transportador</div>
            <div class="card-body">
                <p class="mb-1"><strong><?php echo htmlspecialchars($frete['transportador']); ?></strong></p>
                <p class="mb-1 text-muted small">CNPJ: <?php echo htmlspecialchars($frete['cnpj']); ?></p>
                <p class="mb-0 text-muted small">Modal: <?php echo htmlspecialchars($frete['modal']); ?></p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">Origem</div>
            <div class="card-body">
                <p class="mb-0 small">
                    <?php echo htmlspecialchars(enderecoCompleto($frete['o_logr'], $frete['o_num'], $frete['o_bairro'], $frete['o_cidade'], $frete['o_uf'], $frete['o_cep'])); ?>
                </p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">Destino</div>
            <div class="card-body">
                <p class="mb-0 small">
                    <?php echo htmlspecialchars(enderecoCompleto($frete['d_logr'], $frete['d_num'], $frete['d_bairro'], $frete['d_cidade'], $frete['d_uf'], $frete['d_cep'])); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">
        Produtos do frete
        <span class="badge bg-info text-dark ms-2"><?php echo count($linhas); ?> item(ns)</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Codigo</th>
                    <th>Produto</th>
                    <th class="text-center">Qtd</th>
                    <th class="text-end">Peso unit. (kg)</th>
                    <th class="text-end">Peso total (kg)</th>
                    <th class="text-end">Valor unit. (R$)</th>
                    <th class="text-end">Subtotal (R$)</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($linhas as $l): ?>
                <tr>
                    <td><?php echo htmlspecialchars($l['codigo']); ?></td>
                    <td><?php echo htmlspecialchars($l['nome']); ?></td>
                    <td class="text-center"><?php echo $l['quantidade']; ?></td>
                    <td class="text-end"><?php echo number_format($l['peso'], 2, ',', '.'); ?></td>
                    <td class="text-end"><?php echo number_format($l['peso'] * $l['quantidade'], 2, ',', '.'); ?></td>
                    <td class="text-end"><?php echo number_format($l['valor'], 2, ',', '.'); ?></td>
                    <td class="text-end"><?php echo number_format($l['valor'] * $l['quantidade'], 2, ',', '.'); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="4" class="text-end">Totais</th>
                    <th class="text-end"><?php echo number_format($pesoTotal, 2, ',', '.'); ?></th>
                    <th class="text-end">Mercadorias</th>
                    <th class="text-end"><?php echo number_format($valorProdutos, 2, ',', '.'); ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-body d-flex justify-content-between">
        <div>
            <p class="mb-1"><strong>Data do frete:</strong> <?php echo date('d/m/Y', strtotime($frete['data_frete'])); ?></p>
            <p class="mb-0"><strong>Peso total transportado:</strong> <?php echo number_format($pesoTotal, 2, ',', '.'); ?> kg</p>
        </div>
        <div class="text-end">
            <p class="mb-1 text-muted small">Valor do frete</p>
            <p class="mb-0 fs-3 text-success">R$ <?php echo number_format($frete['valor_total'], 2, ',', '.'); ?></p>
        </div>
    </div>
</div>

<?php require "../includes/rodape.php"; ?>
