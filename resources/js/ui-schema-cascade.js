/**
 * Derafu UI Schema Cascade — client-side population of dependent select fields.
 *
 * Reads the data-cascade attribute emitted by SelectWidgetRenderer and wires
 * up change listeners so that selecting a value in a "trigger" select
 * automatically populates the options of every dependent select below it.
 * Chains of arbitrary depth are supported (region → city → district → ...).
 *
 * Usage:
 *
 *   import { UiSchemaCascade } from './ui-schema-cascade.js';
 *   document.addEventListener('DOMContentLoaded', () => UiSchemaCascade.init());
 *
 * HTML contract (emitted by SelectWidgetRenderer when cascade option is set):
 *
 *   Static choices:
 *   <select name="ciudad"
 *           data-cascade='{"dependsOn":"#/properties/region","choices":{"rm":{...}}}'>
 *   </select>
 *
 *   AJAX:
 *   <select name="ciudad"
 *           data-cascade='{"dependsOn":"#/properties/region","url":"/api/ciudades?region={value}"}'>
 *   </select>
 *
 * Design note — resolveChoices():
 *   All option retrieval is routed through a single resolveChoices() method
 *   that returns a Promise. Static choices resolve immediately from the
 *   embedded map; AJAX choices are fetched via resolveAjax(), which caches
 *   results in memory and handles errors gracefully. Both sources share the
 *   same calling interface — no other part of the module needs to know which.
 */
