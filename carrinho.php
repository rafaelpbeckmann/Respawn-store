<?php

require 'config/conexao.php';

// Se o carrinho ainda não existe, cria vazio
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao       = $_POST['acao'] ?? '';
    $produto_id = (int) ($_POST['produto_id'] ?? 0);

    // Busca o estoque do produto para não deixar passar do limite
    $stmt = $pdo->prepare('SELECT estoque FROM produtos WHERE id = :id AND ativo = 1');
    $stmt->execute([':id' => $produto_id]);
    $estoque = $stmt->fetchColumn();   // o número do estoque, ou false se não existir

    if ($acao === 'adicionar' && $estoque !== false) {
        $atual = $_SESSION['carrinho'][$produto_id] ?? 0;
        // Soma 1, mas nunca passa do estoque
        $_SESSION['carrinho'][$produto_id] = min($atual + 1, (int) $estoque);
    }

    if ($acao === 'atualizar' && $estoque !== false) {
        $quantidade = (int) ($_POST['quantidade'] ?? 1);
        $quantidade = max(1, min($quantidade, (int) $estoque));   // entre 1 e o estoque
        $_SESSION['carrinho'][$produto_id] = $quantidade;
    }

    if ($acao === 'remover') {
        unset($_SESSION['carrinho'][$produto_id]);
    }

    if ($acao === 'limpar') {
        $_SESSION['carrinho'] = [];
    }

    // Remove itens que ficaram com 0 (produto esgotado)
    $_SESSION['carrinho'] = array_filter($_SESSION['carrinho']);

    // Recarrega a página com GET (evita reenviar o formulário ao apertar F5)
    header('Location: carrinho.php');
    exit;
}


$itens    = [];
$subtotal = 0;

if (!empty($_SESSION['carrinho'])) {
    // Cria um "?" para cada produto: IN (?, ?, ?)
    $ids = array_keys($_SESSION['carrinho']);
    $marcadores = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("SELECT id, nome, preco, estoque, imagem FROM produtos WHERE id IN ($marcadores)");
    $stmt->execute($ids);

    foreach ($stmt->fetchAll() as $produto) {
        $quantidade = $_SESSION['carrinho'][$produto['id']];
        $total_item = $produto['preco'] * $quantidade;

        $produto['quantidade'] = $quantidade;
        $produto['total_item'] = $total_item;

        $itens[]   = $produto;
        $subtotal += $total_item;
    }
}

// Função pequena para mostrar dinheiro no formato brasileiro
function reais($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrinho</title>
</head>
<body>
    <p><a href="index.php">← Continuar comprando</a></p>
    <h1>Carrinho</h1>

    <?php if (empty($itens)): ?>
        <p>Seu carrinho está vazio.</p>
    <?php else: ?>
        <table border="1" cellpadding="6">
            <tr>
                <th>Produto</th><th>Preço</th><th>Quantidade</th><th>Total</th><th></th>
            </tr>

            <?php foreach ($itens as $item): ?>
                <tr>
                    <td>
                        <?php if ($item['imagem']): ?>
                            <img src="<?= htmlspecialchars($item['imagem']) ?>" width="50" alt="">
                        <?php endif; ?>
                        <?= htmlspecialchars($item['nome']) ?>
                    </td>
                    <td><?= reais($item['preco']) ?></td>
                    <td>
                        <!-- Alterar quantidade -->
                        <form method="post">
                            <input type="hidden" name="acao" value="atualizar">
                            <input type="hidden" name="produto_id" value="<?= $item['id'] ?>">
                            <input type="number" name="quantidade" value="<?= $item['quantidade'] ?>"
                                   min="1" max="<?= $item['estoque'] ?>" style="width: 60px;">
                            <button type="submit">atualizar</button>
                        </form>
                    </td>
                    <td><?= reais($item['total_item']) ?></td>
                    <td>
                        <!-- Remover item -->
                        <form method="post">
                            <input type="hidden" name="acao" value="remover">
                            <input type="hidden" name="produto_id" value="<?= $item['id'] ?>">
                            <button type="submit">remover</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <p><strong>Subtotal: <?= reais($subtotal) ?></strong></p>
        <p><small>O frete é calculado na próxima etapa, pelo CEP.</small></p>

        <form method="post">
            <input type="hidden" name="acao" value="limpar">
            <button type="submit">esvaziar carrinho</button>
        </form>

        <!-- finalizar.php será criado no próximo passo -->
        <p><a href="finalizar.php">Finalizar compra →</a></p>
    <?php endif; ?>
</body>
</html>