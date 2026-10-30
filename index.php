<?php
require __DIR__ . '/conexao.php';

function listarRegistros(PDO $pdo, string $sql): array
{
    return $pdo->query($sql)->fetchAll();
}

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $acao = $_POST['acao'] ?? '';

    try {
        if ($acao === 'salvar_produto') {
            $stmt = $pdo->prepare('INSERT INTO produtos (codigo, nome, descricao, peso, valor) VALUES (:codigo, :nome, :descricao, :peso, :valor)');
            $stmt->execute([
                ':codigo' => trim($_POST['codigo'] ?? ''),
                ':nome' => trim($_POST['nome'] ?? ''),
                ':descricao' => trim($_POST['descricao'] ?? ''),
                ':peso' => (float) ($_POST['peso'] ?? 0),
                ':valor' => (float) ($_POST['valor'] ?? 0),
            ]);
            $mensagem = 'Produto cadastrado com sucesso.';
        }

        if ($acao === 'salvar_endereco') {
            $stmt = $pdo->prepare('INSERT INTO enderecos (cep, logradouro, numero, complemento, bairro, cidade, uf) VALUES (:cep, :logradouro, :numero, :complemento, :bairro, :cidade, :uf)');
            $stmt->execute([
                ':cep' => preg_replace('/\D+/', '', $_POST['cep'] ?? ''),
                ':logradouro' => trim($_POST['logradouro'] ?? ''),
                ':numero' => trim($_POST['numero'] ?? ''),
                ':complemento' => trim($_POST['complemento'] ?? ''),
                ':bairro' => trim($_POST['bairro'] ?? ''),
                ':cidade' => trim($_POST['cidade'] ?? ''),
                ':uf' => strtoupper(trim($_POST['uf'] ?? '')),
            ]);
            $mensagem = 'Endereço salvo com sucesso.';
        }

        if ($acao === 'salvar_transportador') {
            $stmt = $pdo->prepare('INSERT INTO transportadores (nome, cnpj, telefone, email, modal) VALUES (:nome, :cnpj, :telefone, :email, :modal)');
            $stmt->execute([
                ':nome' => trim($_POST['nome'] ?? ''),
                ':cnpj' => preg_replace('/\D+/', '', $_POST['cnpj'] ?? ''),
                ':telefone' => trim($_POST['telefone'] ?? ''),
                ':email' => trim($_POST['email'] ?? ''),
                ':modal' => trim($_POST['modal'] ?? 'Rodoviario'),
            ]);
            $mensagem = 'Transportador cadastrado com sucesso.';
        }

        if ($acao === 'salvar_frete') {
            $stmt = $pdo->prepare('INSERT INTO fretes (codigo, transportador_id, origem_id, destino_id, data_frete, valor_total) VALUES (:codigo, :transportador_id, :origem_id, :destino_id, :data_frete, :valor_total)');
            $stmt->execute([
                ':codigo' => trim($_POST['codigo'] ?? ''),
                ':transportador_id' => (int) ($_POST['transportador_id'] ?? 0),
                ':origem_id' => (int) ($_POST['origem_id'] ?? 0),
                ':destino_id' => (int) ($_POST['destino_id'] ?? 0),
                ':data_frete' => $_POST['data_frete'] ?? date('Y-m-d'),
                ':valor_total' => (float) ($_POST['valor_total'] ?? 0),
            ]);
            $mensagem = 'Frete cadastrado com sucesso.';
        }

        if ($acao === 'salvar_item_frete') {
            $stmt = $pdo->prepare('INSERT INTO frete_produtos (frete_id, produto_id, quantidade) VALUES (:frete_id, :produto_id, :quantidade)');
            $stmt->execute([
                ':frete_id' => (int) ($_POST['frete_id'] ?? 0),
                ':produto_id' => (int) ($_POST['produto_id'] ?? 0),
                ':quantidade' => (int) ($_POST['quantidade'] ?? 1),
            ]);
            $mensagem = 'Produto adicionado ao frete.';
        }
    } catch (Throwable $e) {
        $mensagem = 'Erro ao salvar: ' . $e->getMessage();
    }
}

