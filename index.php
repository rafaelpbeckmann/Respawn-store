<?php
// PÁGINA INICIAL TEMPORÁRIA
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loja</title>
</head>
<body>
    <h1>Loja</h1>

    <?php if (isset($_SESSION['cliente'])): ?>
        <p>Olá, <?= htmlspecialchars($_SESSION['cliente']['nome']) ?>!</p>
        <a href="logout.php">Sair</a>
    <?php else: ?>
        <p>Você não está logado.</p>
        <a href="login.php">Entrar</a> | <a href="cadastro.php">Cadastrar</a>
    <?php endif; ?>
</body>
</html>
