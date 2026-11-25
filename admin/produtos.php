<?php
session_start();
require '../config/conexao.php';

if (empty($_SESSION['cliente']) || $_SESSION['cliente']['is_admin'] !== 1) {
    header('Location: ../login.php');
    exit;
}

$categorias = $pdo->query('SELECT id, nome FROM categorias ORDER BY nome')->fetchAll();

$nome = $descricao = $preco = $estoque = '';
$categoria_id = '';
$erros   = [];
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome         = trim($_POST['nome'] ?? '');
    $categoria_id = (int) ($_POST['categoria_id'] ?? 0);
    $descricao    = trim($_POST['descricao'] ?? '');
    $preco        = str_replace(',', '.', trim($_POST['preco'] ?? ''));
    $estoque      = trim($_POST['estoque'] ?? '');

    if ($nome === '') {
        $erros[] = 'Preencha o nome do produto.';
    }
    if (!in_array($categoria_id, array_column($categorias, 'id'))) {
        $erros[] = 'Selecione uma categoria.';
    }
    if (!is_numeric($preco) || $preco <= 0) {
        $erros[] = 'Informe um preço válido.';
    }
    if (!ctype_digit($estoque)) {
        $erros[] = 'O estoque precisa ser um número inteiro (0 ou mais).';
    }

    $caminhoImagem = null;
    $arquivo = $_FILES['imagem'] ?? null;

    if ($arquivo && $arquivo['error'] !== UPLOAD_ERR_NO_FILE) {
        $extensoes = ['jpg', 'jpeg', 'png', 'webp'];
        $extensao  = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            $erros[] = 'Erro ao enviar a imagem.';
        } elseif (!in_array($extensao, $extensoes, true) || !getimagesize($arquivo['tmp_name'])) {
            $erros[] = 'A imagem precisa ser JPG, PNG ou WEBP.';
        } elseif ($arquivo['size'] > 2 * 1024 * 1024) {
            $erros[] = 'A imagem pode ter no máximo 2 MB.';
        } elseif (empty($erros)) {
            $nomeArquivo = uniqid('produto_') . '.' . $extensao;
            $pasta = __DIR__ . '/../img/produtos/';

            if (!is_dir($pasta)) {
                mkdir($pasta, 0777, true);
            }

            move_uploaded_file($arquivo['tmp_name'], $pasta . $nomeArquivo);
            $caminhoImagem = 'img/produtos/' . $nomeArquivo;
        }
    }

    if (empty($erros)) {
        $stmt = $pdo->prepare(
            'INSERT INTO produtos (categoria_id, nome, descricao, preco, estoque, imagem)
             VALUES (:categoria_id, :nome, :descricao, :preco, :estoque, :imagem)'
        );
        $stmt->execute([
            ':categoria_id' => $categoria_id,
            ':nome'         => $nome,
            ':descricao'    => $descricao !== '' ? $descricao : null,
            ':preco'        => $preco,
            ':estoque'      => (int) $estoque,
            ':imagem'       => $caminhoImagem,
        ]);

        $sucesso = true;
        $nome = $descricao = $preco = $estoque = '';
        $categoria_id = '';
    }
}

$produtos = $pdo->query(
    'SELECT p.id, p.nome, p.preco, p.estoque, p.imagem, c.nome AS categoria
     FROM produtos p
     JOIN categorias c ON c.id = p.categoria_id
     ORDER BY p.id DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Produtos</title>
</head>
<body>
    <p><a href="../index.php">← Voltar para a loja</a></p>
    <h1>Cadastro de produtos</h1>

    <?php if ($sucesso): ?>
        <p style="color: green;">Produto cadastrado com sucesso!</p>
    <?php endif; ?>

    <?php if (!empty($erros)): ?>
        <ul style="color: red;">
            <?php foreach ($erros as $erro): ?>
                <li><?= htmlspecialchars($erro) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    
    <form method="post" enctype="multipart/form-data">
        <input type="text" name="nome" placeholder="nome do produto" maxlength="120"
               value="<?= htmlspecialchars($nome) ?>" required>

        <select name="categoria_id" required>
            <option value="">categoria</option>
            <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= (int) $categoria_id === (int) $cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <textarea name="descricao" placeholder="descrição (opcional)"><?= htmlspecialchars($descricao) ?></textarea>

        <input type="number" name="preco" placeholder="preço" step="0.01" min="0.01"
               value="<?= htmlspecialchars($preco) ?>" required>
        <input type="number" name="estoque" placeholder="estoque" min="0"
               value="<?= htmlspecialchars($estoque) ?>" required>

        <input type="file" name="imagem" accept=".jpg,.jpeg,.png,.webp">

        <button type="submit">cadastrar produto</button>
    </form>

    <h2>Produtos cadastrados</h2>
    <table border="1" cellpadding="6">
        <tr>
            <th>ID</th><th>Imagem</th><th>Nome</th><th>Categoria</th><th>Preço</th><th>Estoque</th>
        </tr>
        <?php foreach ($produtos as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td>
                    <?php if ($p['imagem']): ?>
                        <img src="../<?= htmlspecialchars($p['imagem']) ?>" width="60" alt="">
                    <?php else: ?>
                        sem imagem
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($p['nome']) ?></td>
                <td><?= htmlspecialchars($p['categoria']) ?></td>
                <td>R$ <?= number_format($p['preco'], 2, ',', '.') ?></td>
                <td><?= $p['estoque'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>