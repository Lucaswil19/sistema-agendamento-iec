function formatarCep(valor) {
    const digitos = String(valor ?? '').replace(/\D/g, '').substring(0, 8);

    if (digitos.length <= 5) {
        return digitos;
    }

    return digitos.substring(0, 5) + '-' + digitos.substring(5);
}

function montarEndereco(dados) {
    const partes = [dados.logradouro, dados.bairro]
        .filter((parte) => typeof parte === 'string' && parte.trim() !== '')
        .map((parte) => parte.trim());
    const cidade = typeof dados.cidade === 'string' ? dados.cidade.trim() : '';
    const uf = typeof dados.uf === 'string' ? dados.uf.trim() : '';
    const cidadeUf = cidade && uf ? cidade + ' - ' + uf : cidade || uf;

    if (cidadeUf) {
        partes.push(cidadeUf);
    }

    return partes.join(', ');
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-cep-consulta]').forEach(function (container) {
        const campoCep = container.querySelector('[data-cep-input]');
        const botao = container.querySelector('[data-cep-button]');
        const rotuloBotao = container.querySelector('[data-cep-button-label]');
        const indicadorCarregamento = container.querySelector('[data-cep-loading]');
        const status = container.querySelector('[data-cep-status]');
        const campoEndereco = document.querySelector('[data-cep-endereco]');

        if (!campoCep || !botao || !status || !campoEndereco) {
            return;
        }

        let cepInicial = campoCep.value.replace(/\D/g, '');
        let cepConsultado = null;
        let consultaAtual = null;
        let cepFoiAlterado = false;
        const formulario = campoCep.closest('form');

        if (status.id) {
            const descricoes = new Set((campoCep.getAttribute('aria-describedby') ?? '')
                .split(/\s+/)
                .filter(Boolean));
            descricoes.add(status.id);
            campoCep.setAttribute('aria-describedby', Array.from(descricoes).join(' '));
        }

        const exibirStatus = function (mensagem, tipo) {
            status.textContent = mensagem;
            status.classList.remove('hidden', 'text-red-700', 'text-emerald-700', 'text-stone-600');
            status.classList.add(tipo === 'erro' ? 'text-red-700' : tipo === 'sucesso' ? 'text-emerald-700' : 'text-stone-600');
            status.setAttribute('role', tipo === 'erro' ? 'alert' : 'status');
        };

        const limparStatus = function () {
            status.textContent = '';
            status.classList.add('hidden');
            status.removeAttribute('role');
        };

        const definirCarregamento = function (carregando) {
            botao.disabled = carregando;
            campoCep.setAttribute('aria-busy', String(carregando));
            rotuloBotao?.classList.toggle('hidden', carregando);
            indicadorCarregamento?.classList.toggle('hidden', !carregando);
            indicadorCarregamento?.classList.toggle('flex', carregando);

            if (carregando) {
                exibirStatus('Consultando CEP...', 'carregando');
            }
        };

        const mensagemDaResposta = function (payload, padrao) {
            const erroValidacao = Object.values(payload.errors ?? {})
                .flat()
                .find((mensagem) => typeof mensagem === 'string');

            return erroValidacao ?? (typeof payload.message === 'string' ? payload.message : padrao);
        };

        const consultar = async function (forcarNovaConsulta, acionadoPeloBotao) {
            const cep = campoCep.value.replace(/\D/g, '');

            if (cep.length !== 8) {
                campoCep.setAttribute('aria-invalid', 'true');
                exibirStatus('Informe um CEP válido com oito dígitos.', 'erro');

                if (acionadoPeloBotao) {
                    campoCep.focus();
                }

                return;
            }

            if (!forcarNovaConsulta && (cep === cepConsultado || consultaAtual?.cep === cep)) {
                return;
            }

            let url;

            try {
                url = new URL(container.dataset.cepUrl, window.location.origin);
            } catch {
                exibirStatus('Não foi possível consultar o CEP neste momento. Preencha o endereço manualmente.', 'erro');
                return;
            }

            if (url.origin !== window.location.origin) {
                exibirStatus('Não foi possível consultar o CEP neste momento. Preencha o endereço manualmente.', 'erro');
                return;
            }

            consultaAtual?.controller.abort();

            const controller = new AbortController();
            consultaAtual = { cep, controller };
            let expirou = false;
            const timeout = window.setTimeout(function () {
                expirou = true;
                controller.abort();
            }, 8000);
            url.searchParams.set('cep', cep);
            definirCarregamento(true);

            try {
                const response = await fetch(url.toString(), {
                    method: 'GET',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    signal: controller.signal,
                });

                let payload = {};

                try {
                    payload = await response.json();
                } catch {
                    payload = {};
                }

                if (controller.signal.aborted || consultaAtual?.controller !== controller) {
                    if (expirou) {
                        throw new Error('Tempo limite da consulta excedido.');
                    }
                    return;
                }

                if (!response.ok) {
                    if (response.status === 422) {
                        campoCep.setAttribute('aria-invalid', 'true');
                    }

                    throw new Error(mensagemDaResposta(
                        payload,
                        'Não foi possível consultar o CEP neste momento. Preencha o endereço manualmente.'
                    ));
                }

                const endereco = montarEndereco(payload);

                if (
                    typeof payload.cep !== 'string'
                    || typeof payload.cidade !== 'string'
                    || typeof payload.uf !== 'string'
                    || endereco === ''
                ) {
                    throw new Error('Não foi possível consultar o CEP neste momento. Preencha o endereço manualmente.');
                }

                campoCep.value = formatarCep(payload.cep);
                campoCep.removeAttribute('aria-invalid');
                campoEndereco.value = endereco;
                cepConsultado = cep;
                cepInicial = cep;
                cepFoiAlterado = false;
                exibirStatus('Endereço encontrado. Confira e complemente com número, quando necessário.', 'sucesso');

                if (acionadoPeloBotao) {
                    campoEndereco.focus();
                }
            } catch (error) {
                if (controller.signal.aborted && !expirou) {
                    return;
                }

                exibirStatus(
                    expirou
                        ? 'A consulta demorou para responder. Tente novamente ou preencha o endereço manualmente.'
                        : error instanceof Error
                        ? error.message
                        : 'Não foi possível consultar o CEP neste momento. Preencha o endereço manualmente.',
                    'erro'
                );

                if (acionadoPeloBotao) {
                    campoCep.focus();
                }
            } finally {
                window.clearTimeout(timeout);
                if (consultaAtual?.controller === controller) {
                    consultaAtual = null;
                    definirCarregamento(false);
                }
            }
        };

        campoCep.value = formatarCep(campoCep.value);

        campoCep.addEventListener('input', function () {
            consultaAtual?.controller.abort();
            consultaAtual = null;
            definirCarregamento(false);
            this.value = formatarCep(this.value);
            this.removeAttribute('aria-invalid');
            cepFoiAlterado = this.value.replace(/\D/g, '') !== cepInicial;
            limparStatus();
        });

        campoCep.addEventListener('blur', function (event) {
            // Nao inicie uma chamada externa ao navegar para outra tela ou minimizar.
            if (cepFoiAlterado && !document.hidden && event.relatedTarget
                && formulario?.contains(event.relatedTarget)
                && !botao.contains(event.relatedTarget)
                && !event.relatedTarget.closest('a, [type="submit"]')) {
                consultar(false, false);
            }
        });

        window.addEventListener('pagehide', function () {
            consultaAtual?.controller.abort();
            consultaAtual = null;
            definirCarregamento(false);
            limparStatus();
        });

        botao.addEventListener('click', function () {
            consultar(true, true);
        });
    });
});
