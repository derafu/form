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
use Derafu\Form\Contract\UiSchema\UiSchemaCompositeConditionInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaConditionInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaRuleInterface;
use Derafu\Form\UiSchema\UiSchemaCompositeConditionType;
use Derafu\Form\UiSchema\UiSchemaRuleEffect;

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
     * against the condition's JSON Schema fragment.
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
     * Evaluates a JSON Schema fragment against a field value.
     *
     * Multiple keywords in the same schema object are combined with an implicit
     * AND (JSON Schema semantics): all must be satisfied for the result to be
     * true. Returns false when no recognised keyword is found.
     *
     * ## Supported keywords
     *
     *   - `const`              — strict equality.
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
     * @param array $schema The JSON Schema fragment from the condition.
     * @param mixed $value  The field value to test.
     * @return bool True if the value satisfies all keywords in the fragment.
     */
    private function evaluateSchema(array $schema, mixed $value): bool
    {
        $checks = [];

        if (array_key_exists('const', $schema)) {
            $checks[] = $value === $schema['const'];
        }

        if (array_key_exists('enum', $schema)) {
            $checks[] = in_array($value, (array) $schema['enum'], strict: true);
        }

        if (array_key_exists('contains', $schema)) {
            if (!is_array($value)) {
                $checks[] = false;
            } else {
                $found = false;
                foreach ($value as $item) {
                    if ($this->evaluateSchema($schema['contains'], $item)) {
                        $found = true;
                        break;
                    }
                }
                $checks[] = $found;
            }
        }

        if (array_key_exists('not', $schema)) {
            $checks[] = !$this->evaluateSchema($schema['not'], $value);
        }

        if (array_key_exists('minimum', $schema)) {
            $checks[] = is_numeric($value) && $value >= $schema['minimum'];
        }

        if (array_key_exists('maximum', $schema)) {
            $checks[] = is_numeric($value) && $value <= $schema['maximum'];
        }

        if (array_key_exists('exclusiveMinimum', $schema)) {
            $checks[] = is_numeric($value) && $value > $schema['exclusiveMinimum'];
        }

        if (array_key_exists('exclusiveMaximum', $schema)) {
            $checks[] = is_numeric($value) && $value < $schema['exclusiveMaximum'];
        }

        if (array_key_exists('pattern', $schema)) {
            // JSON Schema patterns are ECMA regexes without delimiters.
            // We add '/' delimiters and escape any '/' inside the pattern.
            // An invalid PCRE pattern is a programming error (misconfigured
            // form definition) and must throw immediately rather than silently
            // producing wrong activation behaviour.
            $regex = '/' . str_replace('/', '\/', (string) $schema['pattern']) . '/';
            $result = @preg_match($regex, (string) $value);
            if ($result === false) {
                throw new \LogicException(sprintf(
                    'Invalid PCRE pattern in rule condition: "%s".',
                    $schema['pattern']
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
