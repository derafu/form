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

use Derafu\Form\Contract\FormFieldInterface;
use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\Processor\SchemaToRulesMapperInterface;

/**
 * Maps Derafu Form definitions to Derafu Data Processor rules.
 *
 * This class provides comprehensive mapping from form schema and UI schema
 * definitions to Derafu Data Processor rules, considering both data validation
 * requirements and UI-specific processing needs.
 */
final class SchemaToRulesMapper implements SchemaToRulesMapperInterface
{
    /**
     * {@inheritDoc}
     */
    public function mapFormToRules(FormInterface $form): array
    {
        $fieldRules = [];
        $requiredFields = $form->getSchema()->getRequired();

        foreach ($form->getFields() as $fieldName => $field) {
            $fieldRules[$fieldName] = $this->mapFieldToRules($field);

            // Check if this field is required and prepend the validation rule
            // so it fires before type-specific validators (e.g. 'file', 'image')
            // and produces a meaningful "required" error rather than
            // "invalid file format" when no value is submitted.
            if (in_array($fieldName, $requiredFields)) {
                if (!isset($fieldRules[$fieldName]['validate'])) {
                    $fieldRules[$fieldName]['validate'] = [];
                }
                array_unshift($fieldRules[$fieldName]['validate'], 'required');
            }
        }

        return $fieldRules;
    }

    /**
     * {@inheritDoc}
     */
    public function mapFieldToRules(FormFieldInterface $field): array
    {
        $propertySchema = $field->getProperty()->toArray();
        $controlOptions = $field->getControl()->getOptions();

        // Upload controls (file, image) carry an array value ($_FILES-style or
        // PSR-7 UploadedFileInterface), never a plain string. Applying cast or
        // sanitize rules designed for strings would throw a CastingException.
        // Their only relevant processing is file/image validation.
        $uploadControls = ['file', 'image'];
        if (in_array($controlOptions['type'] ?? null, $uploadControls, true)) {
            $uiValidateRules = $this->mapControlOptionsToValidateRules($controlOptions);
            return empty($uiValidateRules) ? [] : ['validate' => $uiValidateRules];
        }

        // Start with schema-based rules.
        $rules = $this->mapSchemaToRules($propertySchema);

        // Apply UI-specific transformations based on control options.
        $uiTransformRules = $this->mapControlOptionsToTransformRules(
            $controlOptions
        );
        if (!empty($uiTransformRules)) {
            $rules['transform'] = array_merge(
                $rules['transform'] ?? [],
                $uiTransformRules
            );
        }

        // Apply UI-specific validations.
        $uiValidateRules = $this->mapControlOptionsToValidateRules(
            $controlOptions
        );
        if (!empty($uiValidateRules)) {
            $rules['validate'] = array_merge(
                $rules['validate'] ?? [],
                $uiValidateRules
            );
        }

        return $rules;
    }

    /**
     * {@inheritDoc}
     */
    public function mapSchemaToRules(array $propertySchema): array
    {
        $rules = [];

        // Casting rules (only for supported types).
        if (
            isset($propertySchema['type'])
            && $this->isTypeSupportedForCasting($propertySchema['type'])
        ) {
            $rules['cast'] = $this->mapTypeToCastRule($propertySchema['type']);
        }

        // Sanitization rules.
        $sanitizeRules = $this->mapToSanitizeRules($propertySchema);
        if (!empty($sanitizeRules)) {
            $rules['sanitize'] = $sanitizeRules;
        }

        // Transformation rules.
        $transformRules = $this->mapToTransformRules($propertySchema);
        if (!empty($transformRules)) {
            $rules['transform'] = $transformRules;
        }

        // Validation rules.
        $validateRules = $this->mapToValidateRules($propertySchema);
        if (!empty($validateRules)) {
            $rules['validate'] = $validateRules;
        }

        return $rules;
    }

    /**
     * Checks if the type is supported for casting.
     *
     * @param string $type The type to check.
     * @return bool True if the type is supported for casting, false otherwise.
     */
    private function isTypeSupportedForCasting(string $type): bool
    {
        return in_array($type, ['string', 'integer', 'number', 'boolean']);
    }

    /**
     * Maps a type to a Derafu Data Processor cast rule.
     *
     * @param string $type The type to map.
     * @return string The Derafu Data Processor cast rule.
     */
    private function mapTypeToCastRule(string $type): string
    {
        return match ($type) {
            'string' => 'string',
            'integer' => 'integer',
            'number' => 'float',
            'boolean' => 'boolean',
            default => 'string',
        };
    }

    /**
     * Maps a property schema to Derafu Data Processor sanitize rules.
     *
     * @param array $propertySchema The property schema to map.
     * @return array The Derafu Data Processor sanitize rules.
     */
    private function mapToSanitizeRules(array $propertySchema): array
    {
        $rules = [];

        // String-specific sanitization.
        if (($propertySchema['type'] ?? '') === 'string') {
            $rules[] = 'trim';
        }

        return $rules;
    }

    /**
     * Maps a property schema to Derafu Data Processor transform rules.
     *
     * @param array $propertySchema The property schema to map.
     * @return array The Derafu Data Processor transform rules.
     */
    private function mapToTransformRules(array $propertySchema): array
    {
        $rules = [];

        // String-specific transformations.
        if (($propertySchema['type'] ?? '') === 'string') {
            // Email format - lowercase.
            if (($propertySchema['format'] ?? '') === 'email') {
                $rules[] = 'lowercase';
            }

            // Enum values - lowercase for case-insensitive comparison.
            if (isset($propertySchema['enum'])) {
                $rules[] = 'lowercase';
            }
        }

        return $rules;
    }

