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

use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\Processor\ProcessResultInterface;
use Derafu\Form\Data\FormData;

/**
 * Result of processing form data through rules.
 */
final class ProcessResult implements ProcessResultInterface
{
    /**
     * The new form with processed data.
     */
    private FormInterface $newForm;

    /**
     * @param FormInterface $originalForm The original form that was processed.
     * @param mixed $processedData The processed data after applying all rules.
     * @param array<string, string[]> $errors Validation errors by field name.
     * @param bool $isValid Whether the data passed all validations.
     * @param string[] $formErrors Errors of the form as a whole, the ones that
     * do not belong to a field.
     */
    public function __construct(
        private readonly FormInterface $originalForm,
        private readonly mixed $processedData,
        private readonly array $errors = [],
        private readonly bool $isValid = true,
        private readonly array $formErrors = []
    ) {
    }

    /**
     * Get the processed data.
     *
     * @return mixed The processed data.
     */
    public function getProcessedData(): mixed
    {
        return $this->processedData;
    }

    /**
     * Get validation errors by field name.
     *
     * @return array<string, string[]>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if the data is valid.
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * Check if there are any errors.
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors) || !empty($this->formErrors);
    }

    /**
     * Get the errors of the form as a whole.
     *
     * @return string[]
     */
    public function getFormErrors(): array
    {
        return $this->formErrors;
    }

    /**
     * Get all error messages as a flat array.
     *
     * @return string[]
     */
    public function getAllErrors(): array
    {
        $allErrors = $this->formErrors;
        foreach ($this->errors as $fieldErrors) {
            $allErrors = array_merge($allErrors, $fieldErrors);
        }

        return $allErrors;
    }

    /**
     * Get errors for a specific field.
     *
     * @return string[]
     */
    public function getFieldErrors(string $fieldName): array
    {
        return $this->errors[$fieldName] ?? [];
    }

    /**
     * Check if a specific field has errors.
     */
    public function hasFieldErrors(string $fieldName): bool
    {
        return
            isset($this->errors[$fieldName])
            && !empty($this->errors[$fieldName])
        ;
    }

    /**
     * Get the form with processed data.
     *
     * Returns a new form instance with the processed data (validated,
     * sanitized, casted, transformed) using the form's withData() method.
     *
     * @return FormInterface The form with processed data.
     */
    public function getForm(): FormInterface
    {
        if (!isset($this->newForm)) {
            $formData = FormData::fromArray($this->processedData);
            $this->newForm = $this->originalForm->withData($formData, $this->errors, $this->formErrors);
        }
        return $this->newForm;
    }
}
