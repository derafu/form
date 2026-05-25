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

use Derafu\Form\Contract\FormFieldInterface;
use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\Processor\FormRulesResolverInterface;

/**
 * Resolves the complete, merged processing rules for every field in a form.
 *
 * Combines three sources per field:
 *   1. Rules derived from the JSON Schema property definition.
 *   2. Rules derived from the UI schema control options.
 *   3. Explicit rules from the form's 'rules' section, merged on top.
 *
 * Merge strategy:
 *   - cast:      explicit replaces derived (mutually exclusive).
 *   - sanitize / transform / validate: explicit appended after derived.
 *   - required:  always placed first in validate, deduplicated.
 */
final class FormRulesResolver implements FormRulesResolverInterface
{
    /**
     * {@inheritDoc}
     */
    public function resolve(FormInterface $form): void
    {
        $form->getRules()->fill($this->resolveFormToRules($form));
    }

    /**
     * Maps a property schema array to Derafu Data Processor rules.
     *
     * This is a pure function — it depends only on the schema array and has no
     * side effects. Exposed as a public method for direct use and unit testing.
     *
     * @param array $propertySchema The JSON Schema property definition.
     * @return array The Data Processor rules array (cast, sanitize, transform, validate).
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
        $sanitizeRules = $this->derivesSanitizeRules($propertySchema);
        if (!empty($sanitizeRules)) {
            $rules['sanitize'] = $sanitizeRules;
        }

        // Transformation rules.
        $transformRules = $this->derivesTransformRules($propertySchema);
        if (!empty($transformRules)) {
            $rules['transform'] = $transformRules;
        }

        // Validation rules.
        $validateRules = $this->derivesValidateRules($propertySchema);
        if (!empty($validateRules)) {
            $rules['validate'] = $validateRules;
        }

        return $rules;
    }

    // =========================================================================
    // Private resolution logic
    // =========================================================================

    /**
     * Resolves all field rules for the form and returns the raw array used
     * internally by resolve() to build the FormRulesInterface.
     *
     * @param FormInterface $form
     * @return array<string, array>
     */
    private function resolveFormToRules(FormInterface $form): array
    {
        $fieldRules = [];
        $requiredFields = $form->getSchema()->getRequired();
        $explicitRules = $form->getRules();

        foreach ($form->getFields() as $fieldName => $field) {
            $additionalRules = $explicitRules[$fieldName] ?? [];
            $fieldRules[$fieldName] = $this->resolveFieldToRules($field, $additionalRules);

            // Ensure 'required' fires first and appears exactly once for
            // required fields, regardless of what additional rules may have
            // added. This produces a meaningful "required" error rather than
            // "invalid file format" when no value is submitted.
            if (in_array($fieldName, $requiredFields)) {
                $validate = $fieldRules[$fieldName]['validate'] ?? [];
                $validate = array_values(
                    array_filter($validate, fn (string $r) => $r !== 'required')
                );
                array_unshift($validate, 'required');
                $fieldRules[$fieldName]['validate'] = $validate;
            }
        }

        return $fieldRules;
    }

    /**
     * Resolves Derafu Data Processor rules for a single form field.
     *
     * Considers both the schema property definition and the UI control
     * configuration, then merges any explicit additional rules on top.
     *
     * @param FormFieldInterface $field The form field to resolve.
     * @param array $additionalRules Extra rules from the form's 'rules' section.
     * @return array The complete Data Processor rules for this field.
     */
    private function resolveFieldToRules(FormFieldInterface $field, array $additionalRules = []): array
    {
        $propertySchema = $field->getProperty()->toArray();
        $controlOptions = $field->getControl()->getOptions();

        // Upload controls (file, image) carry an array value ($_FILES-style or
        // PSR-7 UploadedFileInterface), never a plain string. Applying cast or
        // sanitize rules designed for strings would throw a CastingException.
        // Their only relevant processing is file/image validation.
        $uploadControls = ['file', 'image'];
        if (in_array($controlOptions['type'] ?? null, $uploadControls, true)) {
            $uiValidateRules = $this->derivesControlValidateRules($controlOptions);
            $rules = empty($uiValidateRules) ? [] : ['validate' => $uiValidateRules];
            return empty($additionalRules) ? $rules : $this->mergeAdditionalRules($rules, $additionalRules);
        }

        // Start with schema-based rules.
        $rules = $this->mapSchemaToRules($propertySchema);

        // Apply UI-specific transformations based on control options.
        $uiTransformRules = $this->derivesControlTransformRules($controlOptions);
        if (!empty($uiTransformRules)) {
            $rules['transform'] = array_merge(
                $rules['transform'] ?? [],
                $uiTransformRules
            );
        }

        // Apply UI-specific validations.
        $uiValidateRules = $this->derivesControlValidateRules($controlOptions);
        if (!empty($uiValidateRules)) {
            $rules['validate'] = array_merge(
                $rules['validate'] ?? [],
                $uiValidateRules
            );
        }

        // Merge explicit rules from the form's 'rules' section on top.
        if (!empty($additionalRules)) {
            $rules = $this->mergeAdditionalRules($rules, $additionalRules);
        }

        return $rules;
    }