    /**
     * Maps a property schema to Derafu Data Processor validate rules.
     *
     * @param array $propertySchema The property schema to map.
     * @return array The Derafu Data Processor validate rules.
     */
    private function mapToValidateRules(array $propertySchema): array
    {
        $rules = [];

        // Required validation.
        if (
            isset($propertySchema['required'])
            && $propertySchema['required'] === true
        ) {
            $rules[] = 'required';
        }

        // String validations.
        if (($propertySchema['type'] ?? '') === 'string') {
            if (isset($propertySchema['minLength'])) {
                $rules[] = "min_length:{$propertySchema['minLength']}";
            }

            if (isset($propertySchema['maxLength'])) {
                $rules[] = "max_length:{$propertySchema['maxLength']}";
            }

            // Format validations.
            if (isset($propertySchema['format'])) {
                $rules[] = $this->mapFormatToValidationRule($propertySchema['format']);
            }

            // Pattern validation.
            if (isset($propertySchema['pattern'])) {
                $rules[] = "regex:{$propertySchema['pattern']}";
            }

            // Enum validation.
            if (isset($propertySchema['enum'])) {
                $enumKeys = [];
                $isSequentialEnum = array_is_list($propertySchema['enum']);
                foreach ($propertySchema['enum'] as $enumKey => $enumValue) {
                    $enumKeys[] = $isSequentialEnum ? $enumValue : $enumKey;
                }
                $enumValues = array_map('strtolower', $enumKeys);
                $enumValues = implode(',', $enumValues);
                $rules[] = "in:{$enumValues}";
            }

            // Additional string validations based on common patterns.
            if (isset($propertySchema['contentMediaType'])) {
                $rules[] = $this->mapContentMediaTypeToValidationRule($propertySchema['contentMediaType']);
            }
        }

        // Numeric validations.
        if (in_array($propertySchema['type'] ?? '', ['integer', 'number'])) {
            // Add type validation
            if (($propertySchema['type'] ?? '') === 'integer') {
                $rules[] = 'int';
            } else {
                $rules[] = 'numeric';
            }

            if (isset($propertySchema['minimum'])) {
                $rules[] = "gte:{$propertySchema['minimum']}";
            }

            if (isset($propertySchema['maximum'])) {
                $rules[] = "lte:{$propertySchema['maximum']}";
            }
        }

        // Array validations.
        if (($propertySchema['type'] ?? '') === 'array') {
            if (isset($propertySchema['minItems'])) {
                $rules[] = "min_items:{$propertySchema['minItems']}";
            }

            if (isset($propertySchema['maxItems'])) {
                $rules[] = "max_items:{$propertySchema['maxItems']}";
            }

            if (isset($propertySchema['uniqueItems']) && $propertySchema['uniqueItems'] === true) {
                $rules[] = 'unique';
            }
        }

        return $rules;
    }

    /**
     * Maps control options to transformation rules.
     *
     * This method analyzes UI control options to determine if additional
     * transformations should be applied to the data.
     *
     * @param array $controlOptions The control options from the UI schema.
     * @return array The transformation rules to apply.
     */
    private function mapControlOptionsToTransformRules(array $controlOptions): array
    {
        $rules = [];

        // Handle specific control types that require transformations.
        $controlType = $controlOptions['type'] ?? null;

        switch ($controlType) {
            case 'password':
                // Passwords might need special handling.
                break;
            case 'file':
                // File uploads might need validation.
                break;
            case 'editor':
                // Rich text editors might need HTML sanitization.
                $rules[] = 'strip_tags';
                break;
        }

        return $rules;
    }

    /**
     * Maps control options to validation rules.
     *
     * This method analyzes UI control options to determine if additional
     * validations should be applied to the data.
     *
     * @param array $controlOptions The control options from the UI schema.
     * @return array The validation rules to apply.
     */
    private function mapControlOptionsToValidateRules(array $controlOptions): array
    {
        $rules = [];

        // Handle specific control types that require additional validation.
        $controlType = $controlOptions['type'] ?? null;

        switch ($controlType) {
            case 'file':
                // File uploads need file validation.
                $rules[] = 'file';
                break;
            case 'image':
                // Image uploads need image validation.
                $rules[] = 'image';
                break;
        }

        return $rules;
    }

    /**
     * Maps a format string to its corresponding validation rule.
     *
     * This method converts JSON Schema format specifications to their
     * equivalent Derafu Data Processor validation rules.
     *
     * @param string $format The format specification (e.g., 'email', 'date').
     * @return string The corresponding validation rule name.
     */
    private function mapFormatToValidationRule(string $format): string
    {
        return match ($format) {
            'email' => 'email',
            'uri' => 'url',
            'url' => 'url',
            'date' => 'date_format:Y-m-d',
            'date-time' => 'date_format:Y-m-d H:i:s',
            'time' => 'date_format:H:i:s',
            'tel' => 'regex:/^[\+]?[0-9\s\-\(\)]+$/',
            'hostname' => 'hostname',
            'ipv4' => 'ip',
            'ipv6' => 'ip',
            'uuid' => 'uuid',
            'base64' => 'base64',
            'json' => 'json',
            default => 'string',
        };
    }

    /**
     * Maps content media type to validation rule.
     *
     * This method converts JSON Schema contentMediaType specifications to their
     * equivalent Derafu Data Processor validation rules.
     *
     * @param string $contentMediaType The content media type (e.g., 'application/json').
     * @return string The corresponding validation rule name.
     */
    private function mapContentMediaTypeToValidationRule(string $contentMediaType): string
    {
        return match ($contentMediaType) {
            'application/json' => 'json',
            'text/json' => 'json',
            default => 'string',
        };
    }
}
