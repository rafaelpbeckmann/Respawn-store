<?php
session_start();
require 'config/conexao.php';

if (empty($_SESSION['cliente'])) {
    header('Location: login.php');
    exit;
}
$cliente_id = $_SESSION['cliente']['id'];

$UF_LOJA = 'PA';
$FRETE_GRATIS_ACIMA = 500.00;

$regioes = [
    'Norte'        => ['AC','AM','AP','PA','RO','RR','TO'],
    'Nordeste'     => ['AL','BA','CE','MA','PB','PE','PI','RN','SE'],
    'Centro-Oeste' => ['DF','GO','MS','MT'],
    'Sudeste'      => ['ES','MG','RJ','SP'],
    'Sul'          => ['PR','RS','SC'],
];
$precoPorRegiao = [
    'Norte'        => 25.00,
    'Nordeste'     => 35.00,
    'Centro-Oeste' => 40.00,
    'Sudeste'      => 45.00,
    'Sul'          => 50.00,
];
$PRECO_MESMO_ESTADO = 15.00;

function calcularFrete($uf, $subtotal) {
    global $UF_LOJA, $FRETE_GRATIS_ACIMA, $regioes, $precoPorRegiao, $PRECO_MESMO_ESTADO;

    if ($subtotal >= $FRETE_GRATIS_ACIMA) return 0.00;
    if ($uf === $UF_LOJA)                 return $PRECO_MESMO_ESTADO;

    foreach ($regioes as $regiao => $estados) {
        if (in_array($uf, $estados, true)) {
            return $precoPorRegiao[$regiao];
        }
    }
    return null;
}

$tabelaFrete = [];
foreach ($regioes as $estados) {
    foreach ($estados as $uf) {
        $tabelaFrete[$uf] = calcularFrete($uf, 0);
    }
}

function reais($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

$carrinho = $_SESSION['carrinho'] ?? [];
$itens    = [];
$subtotal = 0;

if (!empty($carrinho)) {
    $ids = array_keys($carrinho);
    $marcadores = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, nome, preco, estoque FROM produtos WHERE id IN ($marcadores) AND ativo = 1");
    $stmt->execute($ids);

    foreach ($stmt->fetchAll() as $p) {
        $p['quantidade'] = $carrinho[$p['id']];
        $p['total_item'] = $p['preco'] * $p['quantidade'];
        $itens[]   = $p;
        $subtotal += $p['total_item'];
    }
}

$stmt = $pdo->prepare('SELECT cep, logradouro, numero, complemento, bairro, cidade, uf FROM clientes WHERE id = :id');
$stmt->execute([':id' => $cliente_id]);
$endereco = $stmt->fetch() ?: [];

$campos = ['cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'uf'];
foreach ($campos as $campo) {
    $endereco[$campo] = $endereco[$campo] ?? '';
}

$erros = [];
$compraFeita = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($campos as $campo) {
        $endereco[$campo] = trim($_POST[$campo] ?? '');
    }
    $endereco['cep'] = preg_replace('/\D/', '', $endereco['cep']);
    $endereco['uf']  = strtoupper($endereco['uf']);

    if (empty($itens)) {
        $erros[] = 'Seu carrinho está vazio.';
    }
    if (strlen($endereco['cep']) !== 8) {
        $erros[] = 'Informe um CEP válido (8 números).';
    }
    if ($endereco['logradouro'] === '' || $endereco['numero'] === '' ||
        $endereco['bairro'] === '' || $endereco['cidade'] === '') {
        $erros[] = 'Preencha o endereço completo.';
    }

    $frete = calcularFrete($endereco['uf'], $subtotal);
    if ($frete === null) {
        $erros[] = 'Selecione um estado válido.';
    }

    foreach ($itens as $item) {
        if ($item['quantidade'] > $item['estoque']) {
            $erros[] = 'Estoque insuficiente para "' . $item['nome'] . '". Disponível: ' . $item['estoque'] . '.';
        }
    }

    if (empty($erros)) {
        $total = $subtotal + $frete;

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO compras (cliente_id, cep, logradouro, numero, complemento, bairro, cidade, uf, subtotal, frete, total)
                 VALUES (:cliente_id, :cep, :logradouro, :numero, :complemento, :bairro, :cidade, :uf, :subtotal, :frete, :total)'
            );
            $stmt->execute([
                ':cliente_id'  => $cliente_id,
                ':cep'         => $endereco['cep'],
                ':logradouro'  => $endereco['logradouro'],
                ':numero'      => $endereco['numero'],
                ':complemento' => $endereco['complemento'] !== '' ? $endereco['complemento'] : null,
                ':bairro'      => $endereco['bairro'],
                ':cidade'      => $endereco['cidade'],
                ':uf'          => $endereco['uf'],
                ':subtotal'    => $subtotal,
                ':frete'       => $frete,
                ':total'       => $total,
            ]);
            $compra_id = $pdo->lastInsertId();

            $inserirItem = $pdo->prepare(
                'INSERT INTO itens_compra (compra_id, produto_id, quantidade, preco_unitario)
                 VALUES (:compra_id, :produto_id, :quantidade, :preco)'
            );
            $baixarEstoque = $pdo->prepare(
                'UPDATE produtos SET estoque = estoque - :qtd
                 WHERE id = :id AND estoque >= :qtd_min'
            );

            foreach ($itens as $item) {
                $inserirItem->execute([
                    ':compra_id'  => $compra_id,
                    ':produto_id' => $item['id'],
                    ':quantidade' => $item['quantidade'],
                    ':preco'      => $item['preco'],
                ]);

                $baixarEstoque->execute([
                    ':qtd'     => $item['quantidade'],
                    ':id'      => $item['id'],
                    ':qtd_min' => $item['quantidade'],
                ]);
                if ($baixarEstoque->rowCount() === 0) {
                    throw new Exception('Estoque insuficiente para "' . $item['nome'] . '".');
                }
            }

            $pdo->commit();

            $_SESSION['carrinho'] = [];
            $compraFeita = $compra_id;

        } catch (Exception $e) {
            $pdo->rollBack();
            $erros[] = 'Não foi possível concluir a compra: ' . $e->getMessage();
        }
    }
}

