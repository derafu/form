<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Processor;

use Derafu\Form\Contract\Processor\UiSchemaRuleEvaluatorInterface;
use Derafu\Form\Contract\UiSchema\ConditionSchemaInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaCompositeConditionInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaConditionInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaRuleInterface;
use Derafu\Form\UiSchema\UiSchemaCompositeConditionType;
use Derafu\Form\UiSchema\UiSchemaRuleEffect;
use LogicException;

/**
 * Evaluates UI schema rules against a data set.
 *
 * This service centralises the rule-evaluation logic so it can be reused by
 * both FormDataProcessor (server-side field filtering) and ControlRenderer
 * (initial render state).
 */
final class UiSchemaRuleEvaluator implements UiSchemaRuleEvaluatorInterface
{
    /**
     * {@inheritDoc}
     */
    public function isActive(UiSchemaRuleInterface $rule, array $data): bool
    {
        $conditionMet = $this->evaluateCompositeCondition($rule->getCondition(), $data);

        return match ($rule->getEffect()) {
            UiSchemaRuleEffect::SHOW    => $conditionMet,
            UiSchemaRuleEffect::HIDE    => !$conditionMet,
            UiSchemaRuleEffect::ENABLE  => $conditionMet,
            UiSchemaRuleEffect::DISABLE => !$conditionMet,
        };
    }

    // =========================================================================
    // Condition evaluation
    // =========================================================================

    /**
     * Evaluates a composite condition recursively against the data.
     *
     * AND → all child conditions must be satisfied.
     * OR  → at least one child condition must be satisfied.
     *
     * @param UiSchemaCompositeConditionInterface $condition The composite
     * condition to evaluate.
     * @param array $data The data to evaluate the condition against.
     * @return bool True if the composite condition is satisfied.
     */
    private function evaluateCompositeCondition(
        UiSchemaCompositeConditionInterface $condition,
        array $data
    ): bool {
        $results = array_map(
            fn ($child) => $child instanceof UiSchemaCompositeConditionInterface
                ? $this->evaluateCompositeCondition($child, $data)
                : $this->evaluateSimpleCondition($child, $data),
            $condition->getConditions()
        );

        return match ($condition->getType()) {
            UiSchemaCompositeConditionType::AND => !in_array(false, $results, strict: true),
            UiSchemaCompositeConditionType::OR  => in_array(true, $results, strict: true),
        };
    }

    /**
     * Evaluates a simple (leaf) condition against the data.
     *
     * Extracts the field value identified by the condition scope and tests it
     * against the condition's schema fragment.
     *
     * @param UiSchemaConditionInterface $condition The simple condition.
     * @param array $data The data to evaluate the condition against.
     * @return bool True if the field value satisfies the condition schema.
     */
    private function evaluateSimpleCondition(
        UiSchemaConditionInterface $condition,
        array $data
    ): bool {
        $fieldName = $this->extractFieldNameFromScope($condition->getScope());
        return $this->evaluateSchema($condition->getSchema(), $data[$fieldName] ?? null);
    }

    /**
     * Evaluates a ConditionSchemaInterface fragment against a field value.
     *
     * Multiple keywords in the same schema object are combined with an implicit
     * AND (JSON Schema semantics): all must be satisfied for the result to be
     * true. Returns false when no recognised keyword is present.
     *
     * ## Supported keywords
     *
     *   - `const`              — strict equality (including null).
     *   - `enum`               — value must be one of the listed values.
     *   - `contains`           — value must be an array with at least one item
     *                           satisfying the nested schema (multiselect).
     *   - `not`                — negation of the nested schema fragment.
     *   - `minimum`            — value >= minimum (numeric, inclusive).
     *   - `maximum`            — value <= maximum (numeric, inclusive).
     *   - `exclusiveMinimum`   — value >  exclusiveMinimum (numeric, strict).
     *   - `exclusiveMaximum`   — value <  exclusiveMaximum (numeric, strict).
     *   - `pattern`            — value matches the ECMA regex (string fields).
     *
     * @param ConditionSchemaInterface $schema The condition schema fragment.
     * @param mixed $value The field value to test.
     * @return bool True if the value satisfies all keywords in the fragment.
     */
    private function evaluateSchema(ConditionSchemaInterface $schema, mixed $value): bool
    {
        $checks = [];

        if ($schema->hasConst()) {
            $checks[] = $value === $schema->getConst();
        }

        if ($schema->getEnum() !== null) {
            $enum = $schema->getEnum();
            // Support both plain arrays (JSON Schema standard: values are valid
            // values) and key→label arrays (keys are submitted values, values
            // are display labels).
            if (array_is_list($enum)) {
                $checks[] = in_array($value, $enum, strict: true);
            } else {
                $checks[] = array_key_exists($value, $enum);
            }
        }

        if ($schema->getContains() !== null) {
            if (!is_array($value)) {
                $checks[] = false;
            } else {
                $found = false;
                foreach ($value as $item) {
                    if ($this->evaluateSchema($schema->getContains(), $item)) {
                        $found = true;
                        break;
                    }
                }
                $checks[] = $found;
            }
        }

        if ($schema->getNot() !== null) {
            $checks[] = !$this->evaluateSchema($schema->getNot(), $value);
        }

        if ($schema->getMinimum() !== null) {
            $checks[] = is_numeric($value) && $value >= $schema->getMinimum();
        }

        if ($schema->getMaximum() !== null) {
            $checks[] = is_numeric($value) && $value <= $schema->getMaximum();
        }

        if ($schema->getExclusiveMinimum() !== null) {
            $checks[] = is_numeric($value) && $value > $schema->getExclusiveMinimum();
        }

        if ($schema->getExclusiveMaximum() !== null) {
            $checks[] = is_numeric($value) && $value < $schema->getExclusiveMaximum();
        }

        if ($schema->getPattern() !== null) {
            // JSON Schema patterns are ECMA regexes without delimiters.
            // We add '/' delimiters and escape any '/' inside the pattern.
            // An invalid PCRE pattern is a programming error (misconfigured
            // form definition) and must throw immediately rather than silently
            // producing wrong activation behaviour.
            $regex = '/' . str_replace('/', '\/', $schema->getPattern()) . '/';
            $result = @preg_match($regex, (string) $value);
            if ($result === false) {
                throw new LogicException(sprintf(
                    'Invalid PCRE pattern in rule condition: "%s".',
                    $schema->getPattern()
                ));
            }
            $checks[] = $result === 1;
        }

        if (empty($checks)) {
            return false;
        }

        // All checks must pass (implicit AND across keywords).
        return !in_array(false, $checks, strict: true);
    }

    /**
     * Extracts the field name from a JSON Pointer scope string.
     *
     * Handles simple scopes (#/properties/field) and nested scopes
     * (#/properties/parent/properties/child → "parent.child").
     *
     * @param string $scope The scope string, e.g. "#/properties/region".
     * @return string The field name or dot-notated path.
     */
    private function extractFieldNameFromScope(string $scope): string
    {
        if (preg_match('~^#/properties/(.+)$~', $scope, $matches)) {
            return str_replace('/properties/', '.', $matches[1]);
        }

        return $scope;
    }
}
