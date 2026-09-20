function formatarCpf(valor) {
    valor = valor.replace(/\D/g, '');
    valor = valor.substring(0, 11);

    valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
    valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
    valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');

    return valor;
}

function formatarTelefone(valor) {
    valor = valor.replace(/\D/g, '');

    if (valor.startsWith('55')) {
        valor = valor.substring(2);
    }

    valor = valor.substring(0, 11);

    if (valor.length <= 2) {
        return '+55 (' + valor;
    }

    if (valor.length <= 7) {
        return '+55 (' + valor.substring(0, 2) + ') ' + valor.substring(2);
    }

    return '+55 (' 
        + valor.substring(0, 2)
        + ') '
        + valor.substring(2, 7)
        + '-'
        + valor.substring(7, 11);
}

document.addEventListener('DOMContentLoaded', function () {
    const campoCpf = document.getElementById('cpf');
    const campoTelefone = document.getElementById('telefone');

    if (campoCpf) {
        campoCpf.value = formatarCpf(campoCpf.value);

        campoCpf.addEventListener('input', function () {
            this.value = formatarCpf(this.value);
        });
    }

    if (campoTelefone) {
        campoTelefone.value = formatarTelefone(campoTelefone.value);

        campoTelefone.addEventListener('input', function () {
            this.value = formatarTelefone(this.value);
        });
    }
});