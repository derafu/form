/**
 * Derafu Invalid Reveal — shows the first invalid control of a form even when
 * it is inside a tab or a collapsed section that is not visible.
 *
 * The browser validates the controls that are in a hidden panel too (a tab that
 * is not active, a section that is collapsed), but it can not focus them: it
 * blocks the submit and shows nothing ("An invalid form control is not
 * focusable"). This script listens for the `invalid` event, opens the panels
 * that have the first invalid control, focuses it and shows the message of the
 * browser.
 *
 * Usage:
 *
 *   import { InvalidReveal } from './invalid-reveal.js';
 *   document.addEventListener('DOMContentLoaded', () => InvalidReveal.init());
 *
 * It uses Bootstrap 5 (`window.bootstrap`) to open the panels, the same that
 * the templates of the categorizations and of the groups use. Without it, it
 * clicks the button of the tab and adds the class `show` to the section.
 */

/**
 * Time to wait for a panel that is opening before focusing the control (ms).
 */
const TRANSITION_TIMEOUT = 600;

export const InvalidReveal = {
    /**
     * Registers the listener on every form of the scope (once each one).
     *
     * @param {Document|Element} scope Root element to search within (defaults
     *   to the full document).
     */
    init(scope = document) {
        scope.querySelectorAll('form').forEach(form => {
            if (form.dataset.invalidReveal === 'true') {
                return;
            }
            form.dataset.invalidReveal = 'true';

            // The `invalid` event does not bubble: it is listened in capture.
            form.addEventListener('invalid', event => this.handle(form, event), true);
        });
    },

    /**
     * Handles the `invalid` event of a control.
     *
     * Only the first invalid control of the form is revealed, and only when it
     * is not visible: if it is visible the browser does its own work.
     *
     * @param {HTMLFormElement} form The form that has the control.
     * @param {Event} event The `invalid` event.
     */
    handle(form, event) {
        if (form._invalidRevealing) {
            return;
        }

        const control = event.target;
        if (control !== this.firstInvalid(form) || this.isVisible(control)) {
            return;
        }

        // The message of the browser would not have where to show: it is shown
        // when the panels are open.
        event.preventDefault();
        form._invalidRevealing = true;

        this.reveal(control)
            .then(() => {
                control.focus();
                control.reportValidity();
            })
            .finally(() => {
                form._invalidRevealing = false;
            });
    },

    /**
     * The first control of the form (in the order of the document) that is not
     * valid.
     *
     * @param {HTMLFormElement} form
     * @returns {Element|undefined}
     */
    firstInvalid(form) {
        return [...form.elements].find(
            element => element.willValidate && !element.validity.valid
        );
    },

    /**
     * Whether the control is shown (no ancestor is hidden).
     *
     * @param {Element} control
     * @returns {boolean}
     */
    isVisible(control) {
        return control.getClientRects().length > 0;
    },

    /**
     * Opens the tabs and the sections that have the control, from the
     * outermost to the innermost.
     *
     * @param {Element} control
     * @returns {Promise<void>} Resolves when the panels are open.
     */
    async reveal(control) {
        const panels = [];
        for (let element = control.parentElement; element; element = element.parentElement) {
            if (element.classList.contains('tab-pane') && !element.classList.contains('active')) {
                panels.unshift({ type: 'tab', element });
            } else if (element.classList.contains('collapse') && !element.classList.contains('show')) {
                panels.unshift({ type: 'collapse', element });
            }
        }

        for (const panel of panels) {
            await (panel.type === 'tab' ? this.showTab(panel.element) : this.showCollapse(panel.element));
        }
    },

    /**
     * Activates the tab that has the pane.
     *
     * @param {Element} pane The `.tab-pane`.
     * @returns {Promise<void>}
     */
    showTab(pane) {
        const trigger = document.querySelector(
            `[data-bs-target="#${CSS.escape(pane.id)}"], a[href="#${CSS.escape(pane.id)}"]`
        );
        if (!trigger) {
            return Promise.resolve();
        }

        return this.whenShown(trigger, 'shown.bs.tab', () => {
            if (window.bootstrap?.Tab) {
                window.bootstrap.Tab.getOrCreateInstance(trigger).show();
            } else {
                trigger.click();
            }
        });
    },

    /**
     * Expands the collapsed section.
     *
     * @param {Element} collapse The `.collapse`.
     * @returns {Promise<void>}
     */
    showCollapse(collapse) {
        return this.whenShown(collapse, 'shown.bs.collapse', () => {
            if (window.bootstrap?.Collapse) {
                window.bootstrap.Collapse.getOrCreateInstance(collapse, { toggle: false }).show();
            } else {
                collapse.classList.add('show');
            }
        });
    },

    /**
     * Runs the action and waits for the event that says that the panel is
     * open (or for the timeout, if the event never comes).
     *
     * @param {Element} target The element that emits the event.
     * @param {string} eventName The event of the end of the transition.
     * @param {Function} action What opens the panel.
     * @returns {Promise<void>}
     */
    whenShown(target, eventName, action) {
        return new Promise(resolve => {
            const timer = setTimeout(resolve, TRANSITION_TIMEOUT);
            target.addEventListener(eventName, () => {
                clearTimeout(timer);
                resolve();
            }, { once: true });

            action();
        });
    },
};
