import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import Notification from '@typo3/backend/notification.js';

const SELECTOR_BUTTON = '.t3js-alttextgen';
const LABEL_SPAN_CLASS = 'alt-text-gen-label';
const STYLE_SELECT_CLASS = 'alt-text-gen-style';
const CONTROL_CONTAINER_CLASS = 'alt-text-gen-control-container';
const CONTROL_MOUNT_CLASS = 'alt-text-gen-control-mount';
const CONTROL_ROW1_CLASS = 'alt-text-gen-row1';
const CONTROL_ROW2_CLASS = 'alt-text-gen-row2';
const SEO_TOGGLE_CLASS = 'alt-text-gen-seo-toggle';
const SEO_TOGGLE_LABEL_CLASS = 'alt-text-gen-seo-toggle-label';
const SEO_INPUT_CLASS = 'alt-text-gen-seo-input';
const GLOBAL_STATE_KEY = '__altTextGenState';
const DEFAULT_STYLE = 'formal';
const MAX_SEO_KEYWORDS = 6;
const SUPPORTED_LOCALES = new Set(['en', 'de']);
const I18N = {
  en: {
    'app.title': 'ALT Text Generator',
    'label.generate': 'Generate',
    'label.generating': 'Generating…',
    'label.saveFirst': 'Save first before generating',
    'error.missingRoute': 'Missing backend route (barrierefrei_space_generate)',
    'error.invalidResponse': 'Invalid server response',
    'error.generationFailed': 'ALT generation failed',
    'error.emptyAlt': 'Empty ALT text returned',
    'error.cannotFindInput': 'Cannot find ALT input',
    'error.reason': 'Reason',
    'error.hint': 'Hint',
    'hint.freeQuotaExhausted': 'Free quota exhausted, please subscribe in Subscription Center and retry.',
    'hint.siteMismatch': 'Site and license binding mismatch, please verify Site URL and License in Subscription Center.',
    'hint.quotaExceeded': 'Plan quota exceeded, upgrade your plan, wait for next billing period, or buy a one-time top-up.',
    'hint.licenseExpired': 'License expired, please renew subscription and retry.',
    'hint.licenseNotActive': 'License is not active yet, complete checkout and wait for sync.',
    'hint.invalidLicense': 'Invalid License Key, update extension configuration and retry.',
    'hint.modelError': 'Generation service is temporarily unavailable, retry later or contact admin.',
    'hint.serverUnavailable': 'Server temporarily unavailable, please retry later.',
    'hint.generic': 'Please review details and retry.',
    'seo.label': 'SEO Keywords',
    'seo.title': 'Custom SEO keywords',
    'seo.placeholder': 'SEO keywords (comma-separated, max 6)',
    'style.selector': 'ALT text style',
    'style.formal': '👔 Formal',
    'style.friendly': '🙂 Friendly',
    'style.casual': '😎 Casual',
    'style.professional': '💼 Professional',
    'style.diplomatic': '🤝 Diplomatic',
    'style.confident': '💪 Confident',
    'style.primary_school': '🧒 Primary School',
    'style.middle_school': '📘 Middle School',
    'style.high_school': '📗 High School',
    'style.academic': '🎓 Academic',
    'style.simplified': '✂️ Simplified',
    'style.vivid': '🎨 Vivid',
    'style.empathetic': '🫶 Empathetic',
    'style.luxury': '💎 Luxury',
    'style.engaging': '🔥 Engaging',
    'style.direct': '➡️ Direct',
    'style.persuasive': '🎯 Persuasive',
    'style.minimalist': '🧩 Minimalist',
    'style.storytelling': '📖 Storytelling',
    'style.technical': '🛠️ Technical',
    'style.brand_safe': '🛡️ Brand-safe',
  },
  de: {
    'app.title': 'ALT-Text-Generator',
    'label.generate': 'Generieren',
    'label.generating': 'Wird generiert…',
    'label.saveFirst': 'Vor dem Generieren zuerst speichern',
    'error.missingRoute': 'Backend-Route fehlt (barrierefrei_space_generate)',
    'error.invalidResponse': 'Ungültige Serverantwort',
    'error.generationFailed': 'ALT-Generierung fehlgeschlagen',
    'error.emptyAlt': 'Leerer ALT-Text wurde zurückgegeben',
    'error.cannotFindInput': 'ALT-Eingabefeld wurde nicht gefunden',
    'error.reason': 'Ursache',
    'error.hint': 'Hinweis',
    'hint.freeQuotaExhausted': 'Freikontingent aufgebraucht. Bitte im Abonnement-Center abonnieren und erneut versuchen.',
    'hint.siteMismatch': 'Site- und Lizenzbindung stimmen nicht überein. Bitte Site-URL und Lizenz im Abonnement-Center prüfen.',
    'hint.quotaExceeded': 'Plan-Kontingent überschritten. Bitte Plan upgraden, bis zum nächsten Abrechnungszeitraum warten oder ein einmaliges Top-up kaufen.',
    'hint.licenseExpired': 'Lizenz abgelaufen. Bitte Abonnement erneuern und erneut versuchen.',
    'hint.licenseNotActive': 'Lizenz ist noch nicht aktiv. Bitte Checkout abschließen und auf Synchronisierung warten.',
    'hint.invalidLicense': 'Ungültiger Lizenzschlüssel. Bitte Erweiterungskonfiguration aktualisieren und erneut versuchen.',
    'hint.modelError': 'Generierungsdienst vorübergehend nicht verfügbar. Später erneut versuchen oder Admin kontaktieren.',
    'hint.serverUnavailable': 'Server vorübergehend nicht verfügbar. Bitte später erneut versuchen.',
    'hint.generic': 'Bitte Details prüfen und erneut versuchen.',
    'seo.label': 'SEO-Schlüsselwörter',
    'seo.title': 'Benutzerdefinierte SEO-Schlüsselwörter',
    'seo.placeholder': 'SEO-Schlüsselwörter (kommagetrennt, max. 6)',
    'style.selector': 'ALT-Text-Stil',
    'style.formal': '👔 Formal',
    'style.friendly': '🙂 Freundlich',
    'style.casual': '😎 Locker',
    'style.professional': '💼 Professionell',
    'style.diplomatic': '🤝 Diplomatisch',
    'style.confident': '💪 Selbstbewusst',
    'style.primary_school': '🧒 Grundschule',
    'style.middle_school': '📘 Mittelstufe',
    'style.high_school': '📗 Oberstufe',
    'style.academic': '🎓 Akademisch',
    'style.simplified': '✂️ Vereinfacht',
    'style.vivid': '🎨 Lebendig',
    'style.empathetic': '🫶 Empathisch',
    'style.luxury': '💎 Exklusiv',
    'style.engaging': '🔥 Fesselnd',
    'style.direct': '➡️ Direkt',
    'style.persuasive': '🎯 Uberzeugend',
    'style.minimalist': '🧩 Minimalistisch',
    'style.storytelling': '📖 Erzahlerisch',
    'style.technical': '🛠️ Technisch',
    'style.brand_safe': '🛡️ Markensicher',
  },
};
const STYLE_VALUES = [
  'formal',
  'friendly',
  'casual',
  'professional',
  'diplomatic',
  'confident',
  'primary_school',
  'middle_school',
  'high_school',
  'academic',
  'simplified',
  'vivid',
  'empathetic',
  'luxury',
  'engaging',
  'direct',
  'persuasive',
  'minimalist',
  'storytelling',
  'technical',
  'brand_safe',
];
const STYLE_VALUE_SET = new Set(STYLE_VALUES);

