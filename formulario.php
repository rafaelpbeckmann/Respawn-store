<?php
require 'config/conexao.php';

// Lista de estados válidos (usada no <select> e na validação)
$estados = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA',
            'PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

// Começam vazias: na primeira visita o formulário aparece em branco
$nome = $cpf = $email = $telefone = $cep = $logradouro = '';
$numero = $complemento = $bairro = $cidade = $uf = '';

$erros   = [];
$sucesso = false;

// Só entra aqui quando o formulário for enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ---------- PASSO B: guardar os dados em variáveis ----------
    $nome        = trim($_POST['nome'] ?? '');
    $cpf         = preg_replace('/\D/', '', $_POST['cpf'] ?? '');   // só números
    $email       = trim($_POST['email'] ?? '');
    $telefone    = trim($_POST['telefone'] ?? '');
    $senha       = $_POST['senha'] ?? '';                           // senha não leva trim
    $confirmar   = $_POST['confirmar_senha'] ?? '';
    $cep         = preg_replace('/\D/', '', $_POST['cep'] ?? '');   // só números
    $logradouro  = trim($_POST['logradouro'] ?? '');
    $numero      = trim($_POST['numero'] ?? '');
    $complemento = trim($_POST['complemento'] ?? '');
    $bairro      = trim($_POST['bairro'] ?? '');
    $cidade      = trim($_POST['cidade'] ?? '');
    $uf          = strtoupper(trim($_POST['uf'] ?? ''));

    // ---------- PASSO C: validação ----------
    if ($nome === '') {
        $erros[] = 'Preencha o nome.';
    }
    if (strlen($cpf) !== 11) {
        $erros[] = 'O CPF precisa ter 11 números.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    }
    if ($telefone === '') {
        $erros[] = 'Preencha o telefone.';
    }
    if (strlen($senha) < 8) {
        $erros[] = 'A senha precisa ter pelo menos 8 caracteres.';
    }
    if ($senha !== $confirmar) {
        $erros[] = 'As senhas não são iguais.';
    }
    if (strlen($cep) !== 8) {
        $erros[] = 'O CEP precisa ter 8 números.';
    }
    if ($logradouro === '' || $numero === '' || $bairro === '' || $cidade === '') {
        $erros[] = 'Preencha o endereço completo.';
    }
    if (!in_array($uf, $estados, true)) {
        $erros[] = 'Selecione um estado.';
    }

    // ---------- E-mail ou CPF já cadastrados? ----------
    if (empty($erros)) {
        $stmt = $pdo->prepare('SELECT email, cpf FROM clientes WHERE email = :email OR cpf = :cpf');
        $stmt->execute([':email' => $email, ':cpf' => $cpf]);

        foreach ($stmt->fetchAll() as $existente) {
            if (strcasecmp($existente['email'], $email) === 0) {
                $erros[] = 'Este e-mail já está cadastrado.';
            }
            if ($existente['cpf'] === $cpf) {
                $erros[] = 'Este CPF já está cadastrado.';
            }
        }
    }

    // ---------- PASSOS D e E: preparar e salvar ----------
    if (empty($erros)) {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);   // embaralha a senha

        $sql = 'INSERT INTO clientes
                    (nome, cpf, email, telefone, senha, cep, logradouro, numero, complemento, bairro, cidade, uf)
                VALUES
                    (:nome, :cpf, :email, :telefone, :senha, :cep, :logradouro, :numero, :complemento, :bairro, :cidade, :uf)';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome'        => $nome,
            ':cpf'         => $cpf,
            ':email'       => $email,
            ':telefone'    => $telefone,
            ':senha'       => $senhaHash,
            ':cep'         => $cep,
            ':logradouro'  => $logradouro,
            ':numero'      => $numero,
            ':complemento' => $complemento !== '' ? $complemento : null,  // vazio vira NULL no banco
            ':bairro'      => $bairro,
            ':cidade'      => $cidade,
            ':uf'          => $uf,
        ]);

        $sucesso = true;

        // Limpa o formulário depois de cadastrar
        $nome = $cpf = $email = $telefone = $cep = $logradouro = '';
        $numero = $complemento = $bairro = $cidade = $uf = '';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro</title>
</head>
<body>
    <h1>Cadastro</h1>

    <?php if ($sucesso): ?>
        <p style="color: green;">Cadastro realizado com sucesso!</p>
    <?php endif; ?>

    <?php if (!empty($erros)): ?>
        <ul style="color: red;">
            <?php foreach ($erros as $erro): ?>
                <li><?= htmlspecialchars($erro) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post">
        <fieldset>
            <legend>Dados pessoais</legend>
            <input type="text" name="nome" placeholder="preencha o seu nome" maxlength="100"
                   value="<?= htmlspecialchars($nome) ?>" required>
            <input type="text" name="cpf" placeholder="CPF (somente números)" inputmode="numeric"
                   pattern="[0-9]{11}" maxlength="11" title="Digite os 11 números do CPF, sem pontos ou traço"
                   value="<?= htmlspecialchars($cpf) ?>" required>
        </fieldset>

        <fieldset>
            <legend>Contato</legend>
            <input type="email" name="email" placeholder="preencha seu email" maxlength="150"
                   value="<?= htmlspecialchars($email) ?>" required>
            <input type="tel" name="telefone" placeholder="Número de telefone" maxlength="20"
                   value="<?= htmlspecialchars($telefone) ?>" required>
        </fieldset>

        <fieldset>
            <legend>Acesso</legend>
            <input type="password" name="senha" placeholder="crie uma senha" minlength="8"
                   autocomplete="new-password" required>
            <input type="password" name="confirmar_senha" placeholder="repita a senha" minlength="8"
                   autocomplete="new-password" required>
        </fieldset>

        <fieldset>
            <legend>Endereço</legend>
            <input type="text" name="cep" placeholder="preencha seu CEP" maxlength="9"
                   value="<?= htmlspecialchars($cep) ?>" required>
            <input type="text" name="logradouro" placeholder="rua, avenida, travessa..." maxlength="150"
                   value="<?= htmlspecialchars($logradouro) ?>" required>
            <input type="text" name="numero" placeholder="número" maxlength="10"
                   value="<?= htmlspecialchars($numero) ?>" required>
            <input type="text" name="complemento" placeholder="complemento (opcional)" maxlength="60"
                   value="<?= htmlspecialchars($complemento) ?>">
            <input type="text" name="bairro" placeholder="bairro" maxlength="80"
                   value="<?= htmlspecialchars($bairro) ?>" required>
            <input type="text" name="cidade" placeholder="cidade" maxlength="80"
                   value="<?= htmlspecialchars($cidade) ?>" required>
            <select name="uf" required>
                <option value="">estado (UF)</option>
                <?php foreach ($estados as $sigla): ?>
                    <option value="<?= $sigla ?>" <?= $uf === $sigla ? 'selected' : '' ?>><?= $sigla ?></option>
                <?php endforeach; ?>
            </select>
        </fieldset>

        <button type="submit">cadastrar</button>
    </form>
</body>
</html>
