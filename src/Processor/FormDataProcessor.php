<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Processor;

use Derafu\DataProcessor\Contract\ProcessorInterface;
use Derafu\Form\Contract\FormFieldInterface;
use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\Processor\FormDataProcessorInterface;
use Derafu\Form\Contract\Processor\FormRulesResolverInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaCompositeConditionInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaConditionInterface;
use Derafu\Form\Exception\ValidationException;
use Derafu\Form\UiSchema\UiSchemaCompositeConditionType;
use Derafu\Form\UiSchema\UiSchemaRuleEffect;
use Throwable;

/**
 * Service to process form data using form definitions and data processor rules.
 */
final class FormDataProcessor implements FormDataProcessorInterface
{
    /**
     * Constructor.
     *
     * @param FormRulesResolverInterface $resolver The resolver that derives and
     * merges all processing rules for the form fields.
     * @param ProcessorInterface $processor The processor to process the data
     * using Derafu\DataProcessor.
     */
    public function __construct(
        private readonly FormRulesResolverInterface $resolver,
        private readonly ProcessorInterface $processor
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function process(FormInterface $form, array $data = []): ProcessResult
    {
        // If no data is provided, get it from the request.
        if (empty($data)) {
            // The data should never be empty. It's the responsibility of the
            // caller to provide the data. This solution is a workaround to
            // avoid the need to pass the request to the processor in some
            // cases, but it's not a good solution neither recommended.
            $data = array_merge($_POST, $_FILES);
        }

        $processedData = [];
        $errors = [];
        $isValid = true;

        // Tracks field names that were intentionally skipped due to an
        // inactive UiSchema rule. These must not be re-added by the
        // extra-fields loop below, even if they were submitted in $data.
        $skippedFields = [];

        // Resolve the complete, merged rules into the form's own FormRules instance.
        $this->resolver->resolve($form);

        // Process each field using the rules now stored in the form.
        foreach ($form->getRules()->toArray() as $fieldName => $fieldRules) {
            // Skip fields whose UiSchema rule makes them inactive given the
            // current submitted data. Inactive fields are not validated and
            // not included in the processed result.
            $field = $form->getField($fieldName);
            if ($field !== null && !$this->isFieldActive($field, $data)) {
                $skippedFields[$fieldName] = true;
                continue;
            }

            $fieldValue = $data[$fieldName] ?? null;

            try {
                // Process the field value through all rules.
                $processedValue = $this->processor->process(
                    $fieldValue,
                    $fieldRules
                );
                $processedData[$fieldName] = $processedValue;
            } catch (ValidationException $e) {
                // Collect validation errors.
                $errors[$fieldName] = [$e->getMessage()];
                $isValid = false;
                // Keep original value for invalid fields.
                $processedData[$fieldName] = $fieldValue;
            } catch (Throwable $e) {
                // Handle other processing errors.
                $errors[$fieldName] = [$e->getMessage()];
                $isValid = false;
                $processedData[$fieldName] = $fieldValue;
            }
        }

        // Add any fields from data that weren't in the schema, excluding
        // fields that were intentionally skipped due to an inactive rule.
        foreach ($data as $fieldName => $fieldValue) {
            if (!isset($processedData[$fieldName]) && !isset($skippedFields[$fieldName])) {
                $processedData[$fieldName] = $fieldValue;
            }
        }

        return new ProcessResult($form, $processedData, $errors, $isValid);
    }

    // =========================================================================
    // Rule evaluation
    // =========================================================================

    /**
     * Determines whether a field is active given the current submitted data.
     *
     * A field is active when its control has no rule, or when the rule's
     * effect and condition together result in an active state:
     *
     *   SHOW    → active when condition IS met.
     *   HIDE    → active when condition is NOT met.
     *   ENABLE  → active when condition IS met.
     *   DISABLE → active when condition is NOT met (disabled = inactive).
     *
     * Inactive fields are skipped: not validated, not included in the result.
     *
     * @param FormFieldInterface $field The field to evaluate.
     * @param array $data The full submitted data array.
     * @return bool True if the field should be processed, false if it must be skipped.
     */
    private function isFieldActive(FormFieldInterface $field, array $data): bool
    {
        $rule = $field->getControl()->getRule();

        if ($rule === null) {
            return true;
        }

        $conditionMet = $this->evaluateCompositeCondition($rule->getCondition(), $data);

        return match ($rule->getEffect()) {
            UiSchemaRuleEffect::SHOW    => $conditionMet,
            UiSchemaRuleEffect::HIDE    => !$conditionMet,
            UiSchemaRuleEffect::ENABLE  => $conditionMet,
            UiSchemaRuleEffect::DISABLE => !$conditionMet,
        };
    }

    /**
     * Evaluates a composite condition recursively against the submitted data.
     *
     * AND → all child conditions must be satisfied.
     * OR  → at least one child condition must be satisfied.
     *
     * @param UiSchemaCompositeConditionInterface $condition The composite condition to evaluate.
     * @param array $data The full submitted data array.
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
     * Evaluates a simple condition against the submitted data.
     *
     * Extracts the field value identified by the condition scope and tests it
     * against the condition's JSON Schema fragment.
     *
     * @param UiSchemaConditionInterface $condition The simple condition to evaluate.
     * @param array $data The full submitted data array.
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
     * Evaluates a JSON Schema fragment against a submitted field value.
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
     *                           An invalid PCRE pattern throws a LogicException
     *                           immediately, as it signals a misconfigured form
     *                           definition (programming error).
     *
     * @param array $schema The JSON Schema fragment from the condition.
     * @param mixed $value  The submitted value of the condition field.
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