    /**
     * Merges explicit additional rules into the schema/UI-derived rules.
     *
     * Merge strategy per category:
     *   - cast:      explicit replaces derived (scalar, mutually exclusive).
     *   - sanitize:  explicit appended after derived (sequential pipeline).
     *   - transform: explicit appended after derived (sequential pipeline).
     *   - validate:  explicit appended after derived (all rules must pass).
     *
     * @param array $rules The rules derived from schema and UI schema.
     * @param array $additionalRules The extra rules from the form's 'rules' section.
     * @return array The merged rules.
     */
    private function mergeAdditionalRules(array $rules, array $additionalRules): array
    {
        // cast: explicit replaces derived entirely. When the cast type changes,
        // the derived sanitizers are also cleared because they were derived for
        // the original schema type (e.g. 'trim' is only safe on strings; after
        // casting to integer it would cause a SanitizationException). Any
        // explicit sanitizers from the 'rules' section are added back below.
        if (isset($additionalRules['cast'])) {
            $prevCast = $rules['cast'] ?? null;
            $rules['cast'] = $additionalRules['cast'];
            if ($prevCast !== $rules['cast']) {
                unset($rules['sanitize']);
            }
        }

        // sanitize, transform, validate: explicit appended after derived.
        foreach (['sanitize', 'transform', 'validate'] as $category) {
            if (!empty($additionalRules[$category])) {
                $rules[$category] = array_merge(
                    $rules[$category] ?? [],
                    (array) $additionalRules[$category]
                );
            }
        }

        return $rules;
    }

    // =========================================================================
    // Schema derivation helpers
    // =========================================================================

    /**
     * @param string $type JSON Schema type.
     * @return bool
     */
    private function isTypeSupportedForCasting(string $type): bool
    {
        return in_array($type, ['string', 'integer', 'number', 'boolean']);
    }

    /**
     * @param string $type JSON Schema type.
     * @return string Data Processor cast rule.
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
     * @param array $propertySchema
     * @return array
     */
    private function derivesSanitizeRules(array $propertySchema): array
    {
        $rules = [];

        if (($propertySchema['type'] ?? '') === 'string') {
            $rules[] = 'trim';
        }

        return $rules;
    }

    /**
     * @param array $propertySchema
     * @return array
     */
    private function derivesTransformRules(array $propertySchema): array
    {
        $rules = [];

        if (($propertySchema['type'] ?? '') === 'string') {
            if (($propertySchema['format'] ?? '') === 'email') {
                $rules[] = 'lowercase';
            }
        }

        return $rules;
    }

    /**
     * @param array $propertySchema
     * @return array
     */
    private function derivesValidateRules(array $propertySchema): array
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

            if (isset($propertySchema['format'])) {
                $rules[] = $this->mapFormatToValidationRule($propertySchema['format']);
            }

            if (isset($propertySchema['pattern'])) {
                $rules[] = "regex:{$propertySchema['pattern']}";
            }

            if (isset($propertySchema['enum'])) {
                $enumKeys = implode(',', array_keys($propertySchema['enum']));
                $rules[] = "in:{$enumKeys}";
            }

            if (isset($propertySchema['contentMediaType'])) {
                $rules[] = $this->mapContentMediaTypeToValidationRule($propertySchema['contentMediaType']);
            }
        }

        // Numeric validations.
        if (in_array($propertySchema['type'] ?? '', ['integer', 'number'])) {
            $rules[] = ($propertySchema['type'] === 'integer') ? 'int' : 'numeric';

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

    // =========================================================================
    // UI control derivation helpers
    // =========================================================================

    /**
     * @param array $controlOptions UI control options.
     * @return array
     */
    private function derivesControlTransformRules(array $controlOptions): array
    {
        $rules = [];

        switch ($controlOptions['type'] ?? null) {
            case 'editor':
                $rules[] = 'strip_tags';
                break;
        }

        return $rules;
    }

    /**
     * @param array $controlOptions UI control options.
     * @return array
     */
    private function derivesControlValidateRules(array $controlOptions): array
    {
        $rules = [];

        switch ($controlOptions['type'] ?? null) {
            case 'file':
                $rules[] = 'file';
                break;
            case 'image':
                $rules[] = 'image';
                break;
        }

        return $rules;
    }

    // =========================================================================
    // Format / media-type helpers
    // =========================================================================

    /**
     * @param string $format JSON Schema format.
     * @return string Data Processor validation rule.
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
     * @param string $contentMediaType JSON Schema contentMediaType.
     * @return string Data Processor validation rule.
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
