<?php
session_start();                 
require 'config/conexao.php';


if (isset($_SESSION['cliente'])) {
    header('Location: index.php');
    exit;
}

$email = '';
$erro  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    
    $stmt = $pdo->prepare('SELECT id, nome, senha, is_admin FROM clientes WHERE email = :email');
    $stmt->execute([':email' => $email]);
    $cliente = $stmt->fetch();   

    
    if ($cliente && password_verify($senha, $cliente['senha'])) {

        session_regenerate_id(true);   

        
        $_SESSION['cliente'] = [
            'id'       => $cliente['id'],
            'nome'     => $cliente['nome'],
            'is_admin' => (int) $cliente['is_admin'],
        ];

        header('Location: index.php');   
        exit;
    }

    $erro = 'E-mail ou senha incorretos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar</title>
</head>
<body>
    <h1>Entrar</h1>

    <?php if ($erro !== ''): ?>
        <p style="color: red;"><?= htmlspecialchars($erro) ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="email" name="email" placeholder="seu e-mail"
               value="<?= htmlspecialchars($email) ?>" required>
        <input type="password" name="senha" placeholder="sua senha"
               autocomplete="current-password" required>
        <button type="submit">entrar</button>
    </form>

    <p>Ainda não tem conta? <a href="cadastro.php">Cadastre-se</a></p>
</body>
</html>