function resolveLocale() {
  const fromDom = String(document.documentElement?.lang || '').toLowerCase();
  const fromTypo3 = String(TYPO3?.lang || '').toLowerCase();
  const candidate = fromDom || fromTypo3;
  const normalized = candidate.startsWith('de') ? 'de' : 'en';
  return SUPPORTED_LOCALES.has(normalized) ? normalized : 'en';
}

function t(key) {
  const locale = resolveLocale();
  const byLocale = I18N[locale] || I18N.en;
  return byLocale[key] || I18N.en[key] || key;
}

function getStyleLabel(styleValue) {
  return t(`style.${styleValue}`);
}

const LABEL_SAVE_FIRST = t('label.saveFirst');

function getGlobalState() {
  const root = window;
  if (!root[GLOBAL_STATE_KEY]) {
    root[GLOBAL_STATE_KEY] = {
      initialized: false,
      observer: null,
      bootstrapScheduled: false,
      pendingSeoKeywords: '',
    };
  }
  return root[GLOBAL_STATE_KEY];
}

/**
 * Function Resolve root container for ALT generation controls to avoid duplicate rendering after two-row layout.
 */
function getControlContainer(buttonEl) {
  const byClass = buttonEl.closest(`.${CONTROL_CONTAINER_CLASS}`);
  if (byClass) {
    return byClass;
  }
  return buttonEl.parentElement;
}

/**
 * Function Write generated ALT text back to the current field and trigger FormEngine change detection.
 *
 *     inputEl (HTMLInputElement|HTMLTextAreaElement): ALT  / ALT input control.
 *     altText (string):  / Text to write.
 *     void
 */