if ($pdo) {
    $produtos = listarRegistros($pdo, 'SELECT * FROM produtos ORDER BY id DESC');
    $enderecos = listarRegistros($pdo, 'SELECT * FROM enderecos ORDER BY id DESC');
    $transportadores = listarRegistros($pdo, 'SELECT * FROM transportadores ORDER BY id DESC');
    $fretes = listarRegistros($pdo, "SELECT f.*, t.nome AS transportador, o.logradouro AS origem, d.logradouro AS destino
        FROM fretes f
        LEFT JOIN transportadores t ON t.id = f.transportador_id
        LEFT JOIN enderecos o ON o.id = f.origem_id
        LEFT JOIN enderecos d ON d.id = f.destino_id
        ORDER BY f.id DESC");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciador Logístico</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
    <header class="topo">
        <div>
            <h1>Gerenciador Logístico</h1>
            <p>Controle de produtos, endereços, transportadores e fretes.</p>
        </div>
    </header>

    <?php if ($dbError): ?>
        <div class="alerta erro">
            <strong>Não foi possível conectar ao banco de dados.</strong>
            <p><?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    <?php elseif ($mensagem): ?>
        <div class="alerta sucesso"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($pdo): ?>
        <main class="container">
            <section class="grid">
                <article class="card">
                    <h2>Produto</h2>
                    <form method="post">
                        <input type="hidden" name="acao" value="salvar_produto">
                        <label>Código<input type="text" name="codigo" required></label>
                        <label>Nome<input type="text" name="nome" required></label>
                        <label>Descrição<textarea name="descricao" rows="3"></textarea></label>
                        <div class="dupla">
                            <label>Peso<input type="number" step="0.01" min="0" name="peso" required></label>
                            <label>Valor<input type="number" step="0.01" min="0" name="valor" required></label>
                        </div>
                        <button type="submit">Salvar produto</button>
                    </form>
                </article>

                <article class="card">
                    <h2>Endereço</h2>
                    <form method="post">
                        <input type="hidden" name="acao" value="salvar_endereco">
                        <label>CEP<input type="text" name="cep" class="cep" maxlength="9" placeholder="00000-000" required></label>
                        <label>Logradouro<input type="text" name="logradouro" required></label>
                        <div class="dupla">
                            <label>Número<input type="text" name="numero" required></label>
                            <label>Complemento<input type="text" name="complemento"></label>
                        </div>
                        <div class="dupla">
                            <label>Bairro<input type="text" name="bairro" required></label>
                            <label>UF<input type="text" name="uf" maxlength="2" style="text-transform: uppercase;" required></label>
                        </div>
                        <label>Cidade<input type="text" name="cidade" required></label>
                        <button type="submit">Salvar endereço</button>
                    </form>
                </article>

                <article class="card">
                    <h2>Transportador</h2>
                    <form method="post">
                        <input type="hidden" name="acao" value="salvar_transportador">
                        <label>Nome<input type="text" name="nome" required></label>
                        <label>CNPJ<input type="text" name="cnpj" class="cnpj" maxlength="18" placeholder="00.000.000/0000-00" required></label>
                        <label>Telefone<input type="tel" name="telefone" class="telefone" maxlength="15" placeholder="(00) 00000-0000"></label>
                        <label>E-mail<input type="email" name="email"></label>
                        <label>Modal<input type="text" name="modal" value="Rodoviario"></label>
                        <button type="submit">Salvar transportador</button>
                    </form>
                </article>

                <article class="card">
                    <h2>Frete</h2>
                    <form method="post">
                        <input type="hidden" name="acao" value="salvar_frete">
                        <label>Código<input type="text" name="codigo" required></label>
                        <div class="dupla">
                            <label>Transportador
                                <select name="transportador_id" required>
                                    <option value="">Selecione</option>
                                    <?php foreach ($transportadores as $transportador): ?>
                                        <option value="<?= (int) $transportador['id'] ?>"><?= htmlspecialchars($transportador['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>Data<input type="date" name="data_frete" required></label>
                        </div>
                        <div class="dupla">
                            <label>Origem
                                <select name="origem_id" required>
                                    <option value="">Selecione</option>
                                    <?php foreach ($enderecos as $endereco): ?>
                                        <option value="<?= (int) $endereco['id'] ?>"><?= htmlspecialchars($endereco['logradouro'] . ', ' . $endereco['numero'] . ' - ' . $endereco['cidade'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>Destino
                                <select name="destino_id" required>
                                    <option value="">Selecione</option>
                                    <?php foreach ($enderecos as $endereco): ?>
                                        <option value="<?= (int) $endereco['id'] ?>"><?= htmlspecialchars($endereco['logradouro'] . ', ' . $endereco['numero'] . ' - ' . $endereco['cidade'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                        <label>Valor total<input type="number" step="0.01" min="0" name="valor_total" required></label>
                        <button type="submit">Salvar frete</button>
                    </form>
                </article>
            </section>

            <section class="card painel">
                <h2>Adicionar produto ao frete</h2>
                <form method="post" class="linha">
                    <input type="hidden" name="acao" value="salvar_item_frete">
                    <label>Frete
                        <select name="frete_id" required>
                            <option value="">Selecione</option>
                            <?php foreach ($fretes as $frete): ?>
                                <option value="<?= (int) $frete['id'] ?>"><?= htmlspecialchars($frete['codigo'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Produto
                        <select name="produto_id" required>
                            <option value="">Selecione</option>
                            <?php foreach ($produtos as $produto): ?>
                                <option value="<?= (int) $produto['id'] ?>"><?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Quantidade<input type="number" min="1" name="quantidade" value="1" required></label>
                    <button type="submit">Adicionar</button>
                </form>
            </section>

            <section class="tabelas">
                <article class="card">
                    <h2>Produtos</h2>
                    <table>
                        <thead>
                            <tr><th>Código</th><th>Nome</th><th>Peso</th><th>Valor</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produtos as $produto): ?>
                                <tr>
                                    <td><?= htmlspecialchars($produto['codigo'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(number_format((float) $produto['peso'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?> kg</td>
                                    <td>R$ <?= htmlspecialchars(number_format((float) $produto['valor'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </article>

                <article class="card">
                    <h2>Transportadores</h2>
                    <table>
                        <thead>
                            <tr><th>Nome</th><th>CNPJ</th><th>Modal</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transportadores as $transportador): ?>
                                <tr>
                                    <td><?= htmlspecialchars($transportador['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($transportador['cnpj'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($transportador['modal'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </article>

                <article class="card">
                    <h2>Fretes</h2>
                    <table>
                        <thead>
                            <tr><th>Código</th><th>Origem</th><th>Destino</th><th>Valor</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fretes as $frete): ?>
                                <tr>
                                    <td><?= htmlspecialchars($frete['codigo'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($frete['origem'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($frete['destino'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>R$ <?= htmlspecialchars(number_format((float) $frete['valor_total'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </article>
            </section>
        </main>
    <?php endif; ?>

    <script src="css/js/campos.js"></script>
</body>
</html>
