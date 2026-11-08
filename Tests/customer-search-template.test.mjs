import assert from 'node:assert/strict';
import {test} from 'node:test';
import {readFileSync} from 'node:fs';
import {fileURLToPath, pathToFileURL} from 'node:url';
import {dirname, resolve as resolvePath} from 'node:path';

const __dirname = dirname(fileURLToPath(import.meta.url));
const vendorRoot = resolvePath(__dirname, '..', 'Assets', 'JS', 'vendor', 'eta', 'dist', 'core.js');
const {Eta} = await import(pathToFileURL(vendorRoot).href);

const twigPath = resolvePath(__dirname, '..', 'View', 'Modal', 'POS', 'CustomerSearch.html.twig');
const twigSource = readFileSync(twigPath, 'utf8');

const match = twigSource.match(/<script id="customer:list:template" type="text\/template">([\s\S]*?)<\/script>/);
assert.ok(match, 'customer:list:template script block must exist in the twig file');

const rawTemplate = match[1];

function resolveTwig(value) {
    return value.replace(/\{\{\s*trans\('([^']+)'\)\s*\}\}/g, '$1');
}

const template = resolveTwig(rawTemplate);

function render(data) {
    const eta = new Eta({useWith: true});
    return eta.renderString(template, data);
}

function countMatches(haystack, needle) {
    let count = 0;
    let index = haystack.indexOf(needle);
    while (index !== -1) {
        count += 1;
        index = haystack.indexOf(needle, index + needle.length);
    }
    return count;
}

test('renders skeleton block while loading is true', () => {
    const html = render({customers: [], loading: true});
    assert.match(html, /aria-busy="true"/);
    assert.match(html, /animate-pulse/);
    assert.doesNotMatch(html, /add-customer-btn/);
});

test('renders translated empty state when there are no customers', () => {
    const html = render({customers: [], loading: false});
    assert.match(html, /no-results/);
    assert.match(html, /no-customers-found/);
    assert.match(html, /fa-user-slash/);
    assert.doesNotMatch(html, /animate-pulse/);
});

test('renders a customer row with the first letter of the description', () => {
    const html = render({
        customers: [{code: 'CUST-001', description: 'Acme Corporation'}],
        loading: false,
    });
    assert.match(html, /data-code="CUST-001"/);
    assert.match(html, />\s*A\s*<\/span>/);
    assert.doesNotMatch(html, /fa-user fa-solid fa-user/);
    assert.doesNotMatch(html, /fa-check/);
    assert.match(html, /rounded-lg border border-slate-200 bg-white px-3 py-2 min-h-14/);
});

test('falls back to # when description is blank', () => {
    const html = render({
        customers: [{code: 'CUST-002', description: '   '}],
        loading: false,
    });
    assert.match(html, />\s*#\s*<\/span>/);
});

test('renders extension-supplied detail chips inline with the requested tone', () => {
    const html = render({
        customers: [{
            code: 'CUST-1',
            description: 'Charlie',
            details: [{label: 'balance', value: '125.00', tone: 'warning'}],
        }],
        loading: false,
    });
    assert.match(html, /Balance/);
    assert.match(html, /125\.00/);
    assert.match(html, /text-amber-700/);
});

test('list rows follow the ProductFilter card pattern: rounded-lg border bg-white px-3 py-2 min-h-14', () => {
    const html = render({
        customers: [
            {code: 'CUST-1', description: 'Alice'},
            {code: 'CUST-2', description: 'Bob'},
        ],
        loading: false,
    });
    const rowSegments = html.match(/class="add-customer-btn[^"]*"/g) || [];
    assert.strictEqual(rowSegments.length, 2);
    assert.ok(rowSegments.every(s => s.includes('rounded-lg border border-slate-200 bg-white')),
        'every row should match the POS card-row utility bundle');
    assert.ok(rowSegments.every(s => s.includes('hover:border-blue-300')),
        'every row should announce blue border on hover (POS convention)');
    assert.ok(rowSegments.every(s => s.includes('active:bg-slate-100/60')),
        'every row should have a press feedback on click');
    assert.ok(rowSegments.every(s => s.includes('focus-within:ring-blue-400')),
        'every row should show a focus ring for keyboard users');
    assert.strictEqual(countMatches(html, 'fa-check'), 0, 'no check icon anywhere in the list');
    assert.doesNotMatch(html, /rounded-full/, 'POS chooser tiles use rounded-xl, not rounded-full');
    assert.doesNotMatch(html, /border-b border-slate-100/, 'no internal separator — rows have their own border');
});

test('no selected-row highlighting — aria-current, border-blue-500, ring-blue-500, bg-blue-50 never appear', () => {
    const html = render({
        customers: [
            {code: 'CUST-A', description: 'Alice'},
            {code: 'CUST-B', description: 'Bob', details: [{label: 'tarifa', value: '500.00', tone: 'success'}]},
            {code: 'CUST-C', description: 'Carla'},
        ],
        loading: false,
        activeCode: 'CUST-B',
    });
    assert.strictEqual(countMatches(html, 'aria-current="true"'), 0,
        'no aria-current="true" attribute');
    assert.strictEqual(countMatches(html, 'border-blue-500'), 0,
        'no selected-border style');
    assert.strictEqual(countMatches(html, 'ring-blue-500'), 0,
        'no selected-ring style');
    assert.strictEqual(countMatches(html, /(?<![\w-])bg-blue-50(?![a-z0-9-])/.source), 0,
        'no selected-bg style');
    const rowSegments = html.match(/class="add-customer-btn[^"]*"/g) || [];
    assert.strictEqual(rowSegments.length, 3);
    assert.ok(rowSegments.every(s => !s.includes('bg-blue-50')),
        'no row should ever carry the bg-blue-50 selected background');
});

test('avatar tile matches Cart/Header selectBtn: h-8 w-8 rounded-xl ring-1', () => {
    const html = render({customers: [{code: 'C1', description: 'Alpha'}], loading: false});
    assert.match(html, /flex h-8 w-8[^"]*rounded-xl[^"]*bg-white[^"]*ring-1[^"]*ring-slate-200/);
});

test('list container matches ProductFilter: gap-2 with no outer rounded border', () => {
    const twigContainerMatch = twigSource.match(/<div class="p-3 sm:p-4">\s*<div id="customer:list:view"[^>]*>/);
    assert.ok(twigContainerMatch);
    const containerTag = twigContainerMatch[0];
    assert.match(containerTag, /flex flex-col gap-2/);
    assert.doesNotMatch(containerTag, /rounded-xl border border-slate-200/);
});

test('does not throw when activeCode is missing from the data object', () => {
    const html = render({customers: [], loading: false, activeCode: 'CUST-1'});
    assert.match(html, /no-results/);
});

test('does not throw with a completely empty data object', () => {
    const html = render({});
    assert.match(html, /fa-user-slash|no-results/);
});

test('simulated real flow: skeleton → results → no clobbering on loading flag reset', () => {
    const template = resolveTwig(rawTemplate);

    const eta = new Eta({useWith: true});
    const customers = [
        {code: '247', description: 'MAESTRO NI'},
        {code: '81', description: 'N2S MEDIOS S.A DE C.U'},
    ];

    const skeletonHtml = eta.renderString(template, {customers: [], loading: true});
    assert.match(skeletonHtml, /aria-busy="true"/);
    assert.doesNotMatch(skeletonHtml, /add-customer-btn/);

    const resultsHtml = eta.renderString(template, {customers, loading: false});
    assert.match(resultsHtml, /data-code="247"/);
    assert.match(resultsHtml, /data-code="81"/);
    assert.match(resultsHtml, />\s*M\s*<\/span>/);

    const loaderOffHtml = eta.renderString(template, {customers: [], loading: false});
    assert.doesNotMatch(loaderOffHtml, /aria-busy="true"/);
    assert.match(loaderOffHtml, /fa-user-slash/);
});
