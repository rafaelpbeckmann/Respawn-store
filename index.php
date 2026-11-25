<?php
session_start();
require 'config/conexao.php';

$cliente = $_SESSION['cliente'] ?? null;


$produtos = $pdo->query(
    'SELECT p.id, p.nome, p.descricao, p.preco, p.estoque, p.imagem, c.nome AS categoria
     FROM produtos p
     JOIN categorias c ON c.id = p.categoria_id
     WHERE p.ativo = 1
     ORDER BY p.nome'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loja</title>
</head>
<body>
    <header>
        <h1>Loja</h1>
        <nav>
            <?php if ($cliente): ?>
                Olá, <?= htmlspecialchars($cliente['nome']) ?>!
                <?php if ($cliente['is_admin'] === 1): ?>
                    | <a href="admin/produtos.php">Admin</a>
                <?php endif; ?>
                | <a href="logout.php">Sair</a>
            <?php else: ?>
                <a href="login.php">Entrar</a> | <a href="cadastro.php">Cadastrar</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <h2>Produtos</h2>

        <?php if (empty($produtos)): ?>
            <p>Nenhum produto cadastrado ainda.</p>
        <?php endif; ?>

        <?php foreach ($produtos as $p): ?>
            <article style="border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;">
                <?php if ($p['imagem']): ?>
                    <img src="<?= htmlspecialchars($p['imagem']) ?>" width="150" alt="<?= htmlspecialchars($p['nome']) ?>">
                <?php endif; ?>

                <h3><?= htmlspecialchars($p['nome']) ?></h3>
                <p><small><?= htmlspecialchars($p['categoria']) ?></small></p>

                <?php if ($p['descricao']): ?>
                    <p><?= htmlspecialchars($p['descricao']) ?></p>
                <?php endif; ?>

                <p><strong>R$ <?= number_format($p['preco'], 2, ',', '.') ?></strong></p>

                <?php if ($p['estoque'] > 0): ?>
                    <!-- O carrinho.php será criado no próximo passo -->
                    <form method="post" action="carrinho.php">
                        <input type="hidden" name="acao" value="adicionar">
                        <input type="hidden" name="produto_id" value="<?= $p['id'] ?>">
                        <button type="submit">Adicionar ao carrinho</button>
                    </form>
                <?php else: ?>
                    <p style="color: gray;">Esgotado</p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </main>
</body>
</html>