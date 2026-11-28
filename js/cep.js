
const campoCep    = document.getElementById('cep');
const campoStatus = document.getElementById('cep-status');
const campoUf     = document.getElementById('uf');

function formatarReais(valor) {
    return valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function atualizarFrete() {
    const spanFrete = document.getElementById('valor-frete');
    if (!spanFrete || typeof TABELA_FRETE === 'undefined') return;

    const uf = campoUf.value;
    let frete = TABELA_FRETE[uf];

    if (frete === undefined) {
        spanFrete.textContent = 'informe o CEP';
        document.getElementById('valor-total').textContent = formatarReais(SUBTOTAL);
        return;
    }
    if (SUBTOTAL >= FRETE_GRATIS_ACIMA) {
        frete = 0;
    }

    spanFrete.textContent = frete === 0 ? 'Grátis' : formatarReais(frete);
    document.getElementById('valor-total').textContent = formatarReais(SUBTOTAL + frete);
}

async function buscarCep() {
    const cep = campoCep.value.replace(/\D/g, '');

    if (cep.length !== 8) return;

    campoStatus.textContent = 'Buscando...';

    try {
        const resposta = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const dados = await resposta.json();

        if (dados.erro) {
            campoStatus.textContent = 'CEP não encontrado.';
            return;
        }

        document.getElementById('logradouro').value = dados.logradouro;
        document.getElementById('bairro').value     = dados.bairro;
        document.getElementById('cidade').value     = dados.localidade;
        campoUf.value = dados.uf;

        campoStatus.textContent = '';
        document.getElementById('numero').focus();
        atualizarFrete();

    } catch (erro) {
        campoStatus.textContent = 'Não foi possível consultar o CEP. Preencha manualmente.';
    }
}

campoCep.addEventListener('blur', buscarCep);
campoCep.addEventListener('input', () => {
    if (campoCep.value.replace(/\D/g, '').length === 8) buscarCep();
});
campoUf.addEventListener('change', atualizarFrete);

atualizarFrete();