$freteInicial = calcularFrete($endereco['uf'], $subtotal);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar compra</title>
</head>
<body>
    <p><a href="carrinho.php">← Voltar ao carrinho</a></p>
    <h1>Finalizar compra</h1>

    <?php if ($compraFeita): ?>

        <p style="color: green;"><strong>Compra realizada com sucesso!</strong></p>
        <p>Número do pedido: <strong>#<?= $compraFeita ?></strong></p>
        <p><a href="index.php">Voltar para a loja</a></p>

    <?php elseif (empty($itens)): ?>

        <p>Seu carrinho está vazio. <a href="index.php">Ver produtos</a></p>

    <?php else: ?>

        <?php if (!empty($erros)): ?>
            <ul style="color: red;">
                <?php foreach ($erros as $erro): ?>
                    <li><?= htmlspecialchars($erro) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <h2>Resumo</h2>
        <table border="1" cellpadding="6">
            <tr><th>Produto</th><th>Qtd</th><th>Total</th></tr>
            <?php foreach ($itens as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['nome']) ?></td>
                    <td><?= $item['quantidade'] ?></td>
                    <td><?= reais($item['total_item']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <form method="post">
            <fieldset>
                <legend>Endereço de entrega</legend>

                <input type="text" id="cep" name="cep" placeholder="CEP" maxlength="9"
                       value="<?= htmlspecialchars($endereco['cep']) ?>" required>
                <span id="cep-status"></span>

                <input type="text" id="logradouro" name="logradouro" placeholder="rua, avenida..." maxlength="150"
                       value="<?= htmlspecialchars($endereco['logradouro']) ?>" required>
                <input type="text" id="numero" name="numero" placeholder="número" maxlength="10"
                       value="<?= htmlspecialchars($endereco['numero']) ?>" required>
                <input type="text" id="complemento" name="complemento" placeholder="complemento (opcional)" maxlength="60"
                       value="<?= htmlspecialchars($endereco['complemento']) ?>">
                <input type="text" id="bairro" name="bairro" placeholder="bairro" maxlength="80"
                       value="<?= htmlspecialchars($endereco['bairro']) ?>" required>
                <input type="text" id="cidade" name="cidade" placeholder="cidade" maxlength="80"
                       value="<?= htmlspecialchars($endereco['cidade']) ?>" required>

                <select id="uf" name="uf" required>
                    <option value="">estado (UF)</option>
                    <?php foreach (array_keys($tabelaFrete) as $sigla): ?>
                        <option value="<?= $sigla ?>" <?= $endereco['uf'] === $sigla ? 'selected' : '' ?>><?= $sigla ?></option>
                    <?php endforeach; ?>
                </select>
            </fieldset>

            <p>Subtotal: <strong><?= reais($subtotal) ?></strong></p>
            <p>Frete: <strong id="valor-frete"><?= $freteInicial === null ? 'informe o CEP' : reais($freteInicial) ?></strong></p>
            <p>Total: <strong id="valor-total"><?= reais($subtotal + ($freteInicial ?? 0)) ?></strong></p>
            <p><small>Frete grátis em compras acima de <?= reais($FRETE_GRATIS_ACIMA) ?>.</small></p>

            <button type="submit">confirmar compra</button>
        </form>

        <script>
            const TABELA_FRETE = <?= json_encode($tabelaFrete) ?>;
            const SUBTOTAL = <?= json_encode((float) $subtotal) ?>;
            const FRETE_GRATIS_ACIMA = <?= json_encode($FRETE_GRATIS_ACIMA) ?>;
        </script>
        <script src="js/cep.js"></script>

    <?php endif; ?>
</body>
</html>