function writeAltText(inputEl, altText) {
  inputEl.value = altText;
  const inputName = (inputEl.getAttribute('data-formengine-input-name') || '').trim();
  if (inputName !== '') {
    const wrapper = inputEl.closest('.form-control-clearable-wrapper') || inputEl.parentElement;
    if (wrapper) {
      const hidden = wrapper.querySelector(`input[type="hidden"][name="${inputName.replace(/"/g, '\\"')}"]`);
      if (hidden) {
        hidden.value = altText;
        hidden.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }
  }
  inputEl.dispatchEvent(new Event('input', { bubbles: true }));
  inputEl.dispatchEvent(new Event('change', { bubbles: true }));
}

/**
 * Function Locate the ALT input associated with the button without depending on a fixed DOM structure.
 *
 *     buttonEl (HTMLButtonElement):  / Clicked button.
 *     HTMLInputElement|HTMLTextAreaElement|null:  / The corresponding input element.
 */
function findAltInput(buttonEl) {
  const formElId = (buttonEl.dataset.formelId || '').trim();
  if (formElId !== '') {
    const byId = document.getElementById(formElId);
    if (byId) {
      return byId;
    }
  }

  const formElName = (buttonEl.dataset.formelName || '').trim();
  if (formElName !== '') {
    const nameEscaped = typeof CSS !== 'undefined' && typeof CSS.escape === 'function' ? CSS.escape(formElName) : formElName.replace(/"/g, '\\"');
    const byDataName = document.querySelector(`[data-formengine-input-name="${nameEscaped}"]`);
    if (byDataName) return byDataName;

    const byName = document.querySelector(`[name="${nameEscaped}"]`);
    if (byName) {
      const wrapper = byName.closest('.form-control-clearable-wrapper') || byName.parentElement;
      if (wrapper) {
        const visible = wrapper.querySelector(`[data-formengine-input-name="${nameEscaped}"]`);
        if (visible) return visible;
      }
      return byName;
    }
  }

  const containers = [
    buttonEl.closest('.formengine-field-item'),
    buttonEl.closest('.form-group'),
    buttonEl.closest('.formengine-field-control'),
    buttonEl.parentElement,
  ].filter(Boolean);
  for (const container of containers) {
    const input = container.querySelector('input[type="text"], textarea');
    if (input) return input;
  }
  return null;
}

/**
 * Function Call backend Ajax route to generate ALT text; using ajaxUrls avoids hard-coded paths and stays compatible with route changes.
 *
 *     fileReferenceUid (number): sys_file_reference.uid / sys_file_reference.uid.
 *     Promise<{alt_text: string}>:  / Backend payload.
 *     Error:  / Request failed or invalid response.
 */
async function requestAltText(fileReferenceUid, style) {
  const url = TYPO3?.settings?.ajaxUrls?.barrierefrei_space_generate;
  if (!url) {
    throw new Error(t('error.missingRoute'));
  }
  const data = new FormData();
  data.append('fileReferenceUid', String(fileReferenceUid));
  data.append('style', String(style || DEFAULT_STYLE));
  data.append('seoKeywords', readSelectedSeoKeywordsFromPayload(style));
  const response = await fetch(url, {
    method: 'POST',
    body: data,
    credentials: 'same-origin',
  });

  let payload = null;
  try {
    payload = await response.json();
  } catch (e) {
    payload = null;
  }

  if (!response.ok) {
    const detailedMessage = buildSafeDetailedErrorMessage(payload, response.status);
    throw new Error(detailedMessage);
  }
  if (!payload || typeof payload !== 'object') {
    throw new Error(t('error.invalidResponse'));
  }
  return payload;
}

/**
 * Function Apply minimal redaction on error text to avoid exposing keys/tokens/secrets in editor UI.
 */
function sanitizeErrorMessage(rawMessage) {
  const text = String(rawMessage || '').trim();
  if (text === '') {
    return '';
  }
  return text
    .replace(/(sk_(live|test)_[A-Za-z0-9]+)/g, '[REDACTED_STRIPE_KEY]')
    .replace(/(pk_(live|test)_[A-Za-z0-9]+)/g, '[REDACTED_STRIPE_PUBLISHABLE_KEY]')
    .replace(/(whsec_[A-Za-z0-9]+)/g, '[REDACTED_WEBHOOK_SECRET]')
    .replace(/(Bearer\s+)[A-Za-z0-9._-]+/gi, '$1[REDACTED_TOKEN]')
    .replace(/(api[_-]?key\s*[:=]\s*)[^\s,;]+/gi, '$1[REDACTED]')
    .replace(/(token\s*[:=]\s*)[^\s,;]+/gi, '$1[REDACTED]')
    .replace(/\bhttps?:\/\/[^\s`'"]+/gi, '[REDACTED_URL]');
}

/**
 * Function Extract editor-friendly business reason from backend/gateway errors and remove technical noise like POST/URL/response.
 */
function extractUserFacingReason(rawMessage) {
  const safe = sanitizeErrorMessage(rawMessage);
  if (safe === '') {
    return '';
  }

  const jsonMatch = safe.match(/\{[\s\S]*\}$/);
  if (jsonMatch) {
    try {
      const parsed = JSON.parse(jsonMatch[0]);
      const detail = String(parsed.detail || parsed.message || '').trim();
      if (detail !== '') {
        return detail;
      }
    } catch (e) {
      // ignore JSON parse failure and fallback to text cleanup
    }
  }

  const textOnly = safe
    .replace(/client error:[\s\S]*?response:/gi, '')
    .replace(/server error:[\s\S]*?response:/gi, '')
    .replace(/`(get|post|put|delete|patch)[^`]*`/gi, '')
    .replace(/status:\s*\d{3}/gi, '')
    .replace(/\s+/g, ' ')
    .trim();

  const keywordFromWrappedError = textOnly.split(':').pop()?.trim() || '';
  const keywordLower = keywordFromWrappedError.toLowerCase();
  const hasBusinessKeyword =
    keywordLower.includes('quota exceeded')
    || keywordLower.includes('free quota exhausted')
    || keywordLower.includes('site mismatch')
    || keywordLower.includes('license expired')
    || keywordLower.includes('license not active')
    || keywordLower.includes('invalid license');
  if (hasBusinessKeyword) {
    return keywordFromWrappedError;
  }

  const hasTechnicalNoise = /(response|client error|server error|post |get |put |delete |patch |traceback|exception|runtimeerror|line \d+)/i.test(textOnly);
  if (hasTechnicalNoise) {
    return '';
  }
  return textOnly;
}

/**
 * Function Read standardized error_code from backend payload.
 */
function extractErrorCode(payload) {
  if (!payload || typeof payload !== 'object') return '';
  const top = String(payload.error_code || '').trim();
  if (top !== '') return top;
  const detail = payload.detail;
  if (detail && typeof detail === 'object') {
    return String(detail.error_code || '').trim();
  }
  return '';
}

/**
 * Function Classify errors strictly by backend-defined error_code contract.
 */
function mapErrorCodeToHint(errorCode, httpStatus) {
  switch (String(errorCode || '').trim().toUpperCase()) {
    case 'PLAN_QUOTA_EXCEEDED':
      return t('hint.quotaExceeded');
    case 'FREE_QUOTA_EXHAUSTED':
      return t('hint.freeQuotaExhausted');
    case 'SITE_MISMATCH':
      return t('hint.siteMismatch');
    case 'LICENSE_EXPIRED':
      return t('hint.licenseExpired');
    case 'LICENSE_NOT_ACTIVE':
      return t('hint.licenseNotActive');
    case 'INVALID_LICENSE':
      return t('hint.invalidLicense');
    case 'MODEL_UNAVAILABLE':
      return t('hint.modelError');
    case 'SERVER_UNAVAILABLE':
      return t('hint.serverUnavailable');
    default:
      if (httpStatus >= 500) return t('hint.serverUnavailable');
      return t('hint.generic');
  }
}

/**
 * Function Compose final error text with safe details and actionable hint instead of generic failure message.
 */
function buildSafeDetailedErrorMessage(payload, httpStatus) {
  const base = t('error.generationFailed');
  const errorCode = extractErrorCode(payload);
  const detailFromPayload = (() => {
    if (!payload || typeof payload !== 'object') return '';
    if (typeof payload.message === 'string' && payload.message.trim() !== '') return payload.message;
    if (typeof payload.detail === 'string' && payload.detail.trim() !== '') return payload.detail;
    if (payload.detail && typeof payload.detail === 'object') {
      return String(payload.detail.message || payload.detail.detail || '').trim();
    }
    if (typeof payload.error === 'string' && payload.error.trim() !== '') return payload.error;
    return '';
  })();
  const reason = extractUserFacingReason(detailFromPayload);
  const hint = mapErrorCodeToHint(errorCode, Number(httpStatus || 0));

  if (reason === '') {
    return `${base}\n${hint}`;
  }
  return `${base}\n${t('error.reason')}: ${reason}\n${t('error.hint')}: ${hint}`;
}

async function requestSavePreferences(fileReferenceUid, style, seoKeywords) {
  const url = TYPO3?.settings?.ajaxUrls?.barrierefrei_space_save_preferences;
  if (!url) {
    return;
  }
  const data = new FormData();
  data.append('fileReferenceUid', String(fileReferenceUid));
  data.append('style', String(style || DEFAULT_STYLE));
  data.append('seoKeywords', String(seoKeywords || ''));
  try {
    await new AjaxRequest(url).post(data);
  } catch (e) {
    // Why Preference save failures should not block main interaction (generation stays available).
  }
}

/**
 * Function Keep requestAltText call signature stable; seoKeywords is injected via transient global payload set at click time.
 */
function readSelectedSeoKeywordsFromPayload() {
  const state = getGlobalState();
  return String(state.pendingSeoKeywords || '');
}

function ensureLabelSpan(controlEl) {
  let span = controlEl.querySelector(`.${LABEL_SPAN_CLASS}`);
  if (span) return span;
  span = document.createElement('span');
  span.className = LABEL_SPAN_CLASS;
  span.textContent = controlEl.dataset.labelGenerate || t('label.generate');
  controlEl.appendChild(document.createTextNode(' '));
  controlEl.appendChild(span);
  return span;
}

function getButtonScopeKey(buttonEl) {
  const fileReferenceUid = Number.parseInt(buttonEl.dataset.filerefUid || '0', 10);
  if (Number.isFinite(fileReferenceUid) && fileReferenceUid > 0) {
    return `uid:${fileReferenceUid}`;
  }
  const formElName = (buttonEl.dataset.formelName || '').trim();
  if (formElName !== '') {
    return `name:${formElName}`;
  }
  return '';
}

function getStyleStorageKey(buttonEl) {
  const scope = getButtonScopeKey(buttonEl);
  return scope !== '' ? `alt-text-gen:style:${scope}` : '';
}

function getSeoKeywordsStorageKey(buttonEl) {
  const scope = getButtonScopeKey(buttonEl);
  return scope !== '' ? `alt-text-gen:seo-keywords:${scope}` : '';
}

/**
 * Function Read persisted style for current button; fallback to default when invalid.
 */
function readPersistedStyle(buttonEl) {
  const fromDataset = String(buttonEl.dataset.persistedStyle || '').trim().toLowerCase();
  if (STYLE_VALUE_SET.has(fromDataset)) {
    return fromDataset;
  }
  const key = getStyleStorageKey(buttonEl);
  if (key === '') {
    return DEFAULT_STYLE;
  }
  try {
    const stored = String(window.localStorage.getItem(key) || '').trim().toLowerCase();
    if (STYLE_VALUE_SET.has(stored)) {
      return stored;
    }
  } catch (e) {
    // Why localStorage can be unavailable in privacy modes; fallback avoids breaking generation flow.
  }
  return DEFAULT_STYLE;
}

function persistStyle(buttonEl, style) {
  const key = getStyleStorageKey(buttonEl);
  if (key === '' || !STYLE_VALUE_SET.has(style)) {
    return;
  }
  buttonEl.dataset.persistedStyle = style;
  try {
    window.localStorage.setItem(key, style);
  } catch (e) {
    // ignore write errors
  }
}

function normalizeSeoKeywords(rawKeywords) {
  const seen = new Set();
  const normalized = [];
  String(rawKeywords || '')
    .split(',')
    .map((item) => item.trim())
    .filter((item) => item !== '')
    .forEach((item) => {
      const key = item.toLowerCase();
      if (seen.has(key) || normalized.length >= MAX_SEO_KEYWORDS) {
        return;
      }
      seen.add(key);
      normalized.push(item);
    });
  return normalized.join(', ');
}

function readPersistedSeoToggle(buttonEl) {
  // Why Keep toggle off by default when no keywords exist; auto-enable when keywords are present.
  const keywords = readPersistedSeoKeywords(buttonEl);
  return keywords !== '';
}

function readPersistedSeoKeywords(buttonEl) {
  const fromDataset = normalizeSeoKeywords(buttonEl.dataset.persistedSeoKeywords || '');
  if (fromDataset !== '') {
    return fromDataset;
  }
  const key = getSeoKeywordsStorageKey(buttonEl);
  if (key === '') return '';
  try {
    return normalizeSeoKeywords(window.localStorage.getItem(key) || '');
  } catch (e) {
    return '';
  }
}

function persistSeoState(buttonEl, enabled, keywords) {
  const keywordsKey = getSeoKeywordsStorageKey(buttonEl);
  if (keywordsKey === '') return;
  const normalizedKeywords = enabled ? normalizeSeoKeywords(keywords) : '';
  buttonEl.dataset.persistedSeoKeywords = normalizedKeywords;
  try {
    window.localStorage.setItem(keywordsKey, normalizedKeywords);
  } catch (e) {
    // ignore write errors
  }
}

/**
 * Function Inject SEO keyword toggle and input for each button with persistence and normalization.
 */
function ensureSeoControls(buttonEl) {
  const controlContainer = getControlContainer(buttonEl);
  if (!controlContainer) {
    return null;
  }

  let toggleEl = controlContainer.querySelector(`.${SEO_TOGGLE_CLASS}[data-for-alttextgen="1"]`);
  let toggleLabelEl = controlContainer.querySelector(`.${SEO_TOGGLE_LABEL_CLASS}[data-for-alttextgen="1"]`);
  let inputEl = controlContainer.querySelector(`.${SEO_INPUT_CLASS}[data-for-alttextgen="1"]`);
  if (toggleEl && toggleLabelEl && inputEl) {
    const enabled = readPersistedSeoToggle(buttonEl);
    const keywords = readPersistedSeoKeywords(buttonEl);
    toggleEl.checked = enabled;
    inputEl.value = keywords;
    inputEl.disabled = !enabled;
    inputEl.placeholder = t('seo.placeholder');
    inputEl.style.display = enabled ? 'inline-block' : 'none';
    return { toggleEl, toggleLabelEl, inputEl };
  }

  // Why Support legacy DOM (checkbox/input without label) by cleaning old nodes before rebuilding.
  if (toggleEl) toggleEl.remove();
  if (toggleLabelEl) toggleLabelEl.remove();
  if (inputEl) inputEl.remove();

  toggleEl = document.createElement('input');
  toggleEl.type = 'checkbox';
  toggleEl.className = SEO_TOGGLE_CLASS;
  toggleEl.setAttribute('data-for-alttextgen', '1');
  toggleEl.setAttribute('title', t('seo.title'));
  toggleEl.style.verticalAlign = 'middle';
  toggleEl.style.flexShrink = '0';
  toggleEl.style.marginRight = '0.25rem';

  toggleLabelEl = document.createElement('span');
  toggleLabelEl.className = SEO_TOGGLE_LABEL_CLASS;
  toggleLabelEl.setAttribute('data-for-alttextgen', '1');
  toggleLabelEl.textContent = t('seo.label');
  toggleLabelEl.style.display = 'inline-block';
  toggleLabelEl.style.verticalAlign = 'middle';
  toggleLabelEl.style.marginRight = '0.5rem';
  toggleLabelEl.style.whiteSpace = 'nowrap';

  inputEl = document.createElement('input');
  inputEl.type = 'text';
  inputEl.className = `${SEO_INPUT_CLASS} form-control form-control-sm`;
  inputEl.setAttribute('data-for-alttextgen', '1');
  inputEl.setAttribute('aria-label', t('seo.title'));
  inputEl.style.display = 'none';
  inputEl.style.minWidth = '240px';
  inputEl.style.maxWidth = '380px';
  inputEl.style.width = '320px';
  inputEl.style.flexBasis = 'auto';
  inputEl.style.marginRight = '0.5rem';
  inputEl.style.verticalAlign = 'middle';
  inputEl.placeholder = t('seo.placeholder');

  const enabled = readPersistedSeoToggle(buttonEl);
  const keywords = readPersistedSeoKeywords(buttonEl);
  toggleEl.checked = enabled;
  inputEl.value = keywords;
  inputEl.disabled = !enabled;
  inputEl.style.display = enabled ? 'inline-block' : 'none';

  toggleEl.addEventListener('change', () => {
    inputEl.disabled = !toggleEl.checked;
    inputEl.style.display = toggleEl.checked ? 'inline-block' : 'none';
    if (!toggleEl.checked) {
      inputEl.value = '';
    }
    persistSeoState(buttonEl, toggleEl.checked, inputEl.value);
    const style = readSelectedStyle(buttonEl);
    const seoKeywords = readSelectedSeoKeywords(buttonEl);
    const fileReferenceUid = Number.parseInt(buttonEl.dataset.filerefUid || '0', 10);
    if (Number.isFinite(fileReferenceUid) && fileReferenceUid > 0) {
      requestSavePreferences(fileReferenceUid, style, seoKeywords);
    }
  });
  inputEl.addEventListener('blur', () => {
    inputEl.value = normalizeSeoKeywords(inputEl.value);
    persistSeoState(buttonEl, toggleEl.checked, inputEl.value);
    const style = readSelectedStyle(buttonEl);
    const seoKeywords = readSelectedSeoKeywords(buttonEl);
    const fileReferenceUid = Number.parseInt(buttonEl.dataset.filerefUid || '0', 10);
    if (Number.isFinite(fileReferenceUid) && fileReferenceUid > 0) {
      requestSavePreferences(fileReferenceUid, style, seoKeywords);
    }
  });

  controlContainer.insertBefore(inputEl, buttonEl);
  controlContainer.insertBefore(toggleLabelEl, inputEl);
  controlContainer.insertBefore(toggleEl, toggleLabelEl);
  return { toggleEl, toggleLabelEl, inputEl };
}

/**
 * Function Normalize control-area layout to avoid crowding when text field, SEO controls, style select and button appear together.
 */
function ensureControlContainerLayout(buttonEl) {
  const controlContainer = getControlContainer(buttonEl);
  if (!controlContainer) {
    return;
  }
  const inputEl = findAltInput(buttonEl);
  if (inputEl) {
    const mountHost =
      inputEl.closest('.formengine-field-item') ||
      inputEl.closest('.form-group') ||
      inputEl.parentElement;
    if (mountHost) {
      let mount = mountHost.querySelector(`.${CONTROL_MOUNT_CLASS}[data-for-alttextgen="1"]`);
      if (!mount) {
        mount = document.createElement('div');
        mount.className = CONTROL_MOUNT_CLASS;
        mount.setAttribute('data-for-alttextgen', '1');
        mount.style.marginTop = '0.5rem';
        mount.style.width = '100%';
        const inputWrapper =
          inputEl.closest('.form-control-wrap') ||
          inputEl.closest('.form-control-clearable-wrapper') ||
          inputEl;
        if (inputWrapper.parentElement === mountHost) {
          inputWrapper.insertAdjacentElement('afterend', mount);
        } else {
          mountHost.appendChild(mount);
        }
      }
      if (controlContainer.parentElement !== mount) {
        mount.appendChild(controlContainer);
      }
    }
  }

  controlContainer.classList.add(CONTROL_CONTAINER_CLASS);
  controlContainer.style.display = 'flex';
  controlContainer.style.flexWrap = 'wrap';
  controlContainer.style.alignItems = 'center';
  controlContainer.style.gap = '0.5rem';
  controlContainer.style.width = '100%';
  controlContainer.style.maxWidth = '100%';
  buttonEl.style.flexShrink = '0';

  let row1 = controlContainer.querySelector(`.${CONTROL_ROW1_CLASS}[data-for-alttextgen="1"]`);
  if (!row1) {
    row1 = document.createElement('div');
    row1.className = CONTROL_ROW1_CLASS;
    row1.setAttribute('data-for-alttextgen', '1');
    row1.style.display = 'flex';
    row1.style.alignItems = 'center';
    row1.style.flexWrap = 'wrap';
    row1.style.gap = '0.5rem';
    row1.style.width = '100%';
    controlContainer.appendChild(row1);
  }

  let row2 = controlContainer.querySelector(`.${CONTROL_ROW2_CLASS}[data-for-alttextgen="1"]`);
  if (!row2) {
    row2 = document.createElement('div');
    row2.className = CONTROL_ROW2_CLASS;
    row2.setAttribute('data-for-alttextgen', '1');
    row2.style.display = 'flex';
    row2.style.alignItems = 'center';
    row2.style.flexWrap = 'wrap';
    row2.style.gap = '0.5rem';
    row2.style.width = '100%';
    controlContainer.appendChild(row2);
  }

  const toggles = Array.from(controlContainer.querySelectorAll(`.${SEO_TOGGLE_CLASS}[data-for-alttextgen="1"]`));
  const toggleLabels = Array.from(controlContainer.querySelectorAll(`.${SEO_TOGGLE_LABEL_CLASS}[data-for-alttextgen="1"]`));
  const styles = Array.from(controlContainer.querySelectorAll(`.${STYLE_SELECT_CLASS}[data-for-alttextgen="1"]`));
  const seoInputs = Array.from(controlContainer.querySelectorAll(`.${SEO_INPUT_CLASS}[data-for-alttextgen="1"]`));

  const toggleEl = toggles.shift() || null;
  const toggleLabelEl = toggleLabels.shift() || null;
  const styleEl = styles.shift() || null;
  const seoInputEl = seoInputs.shift() || null;

  // Why Historical duplicate nodes make layout grow on every bootstrap; keep first instance and remove clones.
  toggles.forEach((node) => node.remove());
  toggleLabels.forEach((node) => node.remove());
  styles.forEach((node) => node.remove());
  seoInputs.forEach((node) => node.remove());

  if (styleEl && styleEl.parentElement !== row1) row1.appendChild(styleEl);
  if (toggleEl) {
    toggleEl.style.marginLeft = 'auto';
  }
  if (toggleEl && toggleEl.parentElement !== row1) row1.appendChild(toggleEl);
  if (toggleLabelEl && toggleLabelEl.parentElement !== row1) row1.appendChild(toggleLabelEl);
  if (seoInputEl && seoInputEl.parentElement !== row2) row2.appendChild(seoInputEl);
  if (buttonEl.parentElement !== row2) row2.appendChild(buttonEl);

  if (seoInputEl) {
    seoInputEl.style.flex = '1 1 320px';
    seoInputEl.style.maxWidth = '520px';
    seoInputEl.style.width = '100%';
  }
  buttonEl.style.flex = '0 0 auto';
}

/**
 * Function Create a tone/style selector for each generate button and reuse existing instance to avoid duplicates.
 *
 *   Editors can choose style before generation instead of relying on a hardcoded server strategy.
 */
function ensureStyleSelect(buttonEl) {
  const controlContainer = getControlContainer(buttonEl);
  if (!controlContainer) {
    return null;
  }
  let selectEl = controlContainer.querySelector(`.${STYLE_SELECT_CLASS}[data-for-alttextgen="1"]`);
  if (selectEl) {
    const persistedStyle = readPersistedStyle(buttonEl);
    if (STYLE_VALUE_SET.has(persistedStyle) && selectEl.value !== persistedStyle) {
      selectEl.value = persistedStyle;
    }
    return selectEl;
  }

  selectEl = document.createElement('select');
  selectEl.className = `${STYLE_SELECT_CLASS} form-select form-select-sm`;
  selectEl.setAttribute('data-for-alttextgen', '1');
  selectEl.setAttribute('aria-label', t('style.selector'));
  selectEl.setAttribute('title', t('style.selector'));
  selectEl.style.maxWidth = '240px';
  selectEl.style.minWidth = '180px';
  selectEl.style.display = 'inline-block';
  selectEl.style.verticalAlign = 'middle';
  selectEl.style.flexShrink = '0';

  STYLE_VALUES.forEach((value) => {
    const opt = document.createElement('option');
    opt.value = value;
    opt.textContent = getStyleLabel(value);
    if (value === DEFAULT_STYLE) {
      opt.selected = true;
    }
    selectEl.appendChild(opt);
  });

  const persistedStyle = readPersistedStyle(buttonEl);
  if (STYLE_VALUE_SET.has(persistedStyle)) {
    selectEl.value = persistedStyle;
  }

  selectEl.addEventListener('change', () => {
    const style = String(selectEl.value || '').trim().toLowerCase();
    persistStyle(buttonEl, style);
    const seoKeywords = readSelectedSeoKeywords(buttonEl);
    const fileReferenceUid = Number.parseInt(buttonEl.dataset.filerefUid || '0', 10);
    if (Number.isFinite(fileReferenceUid) && fileReferenceUid > 0) {
      requestSavePreferences(fileReferenceUid, style, seoKeywords);
    }
  });

  controlContainer.insertBefore(selectEl, buttonEl);
  return selectEl;
}

function readSelectedStyle(buttonEl) {
  const controlContainer = getControlContainer(buttonEl);
  if (!controlContainer) {
    return DEFAULT_STYLE;
  }
  const selectEl = controlContainer.querySelector(`.${STYLE_SELECT_CLASS}[data-for-alttextgen="1"]`);
  if (!selectEl) {
    return DEFAULT_STYLE;
  }
  const style = String(selectEl.value || '').trim().toLowerCase();
  return style !== '' ? style : DEFAULT_STYLE;
}

function readSelectedSeoKeywords(buttonEl) {
  const controlContainer = getControlContainer(buttonEl);
  if (!controlContainer) {
    return '';
  }
  const toggleEl = controlContainer.querySelector(`.${SEO_TOGGLE_CLASS}[data-for-alttextgen="1"]`);
  const inputEl = controlContainer.querySelector(`.${SEO_INPUT_CLASS}[data-for-alttextgen="1"]`);
  if (!toggleEl || !inputEl || !toggleEl.checked) {
    return '';
  }
  const normalized = normalizeSeoKeywords(inputEl.value);
  inputEl.value = normalized;
  persistSeoState(buttonEl, true, normalized);
  return normalized;
}

/**
 * Function Normalize button availability based on fileReferenceUid.
 *
 *   For unsaved records uid=0, backend cannot resolve file reference; disabling early avoids invalid requests.
 */
function syncButtonAvailability(buttonEl) {
  ensureSeoControls(buttonEl);
  ensureStyleSelect(buttonEl);
  ensureControlContainerLayout(buttonEl);
  const labelSpan = ensureLabelSpan(buttonEl);
  const fileReferenceUid = Number.parseInt(buttonEl.dataset.filerefUid || '0', 10);
  const labelGenerate = buttonEl.dataset.labelGenerate || t('label.generate');
  const isUsable = Number.isFinite(fileReferenceUid) && fileReferenceUid > 0;

  if (!isUsable) {
    buttonEl.setAttribute('aria-disabled', 'true');
    buttonEl.classList.add('disabled');
    buttonEl.setAttribute('title', LABEL_SAVE_FIRST);
    labelSpan.textContent = LABEL_SAVE_FIRST;
    return;
  }

  if (buttonEl.getAttribute('aria-busy') !== 'true') {
    buttonEl.removeAttribute('aria-disabled');
    buttonEl.classList.remove('disabled');
    buttonEl.setAttribute('title', labelGenerate);
    labelSpan.textContent = labelGenerate;
  }
}

function bootstrapLabels() {
  document.querySelectorAll(SELECTOR_BUTTON).forEach((el) => syncButtonAvailability(el));
}

/**
 * Function Batch DOM scanning into next frame to avoid UI freeze under frequent mutation callbacks.
 */
function scheduleBootstrap() {
  const state = getGlobalState();
  if (state.bootstrapScheduled) {
    return;
  }
  state.bootstrapScheduled = true;
  window.requestAnimationFrame(() => {
    state.bootstrapScheduled = false;
    bootstrapLabels();
  });
}

function observeNewControls() {
  const state = getGlobalState();
  if (state.observer) {
    state.observer.disconnect();
  }

  const observer = new MutationObserver((mutations) => {
    for (const mutation of mutations) {
      for (const node of mutation.addedNodes) {
        if (!(node instanceof Element)) {
          continue;
        }
        if (node.matches(SELECTOR_BUTTON) || node.querySelector(SELECTOR_BUTTON)) {
          scheduleBootstrap();
          return;
        }
      }
    }
  });

  observer.observe(document.body, { childList: true, subtree: true });
  state.observer = observer;
}

async function handleButtonClick(event) {
  const button = event.target.closest(SELECTOR_BUTTON);
  if (!button) {
    return;
  }
  event.preventDefault();

  const inputEl = findAltInput(button);
  if (!inputEl) {
    Notification.error(t('app.title'), t('error.cannotFindInput'));
    return;
  }

  const fileReferenceUid = Number.parseInt(button.dataset.filerefUid || '0', 10);
  if (!Number.isFinite(fileReferenceUid) || fileReferenceUid <= 0) {
    syncButtonAvailability(button);
    Notification.info(t('app.title'), LABEL_SAVE_FIRST);
    return;
  }

  const labelGenerate = button.dataset.labelGenerate || t('label.generate');
  const labelGenerating = button.dataset.labelGenerating || t('label.generating');
  const labelSpan = ensureLabelSpan(button);

  const canDisable = Object.prototype.hasOwnProperty.call(button, 'disabled');
  if (canDisable) {
    button.disabled = true;
  } else {
    button.setAttribute('aria-disabled', 'true');
    button.classList.add('disabled');
  }
  button.setAttribute('aria-busy', 'true');
  button.setAttribute('title', labelGenerating);
  labelSpan.textContent = labelGenerating;

  try {
    const style = readSelectedStyle(button);
    const seoKeywords = readSelectedSeoKeywords(button);
    const state = getGlobalState();
    state.pendingSeoKeywords = seoKeywords;
    const payload = await requestAltText(fileReferenceUid, style);
    const altText = (payload?.alt_text || '').toString().trim();
    if (!altText) {
      throw new Error(t('error.emptyAlt'));
    }
    writeAltText(inputEl, altText);
  } catch (e) {
    const message = sanitizeErrorMessage(e?.message ? String(e.message) : t('error.generationFailed'));
    Notification.error(t('app.title'), message);
  } finally {
    const state = getGlobalState();
    state.pendingSeoKeywords = '';
    if (canDisable) {
      button.disabled = false;
    } else {
      button.removeAttribute('aria-disabled');
      button.classList.remove('disabled');
    }
    button.removeAttribute('aria-busy');
    button.setAttribute('title', labelGenerate);
    labelSpan.textContent = labelGenerate;
  }
}

function initialize() {
  const state = getGlobalState();
  if (state.initialized) {
    scheduleBootstrap();
    return;
  }

  state.initialized = true;
  document.addEventListener('click', handleButtonClick);
  observeNewControls();
  scheduleBootstrap();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initialize, { once: true });
} else {
  initialize();
}
