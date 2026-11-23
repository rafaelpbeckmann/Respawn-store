<?php
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
    <form name="formulario" method="post">
        <fieldset>
            <legend>Dados pessoais</legend>
            <input type="text" name="nome" placeholder="preencha o seu nome" maxlength="100" required>
            <input type="text" name="cpf" placeholder="CPF (somente números)" inputmode="numeric" pattern="[0-9]{11}" maxlength="11" title="Digite os 11 números do CPF, sem pontos ou traço" required>
        </fieldset>

        <fieldset>
            <legend>Contato</legend>
            <input type="email" name="email" placeholder="preencha seu email" required>
            <input type="tel" name="telefone" placeholder="Número de telefone" required>
        </fieldset>

        <fieldset>
            <legend>Acesso</legend>
            <input type="password" name="senha" placeholder="crie uma senha" minlength="8" autocomplete="new-password" required>
        </fieldset>

        <fieldset>
            <legend>Endereço</legend>
            <input type="text" name="cep" placeholder="preencha seu CEP" maxlength="8" required>
            <input type="text" name="logradouro" placeholder="rua, avenida, travessa..." maxlength="150" required>
            <input type="text" name="numero" placeholder="número" maxlength="10" required>
            <input type="text" name="complemento" placeholder="complemento (opcional)" maxlength="60">
            <input type="text" name="bairro" placeholder="bairro" maxlength="80" required>
            <input type="text" name="cidade" placeholder="cidade" maxlength="80" required>
            <select name="uf" required>
                <option value="">estado (UF)</option>
                <option value="AC">AC</option>
                <option value="AL">AL</option>
                <option value="AP">AP</option>
                <option value="AM">AM</option>
                <option value="BA">BA</option>
                <option value="CE">CE</option>
                <option value="DF">DF</option>
                <option value="ES">ES</option>
                <option value="GO">GO</option>
                <option value="MA">MA</option>
                <option value="MT">MT</option>
                <option value="MS">MS</option>
                <option value="MG">MG</option>
                <option value="PA">PA</option>
                <option value="PB">PB</option>
                <option value="PR">PR</option>
                <option value="PE">PE</option>
                <option value="PI">PI</option>
                <option value="RJ">RJ</option>
                <option value="RN">RN</option>
                <option value="RS">RS</option>
                <option value="RO">RO</option>
                <option value="RR">RR</option>
                <option value="SC">SC</option>
                <option value="SP">SP</option>
                <option value="SE">SE</option>
                <option value="TO">TO</option>
            </select>
        </fieldset>
        <button type="submit">cadastrar</button>
    </form>
</body>
</html>