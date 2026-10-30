<?php
$raiz = "../";
require "../conexao.php";

/* Data escolhida: vem da URL (?data=AAAA-MM-DD) ou do formulario; padrao = hoje */
$data = $_GET['data'] ?? $_POST['data'] ?? date('Y-m-d');

/* Quem pede download recebe o arquivo HTML pronto para salvar/imprimir */
$baixar = isset($_GET['baixar']);

$stmt = $conn->prepare("
    SELECT f.id, f.codigo, f.valor_total,
           t.nome AS transportador, t.cnpj,
           o.cidade AS o_cidade, o.uf AS o_uf,
           d.cidade AS d_cidade, d.uf AS d_uf,
           COUNT(fp.id) AS qtd_produtos,
           COALESCE(SUM(p.peso * fp.quantidade), 0) AS peso_total
    FROM fretes f
    INNER JOIN transportadores t ON t.id = f.transportador_id
    INNER JOIN enderecos o ON o.id = f.origem_id
    INNER JOIN enderecos d ON d.id = f.destino_id
    LEFT JOIN frete_produtos fp ON fp.frete_id = f.id
    LEFT JOIN produtos p ON p.id = fp.produto_id
    WHERE f.data_frete = ?
    GROUP BY f.id, f.codigo, f.valor_total, t.nome, t.cnpj, o.cidade, o.uf, d.cidade, d.uf
    ORDER BY f.id
");
$stmt->bind_param("s", $data);
$stmt->execute();
$fretes = $stmt->get_result();

$linhas = [];
$totalValor = 0;
$totalPeso = 0;
$totalProdutos = 0;

while ($f = $fretes->fetch_assoc()) {
    $linhas[] = $f;
    $totalValor += $f['valor_total'];
    $totalPeso += $f['peso_total'];
    $totalProdutos += $f['qtd_produtos'];
}

$dataBr = date('d/m/Y', strtotime($data));

/* ---------------- Modo download ---------------- */
if ($baixar) {
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="relatorio-fretes-' . $data . '.html"');
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Relatorio de fretes - <?php echo $dataBr; ?></title>
        <style>
            body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #222; margin: 30px; }
            h1 { font-size: 18px; margin-bottom: 4px; }
            p.sub { color: #666; margin-top: 0; margin-bottom: 18px; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #ccc; padding: 6px 8px; }
            th { background: #1f3b63; color: #fff; text-align: left; }
            td.num, th.num { text-align: right; }
            tfoot td { font-weight: bold; background: #f2f4f7; }
        </style>
    </head>
    <body>
    <h1>Relatorio de fretes</h1>
    <p class="sub">Data: <?php echo $dataBr; ?> &middot; Gerenciador Logistico</p>

    <table>
        <thead>
            <tr>
                <th>Codigo</th>
                <th>Transportador</th>
                <th>CNPJ</th>
                <th>Origem</th>
                <th>Destino</th>
                <th class="num">Produtos</th>
                <th class="num">Peso (kg)</th>
                <th class="num">Valor (R$)</th>
            </tr>
        </thead>
        <tbody>
        <?php if (count($linhas) === 0): ?>
            <tr><td colspan="8">Nenhum frete registrado nesta data.</td></tr>
        <?php else: ?>
            <?php foreach ($linhas as $l): ?>
                <tr>
                    <td><?php echo htmlspecialchars($l['codigo']); ?></td>
                    <td><?php echo htmlspecialchars($l['transportador']); ?></td>
                    <td><?php echo htmlspecialchars($l['cnpj']); ?></td>
                    <td><?php echo htmlspecialchars($l['o_cidade'] . '/' . $l['o_uf']); ?></td>
                    <td><?php echo htmlspecialchars($l['d_cidade'] . '/' . $l['d_uf']); ?></td>
                    <td class="num"><?php echo $l['qtd_produtos']; ?></td>
                    <td class="num"><?php echo number_format($l['peso_total'], 2, ',', '.'); ?></td>
                    <td class="num"><?php echo number_format($l['valor_total'], 2, ',', '.'); ?></td>
                </tr>
            <?php endforeach; ?>
            <tfoot>
                <tr>
                    <td colspan="5">TOTAIS (<?php echo count($linhas); ?> frete(s))</td>
                    <td class="num"><?php echo $totalProdutos; ?></td>
                    <td class="num"><?php echo number_format($totalPeso, 2, ',', '.'); ?></td>
                    <td class="num"><?php echo number_format($totalValor, 2, ',', '.'); ?></td>
                </tr>
            </tfoot>
        <?php endif; ?>
        </tbody>
    </table>

    <p class="sub" style="margin-top:18px">
        Relatorio gerado em <?php echo date('d/m/Y H:i'); ?>. Para salvar em PDF, use Ctrl+P e escolha "Salvar como PDF".
    </p>
    </body>
    </html>
    <?php
    exit;
}

/* ---------------- Modo tela ---------------- */
require "../includes/cabecalho.php";

/* Datas que possuem fretes, para facilitar a escolha */
$datas = $conn->query("SELECT DISTINCT data_frete FROM fretes ORDER BY data_frete DESC");
?>

<h2 class="titulo-pagina mb-3">Relatorio de fretes por dia</h2>

<div class="card mb-3 no-print">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-md-4">
                <label class="form-label">Escolha o dia</label>
                <input type="date" name="data" class="form-control" value="<?php echo htmlspecialchars($data); ?>">
            </div>
            <div class="col-auto">
                <button class="btn btn-primary"><i class="bi bi-search"></i> Consultar</button>
            </div>
            <div class="col-auto">
                <a href="relatorio.php?data=<?php echo urlencode($data); ?>&baixar=1" class="btn btn-success">
                    <i class="bi bi-download"></i> Baixar relatorio
                </a>
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Imprimir / PDF
                </button>
            </div>
        </form>

        <?php if ($datas && $datas->num_rows > 0): ?>
            <p class="text-muted small mb-0 mt-3">
                Dias com fretes registrados:
                <?php while ($d = $datas->fetch_assoc()): ?>
                    <a href="relatorio.php?data=<?php echo urlencode($d['data_frete']); ?>" class="badge bg-secondary text-decoration-none">
                        <?php echo date('d/m/Y', strtotime($d['data_frete'])); ?>
                    </a>
                <?php endwhile; ?>
            </p>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <span>Fretes de <?php echo $dataBr; ?></span>
        <span class="text-muted small"><?php echo count($linhas); ?> frete(s)</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Codigo</th>
                    <th>Transportador</th>
                    <th>Origem</th>
                    <th>Destino</th>
                    <th class="text-center">Produtos</th>
                    <th class="text-end">Peso (kg)</th>
                    <th class="text-end">Valor (R$)</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($linhas) > 0): ?>
                <?php foreach ($linhas as $l): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($l['codigo']); ?></td>
                        <td><?php echo htmlspecialchars($l['transportador']); ?></td>
                        <td><?php echo htmlspecialchars($l['o_cidade'] . '/' . $l['o_uf']); ?></td>
                        <td><?php echo htmlspecialchars($l['d_cidade'] . '/' . $l['d_uf']); ?></td>
                        <td class="text-center"><?php echo $l['qtd_produtos']; ?></td>
                        <td class="text-end"><?php echo number_format($l['peso_total'], 2, ',', '.'); ?></td>
                        <td class="text-end"><?php echo number_format($l['valor_total'], 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Nenhum frete registrado nesta data.</td></tr>
            <?php endif; ?>
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="4" class="text-end">Totais</th>
                    <th class="text-center"><?php echo $totalProdutos; ?></th>
                    <th class="text-end"><?php echo number_format($totalPeso, 2, ',', '.'); ?></th>
                    <th class="text-end"><?php echo number_format($totalValor, 2, ',', '.'); ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require "../includes/rodape.php"; ?>
