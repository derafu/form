<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Contract\Processor;

use Derafu\Form\Contract\FormInterface;

/**
 * Interface for form data processing results.
 *
 * This interface defines the contract for objects that encapsulate the result
 * of processing form data, including processed data, validation errors, and
 * validation status.
 */
interface ProcessResultInterface
{
    /**
     * Get the processed data.
     *
     * @return mixed The processed data after applying all rules
     */
    public function getProcessedData(): mixed;

    /**
     * Get validation errors by field name.
     *
     * @return array<string, string[]> Validation errors by field name
     */
    public function getErrors(): array;

    /**
     * Check if the data is valid.
     *
     * @return bool True if the data passed all validations, false otherwise
     */
    public function isValid(): bool;

    /**
     * Check if there are any errors.
     *
     * @return bool True if there are validation errors, false otherwise
     */
    public function hasErrors(): bool;

    /**
     * Get the errors of the form as a whole: the ones that do not belong to a
     * field (for example a CSRF token that is not valid).
     *
     * @return string[]
     */
    public function getFormErrors(): array;

    /**
     * Get all error messages as a flat array.
     *
     * @return string[] All error messages from all fields
     */
    public function getAllErrors(): array;

    /**
     * Get errors for a specific field.
     *
     * @param string $fieldName The name of the field
     * @return string[] Error messages for the specified field
     */
    public function getFieldErrors(string $fieldName): array;

    /**
     * Check if a specific field has errors.
     *
     * @param string $fieldName The name of the field
     * @return bool True if the field has errors, false otherwise
     */
    public function hasFieldErrors(string $fieldName): bool;

    /**
     * Get the form with processed data.
     *
     * Returns a new form instance with the processed data (validated,
     * sanitized, casted, transformed) using the form's withData() method.
     *
     * @return FormInterface The form with processed data.
     */
    public function getForm(): FormInterface;
}
