import assert from 'node:assert/strict';
import {test} from 'node:test';
import {readFileSync} from 'node:fs';
import {fileURLToPath} from 'node:url';
import {dirname, resolve as resolvePath} from 'node:path';

const __dirname = dirname(fileURLToPath(import.meta.url));
const twigPath = resolvePath(__dirname, '..', 'View', 'Modal', 'POS', 'AgentSelect.html.twig');
const twigSource = readFileSync(twigPath, 'utf8');

test('modal header mirrors the customer-search layout (icon tile, title, subtitle, close)', () => {
    assert.match(twigSource, /<div id="agent:select:modal" class="modal hidden">/);
    assert.match(twigSource, /<div class="modal-header flex-col items-stretch gap-3 p-4">/);
    assert.match(twigSource, /flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600/);
    assert.match(twigSource, /fa-solid fa-user-tie/);
    assert.match(twigSource, /<h2 class="text-xl font-semibold text-slate-900">\{\{ trans\('agent'\) \}\}<\/h2>/);
    assert.match(twigSource, /<p class="truncate text-sm text-slate-500">\{\{ trans\('select-agent'\) \}\}<\/p>/);
    assert.match(twigSource, /btn btn-icon btn-naked ml-auto/);
    assert.match(twigSource, /data-toggle="modal" data-target="agent:select:modal"/);
});

test('header has a search input like the customer modal', () => {
    assert.match(twigSource, /<label class="input-icon text-gray-400">/);
    assert.match(twigSource, /<input type="search" id="agentSearchBox" placeholder="\{\{ trans\('search'\) \}\}">/);
});

test('body uses the gap-2 flex-col list container (no outer rounded border, matches ProductFilter + customer modal)', () => {
    assert.match(twigSource, /<div class="p-3 sm:p-4">/);
    assert.match(twigSource, /<div id="agentSelectList" class="flex flex-col gap-2" role="list" aria-live="polite">/);
    assert.doesNotMatch(twigSource, /<table class="w-full">/);
});

test('each agent row uses the modern card-row utility bundle (matches customer modal)', () => {
    assert.match(twigSource, /class="add-agent-btn group pointer-events-auto flex w-full items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2 min-h-14 text-left transition focus:outline-none focus:ring-2 focus-within:ring-blue-400 hover:border-blue-300 hover:bg-slate-50 active:bg-slate-100\/60"/);
    assert.match(twigSource, /data-action="ui:cart:agent:set"/);
    assert.match(twigSource, /data-agent-row/);
    assert.doesNotMatch(twigSource, /<tr class="bg-slate-50 cursor-pointer hover:bg-blue-500 hover:text-white">/);
});

test('agent rows have an initials avatar matching the customer selectBtn pattern', () => {
    assert.match(twigSource, /<span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white text-xs font-bold uppercase text-slate-700 ring-1 ring-slate-200 transition group-hover:bg-slate-100"/);
    assert.match(twigSource, /\{\{ initial \}\}/);
});

test('avatar falls back to # when agent name is blank', () => {
    assert.match(twigSource, /\?: '#'/);
});

test('empty state when no agents are configured (uses no-agents-available translation)', () => {
    assert.match(twigSource, /rounded-lg border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-sm text-slate-400/);
    assert.match(twigSource, /fa-user-slash/);
    assert.match(twigSource, /\{\{ trans\('no-results'\) \}\}/);
    assert.match(twigSource, /\{\{ trans\('no-agents-available'\) \}\}/);
});

test('client-side search filter wired via inline script (parity with customer search pattern)', () => {
    assert.match(twigSource, /<script>[\s\S]*?document\.getElementById\('agentSearchBox'\)[\s\S]*?<\/script>/);
    assert.match(twigSource, /box\.addEventListener\('input'/);
    assert.match(twigSource, /data-agent-row/);
    assert.match(twigSource, /document\.getElementById\('agentNoFilterMatch'\)/);
});

test('cursor-pointer select-none from CSS layer is preserved (role=listitem)', () => {
    assert.match(twigSource, /role="listitem"/);
});

test('modal footer preserves the existing slide hint', () => {
    assert.match(twigSource, /<div class="modal-footer">/);
    assert.match(twigSource, /\{\{ trans\('slide-to-see-more-items'\) \}\}/);
});

test('no selected-row highlighting carried over from customer modal rework', () => {
    assert.doesNotMatch(twigSource, /aria-current="true"/);
    assert.doesNotMatch(twigSource, /border-blue-500/);
    assert.doesNotMatch(twigSource, /ring-blue-500/);
    assert.doesNotMatch(twigSource, /\bbg-blue-50\b/);
    assert.doesNotMatch(twigSource, /fa-check/);
});
