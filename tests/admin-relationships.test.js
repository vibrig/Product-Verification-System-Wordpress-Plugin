// Run with: node tests/admin-relationships.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

function setup(initialClient, inherited) {
    const elements = new Map();
    function element(selector) {
        if (!elements.has(selector)) elements.set(selector, {
            value: '', props: {}, attrs: {}, handlers: {},
            val(value) { if (value === undefined) return this.value; this.value = value; return this; },
            prop(key, value) { if (value === undefined) return this.props[key]; this.props[key] = value; return this; },
            attr(key) { return this.attrs[key]; },
            on(event, handler) { this.handlers[event] = handler; return this; },
            find() { return element('selected-option'); }
        });
        return elements.get(selector);
    }
    const document = {};
    function $(selector) {
        if (selector === document) return { ready: callback => callback($), on() {} };
        return element(selector);
    }
    $.fn = {};
    element('#pcvs-client_id').value = initialClient;
    element('#pcvs-client_id').props.disabled = inherited;
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../assets/js/admin-scripts.js'), 'utf8'), { jQuery: $, document });
    return {
        client: element('#pcvs-client_id'),
        inheritedMessage: element('#pcvs-client-inherited'),
        manualMessage: element('#pcvs-client-manual'),
        project(clientId) {
            element('selected-option').attrs['data-client-id'] = clientId;
            element('#pcvs-project_id').handlers.change();
        }
    };
}

const manual = setup('101', false);
manual.project('202');
assert.equal(manual.client.value, '202');
assert.equal(manual.client.props.disabled, true);
assert.equal(manual.inheritedMessage.props.hidden, false);
assert.equal(manual.manualMessage.props.hidden, true);
manual.project('0');
assert.equal(manual.client.value, '101');
assert.equal(manual.client.props.disabled, false);
assert.equal(manual.manualMessage.props.hidden, false);
manual.client.val('303');
manual.client.handlers.change();
manual.project('202');
manual.project(undefined);
assert.equal(manual.client.value, '303');

const inherited = setup('202', true);
inherited.project('404');
assert.equal(inherited.client.value, '404');
inherited.project('0');
assert.equal(inherited.client.value, '');
assert.equal(inherited.client.props.disabled, false);
console.log('PASS: automatic client selection, project switching, manual selection restore, and selection prompts.');
