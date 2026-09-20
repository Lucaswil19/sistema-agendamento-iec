document.addEventListener('DOMContentLoaded', function () {
    const campoDataNascimento = document.getElementById('data_nascimento');
    const campoIdade = document.getElementById('idade');

    if (!campoDataNascimento || !campoIdade) {
        return;
    }

    function calcularIdade(dataDigitada) {
        if (!dataDigitada || dataDigitada.length !== 10) {
            return '';
        }

        const partes = dataDigitada.split('/');

        if (partes.length !== 3) {
            return '';
        }

        const dia = parseInt(partes[0], 10);
        const mes = parseInt(partes[1], 10) - 1;
        const ano = parseInt(partes[2], 10);

        const nascimento = new Date(ano, mes, dia);

        if (
            nascimento.getFullYear() !== ano ||
            nascimento.getMonth() !== mes ||
            nascimento.getDate() !== dia
        ) {
            return '';
        }

        const hoje = new Date();

        if (nascimento > hoje) {
            return '';
        }

        let idade = hoje.getFullYear() - nascimento.getFullYear();

        const aindaNaoFezAniversario =
            hoje.getMonth() < nascimento.getMonth() ||
            (
                hoje.getMonth() === nascimento.getMonth() &&
                hoje.getDate() < nascimento.getDate()
            );

        if (aindaNaoFezAniversario) {
            idade--;
        }

        return idade;
    }

    function atualizarIdade() {
        campoIdade.value = calcularIdade(campoDataNascimento.value);
    }

    campoDataNascimento.addEventListener('input', atualizarIdade);
    campoDataNascimento.addEventListener('blur', atualizarIdade);

    atualizarIdade();
});
