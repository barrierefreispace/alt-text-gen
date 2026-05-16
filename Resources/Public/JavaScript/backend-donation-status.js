import AjaxRequest from '@typo3/core/ajax/ajax-request.js';

const REFRESH_MS = 60_000;
const ROOT_ID = 'alt-text-gen-donation-status';

function currencySymbol(currency) {
  const c = (currency || '').toLowerCase();
  if (c === 'eur') return '€';
  if (c === 'usd') return '$';
  if (c === 'gbp') return '£';
  return c.toUpperCase() ? `${c.toUpperCase()} ` : '';
}

async function fetchDonation() {
  const url = TYPO3?.settings?.ajaxUrls?.barrierefrei_space_donation_status;
  if (!url) return null;
  const response = await new AjaxRequest(url).get();
  return await response.resolve();
}

function findTargetContainer() {
  const candidates = [
    document.querySelector('.scaffold-modulemenu-footer'),
    document.querySelector('.scaffold-modulemenu'),
    document.querySelector('#typo3-navigation'),
  ].filter(Boolean);
  return candidates[0] || null;
}

function ensureRoot(container) {
  let el = document.getElementById(ROOT_ID);
  if (el) return el;
  el = document.createElement('div');
  el.id = ROOT_ID;
  el.style.padding = '8px 10px';
  el.style.fontSize = '12px';
  el.style.opacity = '0.9';
  el.style.whiteSpace = 'pre-line';
  container.appendChild(el);
  return el;
}

function render(rootEl, payload) {
  if (!payload || typeof payload !== 'object') {
    rootEl.textContent = 'Donated: —';
    return;
  }
  const totalAmount = payload.donated_amount;
  const totalCents = payload.donated_cents;
  const periodAmount = payload.donated_amount_period;
  const periodCents = payload.donated_cents_period;
  const currency = payload.currency;
  const formattedTotal =
    typeof totalAmount === 'number'
      ? `${currencySymbol(currency)}${totalAmount.toFixed(2)}`
      : typeof totalCents === 'number'
        ? `${currencySymbol(currency)}${(totalCents / 100).toFixed(2)}`
        : '—';
  const formattedPeriod =
    typeof periodAmount === 'number'
      ? `${currencySymbol(currency)}${periodAmount.toFixed(2)}`
      : typeof periodCents === 'number'
        ? `${currencySymbol(currency)}${(periodCents / 100).toFixed(2)}`
        : '—';
  rootEl.textContent = `Donated this period: ${formattedPeriod}\nTotal donated: ${formattedTotal}`;
}

async function tick(rootEl) {
  try {
    const payload = await fetchDonation();
    render(rootEl, payload);
  } catch {
    render(rootEl, null);
  }
}

function start() {
  const container = findTargetContainer();
  if (!container) return;
  const rootEl = ensureRoot(container);
  tick(rootEl);
  window.setInterval(() => tick(rootEl), REFRESH_MS);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', start);
} else {
  start();
}
