document.addEventListener('DOMContentLoaded', function () {
    const camposData = document.querySelectorAll('[data-mascara="data"]');
    const camposHora = document.querySelectorAll('[data-mascara="hora"]');

    function formatarData(valor) {
        if (/^\d{4}-\d{2}-\d{2}$/.test(valor)) {
            return valor.substring(8, 10) + '/' + valor.substring(5, 7) + '/' + valor.substring(0, 4);
        }

        valor = valor.replace(/\D/g, '');
        valor = valor.substring(0, 8);

        if (valor.length > 2) {
            valor = valor.substring(0, 2) + '/' + valor.substring(2);
        }

        if (valor.length > 5) {
            valor = valor.substring(0, 5) + '/' + valor.substring(5, 9);
        }

        return valor;
    }

    function formatarHora(valor) {
        valor = valor.replace(/\D/g, '');
        valor = valor.substring(0, 4);

        if (valor.length > 2) {
            valor = valor.substring(0, 2) + ':' + valor.substring(2, 4);
        }

        return valor;
    }

    camposData.forEach(function (campo) {
        campo.value = formatarData(campo.value);

        campo.addEventListener('input', function () {
            campo.value = formatarData(campo.value);
        });
    });

    camposHora.forEach(function (campo) {
        campo.value = formatarHora(campo.value);

        campo.addEventListener('input', function () {
            campo.value = formatarHora(campo.value);
        });
    });
});