export const UiSchemaCascade = {

    /**
     * Initialises cascade handling for all select[data-cascade] elements
     * found inside `scope`.
     *
     * Selects are sorted so that triggers are always initialised before their
     * dependents, enabling correct population of multi-level chains on load.
     *
     * @param {Document|Element} scope Root element to search within (defaults
     *   to the full document).
     */
    async init(scope = document) {
        const selects = Array.from(scope.querySelectorAll('select[data-cascade]'));
        if (selects.length === 0) {
            return;
        }

        // Map: triggerFieldName → [dependent <select> elements].
        const dependencyMap = this.buildDependencyMap(selects);

        // Process triggers before their dependents so initial population works
        // correctly for chained selects (e.g. region → city → district).
        const sorted = this.sortByDependency(selects);

        // Use for...of + await so that each select is fully populated before
        // moving to the next one. This is critical for chains: comarca must read
        // ciudad's value only after ciudad has already been populated. With a
        // plain forEach + .then(), all .then() callbacks are deferred to the
        // microtask queue and run after the loop ends — too late for downstream
        // selects to see the correct intermediate values.
        for (const select of sorted) {
            let cascade;
            try {
                cascade = JSON.parse(select.dataset.cascade);
            } catch (e) {
                console.warn('[UiSchemaCascade] Could not parse data-cascade on', select, e);
                continue;
            }

            const form = select.closest('form');
            if (!form) {
                continue;
            }

            const triggerName = this.extractFieldName(cascade.dependsOn);

            // Register change listener on every element sharing the trigger name
            // (covers both <select> and <input type="radio"> triggers).
            form.querySelectorAll(`[name="${triggerName}"]`).forEach(trigger => {
                trigger.addEventListener('change', () => {
                    this.updateSelect(select, cascade, form);
                });
            });

            // Initial population: await the result so downstream selects in the
            // chain see the correct value on their own iteration.
            const triggerValue = this.getFieldValue(triggerName, form);
            if (triggerValue) {
                const choices = await this.resolveChoices(cascade, triggerValue);
                this.populateSelect(select, choices);
            }
        }
    },

    // =========================================================================
    // Update
    // =========================================================================

    /**
     * Called when a trigger field changes. Fetches the new choices for the
     * current trigger value, repopulates the dependent select, then propagates
     * the change to any further dependents by dispatching a synthetic 'change'
     * event on the newly-populated select.
     *
     * Propagation via dispatchEvent reuses the listeners registered in init(),
     * so chains of arbitrary depth work without any explicit recursion here:
     * the event on ciudad triggers updateSelect for comarca, which in turn
     * dispatches on comarca and so on.
     *
     * The form-scoping that used to live in resetDownstream() is now implicit:
     * each listener was registered with form.querySelectorAll(), so it already
     * only fires for selects within the same <form>.
     *
     * @param {HTMLSelectElement} select  The dependent select to update.
     * @param {Object}            cascade Parsed cascade config from data-cascade.
     * @param {HTMLFormElement}   form   Closest <form> element.
     */
    updateSelect(select, cascade, form) {
        const triggerName = this.extractFieldName(cascade.dependsOn);
        const triggerValue = this.getFieldValue(triggerName, form);

        this.resolveChoices(cascade, triggerValue).then(choices => {
            this.populateSelect(select, choices);
            // Notify downstream selects by dispatching a synthetic change event.
            // Their listeners (registered in init) will call updateSelect on
            // them in turn, re-populating or clearing them based on the new
            // value. When triggerValue is empty, populateSelect produces an
            // empty select, the event fires, and each downstream level clears
            // itself recursively — no separate resetDownstream needed.
            select.dispatchEvent(new Event('change'));
        });
    },

    /**
     * Replaces the options of a <select> (keeping the placeholder) with the
     * provided choices map. If the previously selected value is still present
     * in the new choices it is preserved; otherwise:
     *   - Selects with a placeholder (<option value="">) reset to that placeholder.
     *   - Selects without a placeholder fall through to their first real option,
     *     matching the default browser behaviour for non-cascade selects.
     *
     * @param {HTMLSelectElement} select  The select element to update.
     * @param {Object}            choices Map of { value: label } pairs.
     */
    populateSelect(select, choices) {
        const previousValue = select.value;

        // Remove all non-placeholder options (placeholder has value="").
        Array.from(select.options).forEach(opt => {
            if (opt.value !== '') {
                opt.remove();
            }
        });

        // Append new options.
        Object.entries(choices).forEach(([value, label]) => {
            select.add(new Option(label, value));
        });

        // Restore previous selection if the value is still valid.
        if (previousValue && Object.prototype.hasOwnProperty.call(choices, previousValue)) {
            select.value = previousValue;
        } else {
            select.value = '';
            // If no <option value=""> placeholder exists, value='' matches nothing
            // (selectedIndex → -1) and the select appears visually blank. In that
            // case fall through to the first available option instead.
            if (select.selectedIndex === -1) {
                select.selectedIndex = 0;
            }
        }
    },

    // =========================================================================
    // Choices resolution
    // =========================================================================

    /**
     * Resolves the choices for a given trigger value.
     *
     * Always returns a Promise so that static and AJAX sources share the same
     * calling interface. Static choices resolve immediately; AJAX choices are
     * fetched remotely via resolveAjax().
     *
     * @param {Object} cascade      Parsed cascade config (has .choices or .url).
     * @param {string} triggerValue Current value of the trigger field.
     * @returns {Promise<Object>}   Resolves to a { value: label } map.
     */
    resolveChoices(cascade, triggerValue) {
        if (!triggerValue) {
            return Promise.resolve({});
        }

        if (cascade.choices) {
            return this.resolveStatic(cascade.choices, triggerValue);
        }

        if (cascade.url) {
            return this.resolveAjax(cascade.url, triggerValue);
        }

        return Promise.resolve({});
    },

    /**
     * Returns the choices for `triggerValue` from an embedded static map.
     *
     * @param {Object} choices      The full choices map from data-cascade.
     * @param {string} triggerValue Current value of the trigger field.
     * @returns {Promise<Object>}   Resolves to the sub-map for triggerValue,
     *                              or {} if the key is not found.
     */
    resolveStatic(choices, triggerValue) {
        return Promise.resolve(choices[triggerValue] ?? {});
    },

    /**
     * Fetches choices for `triggerValue` from a remote endpoint.
     *
     * The URL template may contain `{value}` as a placeholder, which is
     * replaced with the URL-encoded trigger value before the request is made,
     * e.g. "/api/cities?region={value}" → "/api/cities?region=rm".
     *
     * Session cookies are sent automatically (`credentials: 'same-origin'`),
     * so endpoints protected by a server-side session require no extra setup.
     *
     * Results are cached in memory (keyed by resolved URL) for the lifetime
     * of the page, avoiding redundant requests when the user selects a
     * previously-fetched value again.
     *
     * The endpoint must return a JSON object mapping option values to labels:
     *   { "santiago": "Santiago", "maipu": "Maipú", ... }
     *
     * @param {string} urlTemplate URL with optional `{value}` placeholder.
     * @param {string} triggerValue Current value of the trigger field.
     * @returns {Promise<Object>} Resolves to a { value: label } map, or {} on error.
     */
    resolveAjax(urlTemplate, triggerValue) {
        const url = urlTemplate.replace('{value}', encodeURIComponent(triggerValue));

        if (this._ajaxCache.has(url)) {
            return Promise.resolve(this._ajaxCache.get(url));
        }

        return fetch(url, { credentials: 'same-origin' })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                this._ajaxCache.set(url, data);
                return data;
            })
            .catch(err => {
                console.warn('[UiSchemaCascade] AJAX fetch failed for', url, err);
                return {};
            });
    },

    /** In-memory cache for AJAX results, keyed by resolved URL. */
    _ajaxCache: new Map(),

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Builds the dependency map used by sortByDependency to order selects
     * correctly for initial population.
     *
     * @param {HTMLSelectElement[]} selects All cascade selects in scope.
     * @returns {Object} Map of triggerFieldName → [dependent select elements].
     */
    buildDependencyMap(selects) {
        const map = {};
        selects.forEach(select => {
            try {
                const cascade = JSON.parse(select.dataset.cascade);
                const triggerName = this.extractFieldName(cascade.dependsOn);
                if (!map[triggerName]) {
                    map[triggerName] = [];
                }
                map[triggerName].push(select);
            } catch (e) {
                // Skip selects with malformed cascade JSON.
            }
        });
        return map;
    },

    /**
     * Sorts cascade selects so that a trigger always appears before its
     * dependents. This ensures correct initial population order for chains.
     *
     * Uses a simple bubble-sort that swaps a dependent ahead of its trigger
     * when the trigger appears later in the list. Stable for acyclic graphs;
     * circular dependencies (a programming error) are guarded with an
     * iteration cap.
     *
     * @param {HTMLSelectElement[]} selects Unsorted cascade selects.
     * @returns {HTMLSelectElement[]}       Sorted copy.
     */
    sortByDependency(selects) {
        const result = [...selects];
        const maxIterations = result.length * result.length;
        let swapped = true;
        let iterations = 0;

        while (swapped && iterations < maxIterations) {
            swapped = false;
            iterations++;

            for (let i = 0; i < result.length - 1; i++) {
                let cascade;
                try {
                    cascade = JSON.parse(result[i].dataset.cascade);
                } catch (e) {
                    continue;
                }

                const triggerName = this.extractFieldName(cascade.dependsOn);

                // If this select's trigger appears later in the list, move the
                // trigger before this select.
                const triggerIdx = result.findIndex(
                    (s, idx) => idx > i && s.name === triggerName
                );

                if (triggerIdx !== -1) {
                    [result[i], result[triggerIdx]] = [result[triggerIdx], result[i]];
                    swapped = true;
                }
            }
        }

        return result;
    },

    /**
     * Reads the current value of a named field from the form DOM.
     *
     * Handles <select>, radio groups, and regular inputs.
     *
     * @param {string}          fieldName The field name attribute.
     * @param {HTMLFormElement} form      The <form> element to search within.
     * @returns {string} The current value, or '' if not found or empty.
     */
    getFieldValue(fieldName, form) {
        // <select> (single).
        const select = form.querySelector(`select[name="${fieldName}"]`);
        if (select) {
            return select.value || '';
        }

        // Radio group — return the checked option's value.
        const checkedRadio = form.querySelector(
            `input[type="radio"][name="${fieldName}"]:checked`
        );
        if (checkedRadio) {
            return checkedRadio.value;
        }

        // Any other input (text, hidden, etc.).
        const input = form.querySelector(`[name="${fieldName}"]`);
        return input ? (input.value || '') : '';
    },

    /**
     * Extracts the plain field name from a JSON Pointer scope string.
     *
     * "#/properties/region"                   → "region"
     * "#/properties/address/properties/city"  → "address.city"
     *
     * @param {string} scope JSON Pointer scope string.
     * @returns {string}
     */
    extractFieldName(scope) {
        const match = scope.match(/^#\/properties\/(.+)$/);
        if (!match) {
            return scope;
        }
        return match[1].replace(/\/properties\//g, '.');
    },
};
