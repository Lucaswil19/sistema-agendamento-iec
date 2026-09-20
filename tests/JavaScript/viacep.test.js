import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/js/beneficiarios/viacep.js', import.meta.url), 'utf8');
const flush = () => new Promise(resolve => setImmediate(resolve));

function setup({ ignoreAbort = false } = {}) {
    const element = () => ({
        value: '', textContent: '', disabled: false, attributes: new Map(), listeners: new Map(),
        classList: { add() {}, remove() {}, toggle() {} },
        setAttribute(name, value) { this.attributes.set(name, value); },
        getAttribute(name) { return this.attributes.get(name) ?? null; },
        removeAttribute(name) { this.attributes.delete(name); },
        addEventListener(name, callback) { this.listeners.set(name, callback); },
        dispatch(name, event = {}) { this.listeners.get(name)?.call(this, event); },
        contains(target) { return this === target; },
        closest() { return null; },
        focus() {},
    });
    const input = element();
    const button = element();
    const address = element();
    const status = element();
    const nextField = element();
    const form = { contains: target => [input, button, address, nextField].includes(target) };
    input.closest = () => form;
    const elements = {
        '[data-cep-input]': input, '[data-cep-button]': button,
        '[data-cep-status]': status, '[data-cep-button-label]': element(),
        '[data-cep-loading]': element(),
    };
    const container = {
        dataset: { cepUrl: '/beneficiarios/consultar-cep' },
        querySelector: selector => elements[selector],
    };
    const document = {
        hidden: false,
        querySelectorAll: () => [container],
        querySelector: () => address,
        addEventListener: (_, callback) => callback(),
    };
    const timers = new Map();
    const events = new Map();
    let nextTimer = 0;
    const requests = [];
    const window = {
        location: { origin: 'http://localhost' },
        setTimeout: (callback, ms) => { timers.set(++nextTimer, { callback, ms }); return nextTimer; },
        clearTimeout: id => timers.delete(id),
        addEventListener: (name, callback) => events.set(name, callback),
    };
    const fetch = (url, { signal }) => new Promise((resolve, reject) => {
        requests.push({
            url, signal,
            resolve: payload => resolve({ ok: true, json: async () => payload }),
        });
        if (!ignoreAbort) signal.addEventListener('abort', () => reject(new DOMException('Aborted', 'AbortError')));
    });
    vm.runInNewContext(source, { document, window, fetch, URL, AbortController, DOMException });
    const type = value => { input.value = value; input.dispatch('input'); };
    return { document, input, button, address, status, nextField, requests, timers, events, type };
}

const payload = (cep, logradouro) => ({ cep, logradouro, bairro: 'Centro', cidade: 'Cidade', uf: 'SC' });

test('sair da tela ou minimizar nao inicia consulta de CEP', () => {
    const app = setup();
    app.type('01001000');
    app.input.dispatch('blur', { relatedTarget: { closest: () => ({}) } });
    app.input.dispatch('blur', { relatedTarget: null });
    app.document.hidden = true;
    app.input.dispatch('blur', { relatedTarget: app.nextField });
    assert.equal(app.requests.length, 0);
});

test('clicar em consultar envia uma unica requisicao, sem duplicar no blur', async () => {
    const app = setup();
    app.type('01001000');
    app.input.dispatch('blur', { relatedTarget: app.button });
    app.button.dispatch('click');
    assert.equal(app.requests.length, 1);
    app.requests[0].resolve(payload('01001-000', 'Rua correta'));
    await flush();
    assert.match(app.address.value, /Rua correta/);
    assert.equal(app.button.disabled, false);
    assert.equal(app.timers.size, 0);
});

test('resposta de um CEP antigo nao sobrescreve a digitacao atual', async () => {
    const app = setup({ ignoreAbort: true });
    app.type('01001000');
    app.input.dispatch('blur', { relatedTarget: app.nextField });
    app.type('88350000');
    assert.equal(app.requests[0].signal.aborted, true);
    app.input.dispatch('blur', { relatedTarget: app.nextField });
    app.requests[0].resolve(payload('01001-000', 'Rua antiga'));
    await flush();
    assert.equal(app.input.value, '88350-000');
    assert.equal(app.address.value, '');
    assert.equal(app.button.disabled, true);
    app.requests[1].resolve(payload('88350-000', 'Rua nova'));
    await flush();
    assert.match(app.address.value, /Rua nova/);
});

test('tempo limite libera os controles e permite uma nova tentativa', async () => {
    const app = setup();
    app.type('01001000');
    app.button.dispatch('click');
    const timer = [...app.timers.values()][0];
    assert.equal(timer.ms, 8000);
    timer.callback();
    await flush();
    assert.equal(app.button.disabled, false);
    assert.match(app.status.textContent, /demorou/);
    assert.equal(app.timers.size, 0);
    app.button.dispatch('click');
    assert.equal(app.requests.length, 2);
});

test('sair da pagina cancela a consulta e limpa o carregamento', async () => {
    const app = setup();
    app.type('01001000');
    app.button.dispatch('click');
    app.events.get('pagehide')();
    await flush();
    assert.equal(app.requests[0].signal.aborted, true);
    assert.equal(app.button.disabled, false);
    assert.equal(app.status.textContent, '');
    assert.equal(app.timers.size, 0);
});
