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
use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\Processor\FormDataProcessorInterface;
use Derafu\Form\Contract\Processor\SchemaToRulesMapperInterface;
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
     * @param SchemaToRulesMapperInterface $mapper The mapper to convert form
     * definitions to rules of Derafu\DataProcessor
     * @param ProcessorInterface $processor The processor to process the data
     * using Derafu\DataProcessor
     */
    public function __construct(
        private readonly SchemaToRulesMapperInterface $mapper,
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

        // Map form to rules.
        $rules = $this->mapper->mapFormToRules($form);

        // Process each field.
        foreach ($rules as $fieldName => $fieldRules) {
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

        // Add any fields from data that weren't in the schema.
        foreach ($data as $fieldName => $fieldValue) {
            if (!isset($processedData[$fieldName])) {
                $processedData[$fieldName] = $fieldValue;
            }
        }

        return new ProcessResult($form, $processedData, $errors, $isValid);
    }
}
