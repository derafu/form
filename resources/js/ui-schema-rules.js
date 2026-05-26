/**
 * Derafu Form UI Schema Rules — client-side evaluation of JSON Forms UI schema rules.
 *
 * Mirrors the logic in UiSchemaRuleEvaluator.php so that SHOW / HIDE / ENABLE
 * / DISABLE rules react to field changes without a page reload.
 *
 * Usage:
 *
 *   import { UiSchemaRules } from './ui-schema-rules.js';
 *   document.addEventListener('DOMContentLoaded', () => UiSchemaRules.init());
 *
 * The PHP renderer already applies the initial state (d-none / disabled) so
 * this module only needs to wire up change listeners and re-evaluate on every
 * user interaction.
 *
 * HTML contract (emitted by row.html.twig when a control has a rule):
 *
 *   <div class="mb-3 [d-none]"
 *        data-rule='{"effect":"SHOW","condition":{"scope":"#/properties/x","schema":{"const":"y"}}}'
 *        data-field="fieldName">
 *     ...
 *   </div>
 */
export const UiSchemaRules = {

    /**
     * Initialises rule handling for all [data-rule] rows inside `scope`.
     *
     * @param {Document|Element} scope Root element to search within (defaults
     *   to the full document).
     */
    init(scope = document) {
        scope.querySelectorAll('[data-rule]').forEach(row => {
            let rule;
            try {
                rule = JSON.parse(row.dataset.rule);
            } catch (e) {
                console.warn('[UiSchemaRules] Could not parse data-rule on', row, e);
                return;
            }

            const form = row.closest('form');
            if (!form) {
                return;
            }

            this.setupRule(row, rule, form);
        });
    },

    /**
     * Registers change / input listeners on all trigger fields for a rule.
     *
     * @param {Element} row  The .mb-3 wrapper div carrying data-rule.
     * @param {Object}  rule Parsed rule object {effect, condition}.
     * @param {Element} form The closest <form> element.
     */
    setupRule(row, rule, form) {
        const triggerNames = this.collectTriggerNames(rule.condition);
        const seen = new Set();

        triggerNames.forEach(name => {
            if (seen.has(name)) {
                return;
            }
            seen.add(name);

            // Collect all inputs that belong to this field name (covers
            // radio groups, checkbox groups, regular inputs and selects).
            const inputs = [
                ...form.querySelectorAll(`[name="${name}"]`),
                ...form.querySelectorAll(`[name="${name}[]"]`),
            ];

            inputs.forEach(input => {
                const handler = () => this.applyRule(row, rule, form);
                input.addEventListener('change', handler);

                // For free-text inputs also react on every keystroke so that
                // pattern / minimum / maximum conditions feel responsive.
                if (
                    input.tagName === 'INPUT'
                    && !['checkbox', 'radio', 'file'].includes(input.type)
                ) {
                    input.addEventListener('input', handler);
                }
            });
        });
    },

    /**
     * Evaluates the rule against the form's current values and applies the
     * appropriate effect to the row.
     *
     * SHOW / HIDE → toggle Bootstrap's d-none class + disable/enable all
     *               form controls inside the row (so hidden fields are not
     *               submitted, consistent with server-side skip behaviour).
     * ENABLE / DISABLE → only toggle disabled on the form controls (the row
     *                    stays visible).
     *
     * @param {Element} row  Row element.
     * @param {Object}  rule Parsed rule object.
     * @param {Element} form Closest <form> element.
     */
    applyRule(row, rule, form) {
        const conditionMet = this.evaluateCondition(rule.condition, form);
        const effect = rule.effect;

        const shouldHide =
            (effect === 'SHOW' && !conditionMet) ||
            (effect === 'HIDE' && conditionMet);

        const shouldDisable =
            (effect === 'ENABLE' && !conditionMet) ||
            (effect === 'DISABLE' && conditionMet);

        if (effect === 'SHOW' || effect === 'HIDE') {
            row.classList.toggle('d-none', shouldHide);
            // Disable controls inside a hidden row so they are not submitted.
            row.querySelectorAll('input, select, textarea').forEach(el => {
                el.disabled = shouldHide;
            });
        } else {
            // ENABLE / DISABLE: only affect interactivity, never visibility.
            row.querySelectorAll('input, select, textarea').forEach(el => {
                el.disabled = shouldDisable;
            });
        }
    },

    // =========================================================================
    // Condition evaluation (mirrors UiSchemaRuleEvaluator.php)
    // =========================================================================

    /**
     * Evaluates a condition (simple or composite) against current form values.
     *
     * @param {Object}  condition Parsed condition.
     * @param {Element} form      Closest <form> element.
     * @returns {boolean}
     */
    evaluateCondition(condition, form) {
        // Composite condition: has a "type" key of "AND" or "OR".
        if (condition.type === 'AND') {
            return condition.conditions.every(c => this.evaluateCondition(c, form));
        }
        if (condition.type === 'OR') {
            return condition.conditions.some(c => this.evaluateCondition(c, form));
        }

        // Simple (leaf) condition: has "scope" + "schema".
        const fieldName = this.extractFieldName(condition.scope);
        const value = this.getFieldValue(fieldName, form);
        return this.evaluateSchema(condition.schema, value);
    },

    /**
     * Evaluates a JSON Schema fragment against a value.
     *
     * Supported keywords: const, enum, contains, not, minimum, maximum,
     * exclusiveMinimum, exclusiveMaximum, pattern.
     *
     * Multiple keywords are combined with an implicit AND (all must pass).
     * Returns false when no recognised keyword is present.
     *
     * @param {Object} schema JSON Schema fragment from the condition.
     * @param {*}      value  Current field value (string from DOM, or array).
     * @returns {boolean}
     */
    evaluateSchema(schema, value) {
        const checks = [];

        if ('const' in schema) {
            // DOM values are always strings; coerce for numeric consts.
            checks.push(value === schema.const || String(value) === String(schema.const));
        }

        if ('enum' in schema) {
            checks.push(
                schema.enum.some(v => value === v || String(value) === String(v))
            );
        }

        if ('contains' in schema) {
            const arr = Array.isArray(value) ? value : [value];
            checks.push(arr.some(item => this.evaluateSchema(schema.contains, item)));
        }

        if ('not' in schema) {
            checks.push(!this.evaluateSchema(schema.not, value));
        }

        if ('minimum' in schema) {
            checks.push(value !== '' && !isNaN(value) && Number(value) >= schema.minimum);
        }

        if ('maximum' in schema) {
            checks.push(value !== '' && !isNaN(value) && Number(value) <= schema.maximum);
        }

        if ('exclusiveMinimum' in schema) {
            checks.push(value !== '' && !isNaN(value) && Number(value) > schema.exclusiveMinimum);
        }

        if ('exclusiveMaximum' in schema) {
            checks.push(value !== '' && !isNaN(value) && Number(value) < schema.exclusiveMaximum);
        }

        if ('pattern' in schema) {
            checks.push(new RegExp(schema.pattern).test(String(value ?? '')));
        }

        if (checks.length === 0) {
            return false;
        }

        // All checks must pass (implicit AND across keywords).
        return checks.every(Boolean);
    },

    // =========================================================================
    // DOM helpers
    // =========================================================================

    /**
     * Reads the current value of a named form field from the DOM.
     *
     * Handles: regular inputs, checkboxes (single + groups), radio buttons,
     * single selects, multiple selects.
     *
     * @param {string}  fieldName The field name (without [] suffix).
     * @param {Element} form      The <form> element to search within.
     * @returns {string|string[]|null}
     */
    getFieldValue(fieldName, form) {
        // Checkbox groups use name="field[]".
        const checkboxGroup = form.querySelectorAll(
            `input[type="checkbox"][name="${fieldName}[]"]:checked`
        );
        if (checkboxGroup.length > 0) {
            return Array.from(checkboxGroup).map(cb => cb.value);
        }

        // Single checkbox (boolean field).
        const singleCheckbox = form.querySelector(
            `input[type="checkbox"][name="${fieldName}"]`
        );
        if (singleCheckbox) {
            return singleCheckbox.checked ? (singleCheckbox.value || 'true') : '';
        }

        // Radio group — return the checked option's value.
        const checkedRadio = form.querySelector(
            `input[type="radio"][name="${fieldName}"]:checked`
        );
        if (checkedRadio) {
            return checkedRadio.value;
        }
        // If no radio is checked yet, return empty string.
        const anyRadio = form.querySelector(`input[type="radio"][name="${fieldName}"]`);
        if (anyRadio) {
            return '';
        }

        // Select element (single or multiple).
        const select = form.querySelector(`select[name="${fieldName}"]`);
        if (select) {
            if (select.multiple) {
                return Array.from(select.selectedOptions).map(o => o.value);
            }
            return select.value;
        }

        // Any other input type (text, number, date, etc.).
        const input = form.querySelector(`[name="${fieldName}"]`);
        return input ? input.value : null;
    },

    /**
     * Extracts the field name from a JSON Pointer scope.
     *
     * "#/properties/fieldName"                     → "fieldName"
     * "#/properties/parent/properties/child"       → "parent.child"
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

    /**
     * Recursively collects all trigger field names referenced in a condition.
     *
     * @param {Object} condition Parsed condition (simple or composite).
     * @returns {string[]}
     */
    collectTriggerNames(condition) {
        if (condition.type === 'AND' || condition.type === 'OR') {
            return condition.conditions.flatMap(c => this.collectTriggerNames(c));
        }
        return [this.extractFieldName(condition.scope)];
    },
};
