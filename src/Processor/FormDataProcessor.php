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
use Derafu\Form\Contract\Processor\UiSchemaRuleEvaluatorInterface;
use Derafu\Form\Exception\ValidationException;
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
     * @param UiSchemaRuleEvaluatorInterface $evaluator The evaluator that
     * determines whether a field is active given the submitted data and the
     * field's UI schema rule. Defaults to a plain UiSchemaRuleEvaluator so
     * that existing call sites that instantiate FormDataProcessor directly
     * (e.g., tests) do not need to change.
     */
    public function __construct(
        private readonly FormRulesResolverInterface $resolver,
        private readonly ProcessorInterface $processor,
        private readonly UiSchemaRuleEvaluatorInterface $evaluator = new UiSchemaRuleEvaluator(),
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
     * Delegates to UiSchemaRuleEvaluator. A field with no rule is always active.
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

        return $this->evaluator->isActive($rule, $data);
    }
}
