/* eslint-disable no-alert */
(function () {
    'use strict';

    const GUIDE_STORAGE_KEY = 'barrierefreiSpace.subscriptionGuide.v1.seen';

    /**
     * Copy text to the clipboard with Clipboard API first, then fallback to execCommand.
     *
     * @param {string} text Text to copy.
     * @returns {Promise<boolean>} Whether the copy operation succeeds.
     */
    async function copyText(text) {
        if (!text) {
            return false;
        }

        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
                return true;
            }
        } catch (error) {
            // Clipboard API may fail under backend iframe or permission policy, so fallback is required.
        }

        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', 'readonly');
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();
        textarea.setSelectionRange(0, textarea.value.length);

        let copied = false;
        try {
            copied = document.execCommand('copy');
        } catch (error) {
            copied = false;
        }

        document.body.removeChild(textarea);
        return copied;
    }

    function bindCopyButton() {
        const copyButton = document.querySelector('.js-copy-license-key');
        const feedback = document.querySelector('.js-copy-feedback');
        if (!copyButton) {
            return;
        }

        copyButton.addEventListener('click', async function () {
            const licenseKey = copyButton.getAttribute('data-license-key') || '';
            const messageCopied = copyButton.getAttribute('data-copy-success') || 'Copied';
            const messageCopyFailed = copyButton.getAttribute('data-copy-failed') || 'Copy failed, please copy manually';
            const promptManualCopy = copyButton.getAttribute('data-copy-prompt') || 'Please copy license key manually';
            const copied = await copyText(licenseKey);

            if (feedback) {
                feedback.textContent = copied ? messageCopied : messageCopyFailed;
            }

            if (!copied && licenseKey) {
                window.prompt(promptManualCopy, licenseKey);
            }
        });
    }

    function bindActionLoadingButtons() {
        const buttons = Array.prototype.slice.call(document.querySelectorAll('.js-action-loading'));

        buttons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                if (button.classList.contains('is-loading') || button.getAttribute('aria-disabled') === 'true') {
                    event.preventDefault();
                    return;
                }

                const loadingLabel = button.getAttribute('data-loading-label') || 'Processing...';
                const spinner = document.createElement('span');
                const label = document.createElement('span');

                spinner.className = 'atg-button-spinner';
                spinner.setAttribute('aria-hidden', 'true');
                label.textContent = loadingLabel;

                button.classList.add('is-loading');
                button.setAttribute('aria-disabled', 'true');
                button.textContent = '';
                button.appendChild(spinner);
                button.appendChild(label);
            });
        });
    }

    function initTimelinePagination() {
        const timeline = document.querySelector('.js-timeline');
        const pagination = document.querySelector('.js-timeline-pagination');
        if (!timeline || !pagination) {
            return;
        }

        const items = Array.prototype.slice.call(timeline.querySelectorAll('.js-timeline-item'));
        const pageSize = Math.max(1, parseInt(timeline.getAttribute('data-timeline-page-size') || '5', 10));
        const totalPages = Math.ceil(items.length / pageSize);
        if (totalPages <= 1) {
            return;
        }

        const previousButton = pagination.querySelector('.js-timeline-prev');
        const nextButton = pagination.querySelector('.js-timeline-next');
        const currentLabel = pagination.querySelector('.js-timeline-page-current');
        const totalLabel = pagination.querySelector('.js-timeline-page-total');
        let currentPage = 1;

        function renderPage() {
            const startIndex = (currentPage - 1) * pageSize;
            const endIndex = startIndex + pageSize;

            items.forEach(function (item, index) {
                item.hidden = index < startIndex || index >= endIndex;
            });

            if (currentLabel) {
                currentLabel.textContent = String(currentPage);
            }
            if (totalLabel) {
                totalLabel.textContent = String(totalPages);
            }
            if (previousButton) {
                previousButton.disabled = currentPage === 1;
            }
            if (nextButton) {
                nextButton.disabled = currentPage === totalPages;
            }
        }

        if (previousButton) {
            previousButton.addEventListener('click', function () {
                currentPage = Math.max(1, currentPage - 1);
                renderPage();
            });
        }
        if (nextButton) {
            nextButton.addEventListener('click', function () {
                currentPage = Math.min(totalPages, currentPage + 1);
                renderPage();
            });
        }

        pagination.hidden = false;
        renderPage();
    }

    function getGuideSeen() {
        try {
            return window.localStorage.getItem(GUIDE_STORAGE_KEY) === '1';
        } catch (error) {
            return false;
        }
    }

    function setGuideSeen() {
        try {
            window.localStorage.setItem(GUIDE_STORAGE_KEY, '1');
        } catch (error) {
            // Ignore storage errors in restricted backend contexts.
        }
    }

    function initGuide() {
        const shell = document.querySelector('.js-guide-shell');
        const openButton = document.querySelector('.js-guide-open');
        if (!shell || !openButton) {
            return;
        }

        const steps = Array.prototype.slice.call(shell.querySelectorAll('.js-guide-step'));
        const dots = Array.prototype.slice.call(shell.querySelectorAll('.js-guide-dot'));
        const closeButtons = Array.prototype.slice.call(shell.querySelectorAll('.js-guide-close'));
        const skipButton = shell.querySelector('.js-guide-skip');
        const previousButton = shell.querySelector('.js-guide-prev');
        const nextButton = shell.querySelector('.js-guide-next');
        const nextLabel = shell.querySelector('.js-guide-next-label');
        const finishLabel = shell.querySelector('.js-guide-finish-label');
        let currentStep = 0;
        let lastFocusedElement = null;

        function renderStep() {
            const isLastStep = currentStep === steps.length - 1;

            steps.forEach(function (step, index) {
                step.hidden = index !== currentStep;
            });
            dots.forEach(function (dot, index) {
                dot.classList.toggle('is-active', index === currentStep);
            });

            if (previousButton) {
                previousButton.disabled = currentStep === 0;
            }
            if (nextLabel) {
                nextLabel.hidden = isLastStep;
            }
            if (finishLabel) {
                finishLabel.hidden = !isLastStep;
            }
        }

        function openGuide() {
            lastFocusedElement = document.activeElement;
            currentStep = 0;
            shell.hidden = false;
            document.documentElement.classList.add('atg-guide-is-open');
            renderStep();

            const closeButton = shell.querySelector('.atg-guide-close');
            if (closeButton) {
                closeButton.focus();
            }
        }

        function closeGuide(markSeen) {
            shell.hidden = true;
            document.documentElement.classList.remove('atg-guide-is-open');
            if (markSeen) {
                setGuideSeen();
            }
            if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
                lastFocusedElement.focus();
            }
        }

        openButton.addEventListener('click', openGuide);

        closeButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                closeGuide(true);
            });
        });

        if (skipButton) {
            skipButton.addEventListener('click', function () {
                closeGuide(true);
            });
        }

        if (previousButton) {
            previousButton.addEventListener('click', function () {
                currentStep = Math.max(0, currentStep - 1);
                renderStep();
            });
        }

        if (nextButton) {
            nextButton.addEventListener('click', function () {
                if (currentStep >= steps.length - 1) {
                    closeGuide(true);
                    return;
                }
                currentStep += 1;
                renderStep();
            });
        }

        document.addEventListener('keydown', function (event) {
            if (!shell.hidden && event.key === 'Escape') {
                closeGuide(true);
            }
        });

        renderStep();
        if (!getGuideSeen()) {
            openGuide();
        }
    }

    function initSubscriptionCenter() {
        bindActionLoadingButtons();
        bindCopyButton();
        initTimelinePagination();
        initGuide();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSubscriptionCenter);
        return;
    }
    initSubscriptionCenter();
})();
