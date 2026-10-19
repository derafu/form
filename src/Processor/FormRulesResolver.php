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

use Derafu\DataProcessor\RuleSet;
use Derafu\Form\Contract\FormFieldInterface;
use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\Processor\FormRulesResolverInterface;
use Derafu\Form\Contract\Rules\FormRulesInterface;
use Derafu\Form\Contract\Schema\ArraySchemaInterface;
use Derafu\Form\Contract\Schema\BooleanSchemaInterface;
use Derafu\Form\Contract\Schema\IntegerSchemaInterface;
use Derafu\Form\Contract\Schema\NumberSchemaInterface;
use Derafu\Form\Contract\Schema\PropertySchemaInterface;
use Derafu\Form\Contract\Schema\StringSchemaInterface;
use Derafu\Form\Contract\UiSchema\ControlInterface;
use Derafu\Form\Schema\Pattern;
use Derafu\Support\File;

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
 *
 * Note: 'required' is a property of the parent ObjectSchema (getRequired()),
 * not of a leaf property. It is injected by resolveFormToRules() and must not
 * be derived from the property object itself.
 */
final class FormRulesResolver implements FormRulesResolverInterface
{
    /**
     * {@inheritDoc}
     */
    public function resolve(FormInterface $form): void
    {
        $form->getRules()->fill(array_map(
            static fn (RuleSet $rules): array => $rules->toArray(),
            $this->resolveFormToRules($form)
        ));
    }

    /**
     * Maps a property schema object to Derafu Data Processor rules.
     *
     * This is a pure function — it depends only on the property object and has
     * no side effects. Exposed as a public method for direct use and unit testing.
     *
     * Note: 'required' is not derived here. It lives on the parent ObjectSchema
     * and is injected at the form level by resolveFormToRules().
     *
     * @param PropertySchemaInterface $property The JSON Schema property object.
     * @return array{transform?: list<string>, sanitize?: list<string>, cast?: string, validate?: list<string>} The Data Processor rules array (cast, sanitize, transform, validate).
     */
    public function mapSchemaToRules(PropertySchemaInterface $property): array
    {
        return $this->deriveRules($property)->toArray();
    }

    /**
     * Derives the rules of a property from its schema.
     *
     * @param PropertySchemaInterface $property The JSON Schema property object.
     * @return RuleSet The rules (the casting ones only for the supported types).
     */
    private function deriveRules(PropertySchemaInterface $property): RuleSet
    {
        $type = $property->getType();

        return new RuleSet(
            cast: $this->isTypeSupportedForCasting($type) ? $this->mapTypeToCastRule($type) : null,
            transform: $this->derivesTransformRules($property),
            sanitize: $this->derivesSanitizeRules($property),
            validate: $this->derivesValidateRules($property),
        );
    }

    // =========================================================================
    // Private resolution logic
    // =========================================================================

    /**
     * Resolves all field rules for the form and returns the rules used
     * internally by resolve() to build the FormRulesInterface.
     *
     * @param FormInterface $form
     * @return array<string, RuleSet>
     */
    private function resolveFormToRules(FormInterface $form): array
    {
        $fieldRules = [];
        // What the definition declares: the rules that are in the form now may
        // be the ones that a previous resolution gave.
        $explicitRules = $form->getRules()->toArray();

        foreach ($form->getFields() as $fieldName => $field) {
            $rules = $this->resolveFieldToRules(
                $field,
                RuleSet::fromArray($explicitRules[$fieldName] ?? [])
            );

            // Ensure 'required' fires first and appears exactly once for
            // required fields, regardless of what additional rules may have
            // added. This produces a meaningful "required" error rather than
            // "invalid file format" when no value is submitted.
            if ($field->isRequired()) {
                $validate = array_values(
                    array_filter($rules->validate, fn (string $r) => $r !== 'required')
                );
                array_unshift($validate, 'required');
                $rules = new RuleSet($rules->cast, $rules->transform, $rules->sanitize, $validate);
            }

            $fieldRules[$fieldName] = $rules;
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
     * @param RuleSet $additionalRules Extra rules from the form's 'rules' section.
     * @return RuleSet The complete Data Processor rules for this field.
     */
    private function resolveFieldToRules(FormFieldInterface $field, RuleSet $additionalRules): RuleSet
    {
        $property = $field->getProperty();
        $control = $field->getControl();

        // Upload controls (file, image) carry an array value ($_FILES-style or
        // PSR-7 UploadedFileInterface), never a plain string. Applying cast or
        // sanitize rules designed for strings would throw a CastingException.
        // Their only relevant processing is file/image validation.
        $uploadControls = ['file', 'image'];
        if (in_array($control->getControlType(), $uploadControls, true)) {
            return $this->resolveUploadRules($control, $additionalRules);
        }

        // Start with schema-based rules.
        $rules = $this->deriveRules($property);

        // Apply UI-specific transformations and validations based on control
        // options.
        // The control of a password does not trim it either.
        $sanitize = $control->getControlType() === 'password'
            ? array_values(array_diff($rules->sanitize, ['trim']))
            : $rules->sanitize
        ;
        $rules = new RuleSet(
            cast: $rules->cast,
            transform: array_merge($rules->transform, $this->derivesControlTransformRules($control)),
            sanitize: $sanitize,
            validate: array_values(array_unique(array_merge(
                $rules->validate,
                $this->derivesControlValidateRules($control)
            ))),
        );

        // Merge explicit rules from the form's 'rules' section on top.
        return $this->mergeAdditionalRules($rules, $additionalRules);
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
     * @param RuleSet $rules The rules derived from schema and UI schema.
     * @param RuleSet $additionalRules The extra rules from the form's 'rules' section.
     * @return RuleSet The merged rules.
     */
    private function mergeAdditionalRules(RuleSet $rules, RuleSet $additionalRules): RuleSet
    {
        // cast: explicit replaces derived entirely. When the cast type changes,
        // the derived sanitizers are also cleared because they were derived for
        // the original schema type (e.g. 'trim' is only safe on strings; after
        // casting to integer it would cause a SanitizationException). Any
        // explicit sanitizers from the 'rules' section are added back below.
        $cast = $rules->cast;
        $sanitize = $rules->sanitize;
        if ($additionalRules->cast !== null) {
            $cast = $additionalRules->cast;
            if ($rules->cast !== $cast) {
                $sanitize = [];
            }
        }

        // sanitize, transform, validate: explicit appended after derived.
        return new RuleSet(
            cast: $cast,
            transform: array_merge($rules->transform, $additionalRules->transform),
            sanitize: array_merge($sanitize, $additionalRules->sanitize),
            validate: array_merge($rules->validate, $additionalRules->validate),
        );
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
     * @param PropertySchemaInterface $property
     * @return list<string>
     */
    private function derivesSanitizeRules(PropertySchemaInterface $property): array
    {
        $rules = [];

        // A password is not trimmed: its spaces at the ends are part of it.
        if ($property instanceof StringSchemaInterface && $property->getFormat() !== 'password') {
            $rules[] = 'trim';
        }

        return $rules;
    }

    /**
     * @param PropertySchemaInterface $property
     * @return list<string>
     */
    private function derivesTransformRules(PropertySchemaInterface $property): array
    {
        $rules = [];

        if ($property instanceof StringSchemaInterface && $property->getFormat() === 'email') {
            $rules[] = 'lowercase';
        }

        return $rules;
    }

    /**
     * @param PropertySchemaInterface $property
     * @return list<string>
     */
    private function derivesValidateRules(PropertySchemaInterface $property): array
    {
        $rules = [];

        // String validations.
        if ($property instanceof StringSchemaInterface) {
            if ($property->getMinLength() !== null) {
                $rules[] = "min_length:{$property->getMinLength()}";
            }

            if ($property->getMaxLength() !== null) {
                $rules[] = "max_length:{$property->getMaxLength()}";
            }

            $formatRule = $property->getFormat() !== null
                ? $this->mapFormatToValidationRule($property->getFormat())
                : null
            ;
            if ($formatRule !== null) {
                $rules[] = $formatRule;
            }

            if ($property->getPattern() !== null) {
                // JSON Schema patterns are ECMA (no delimiters). The DataProcessor's
                // RegexRule uses preg_match(), which requires PCRE delimiters.
                $rules[] = 'regex:' . Pattern::toRegex($property->getPattern());
            }

            $mediaTypeRule = $property->getContentMediaType() !== null
                ? $this->mapContentMediaTypeToValidationRule($property->getContentMediaType())
                : null
            ;
            if ($mediaTypeRule !== null) {
                $rules[] = $mediaTypeRule;
            }
        }

        // choices validation: derives allowed values from oneOf (const+title pairs)
        // or enum (plain list). getChoices() normalises both sources to [value => label].
        // A boolean has no choices to list: it is fixed to `true` or to `false`
        // with `const` or with an `enum` of that one value (the terms of a
        // service, for example, with `const: true`).
        if ($property instanceof BooleanSchemaInterface) {
            if ($property->getConst() === true || $property->getEnum() === [true]) {
                $rules[] = 'accepted';
            } elseif ($property->getConst() === false || $property->getEnum() === [false]) {
                $rules[] = 'declined';
            }
        } else {
            $choices = $property->getChoices();
            $const = $property->getConst();
            if ($choices !== null) {
                $rules[] = $this->inRule(array_keys($choices));
            } elseif (is_string($const) || is_int($const) || is_float($const)) {
                $rules[] = $this->inRule([$const]);
            }
        }

        // Integer validations.
        if ($property instanceof IntegerSchemaInterface) {
            $rules[] = 'int';

            if ($property->getMinimum() !== null) {
                $rules[] = "gte:{$property->getMinimum()}";
            }

            if ($property->getMaximum() !== null) {
                $rules[] = "lte:{$property->getMaximum()}";
            }

            array_push($rules, ...$this->derivesNumericLimitRules($property));
        }

        // Number (float) validations.
        if ($property instanceof NumberSchemaInterface) {
            $rules[] = 'numeric';

            if ($property->getMinimum() !== null) {
                $rules[] = "gte:{$property->getMinimum()}";
            }

            if ($property->getMaximum() !== null) {
                $rules[] = "lte:{$property->getMaximum()}";
            }

            array_push($rules, ...$this->derivesNumericLimitRules($property));
        }

        // Array validations.
        if ($property instanceof ArraySchemaInterface) {
            if ($property->getMinItems() !== null) {
                $rules[] = "min_items:{$property->getMinItems()}";
            }

            if ($property->getMaxItems() !== null) {
                $rules[] = "max_items:{$property->getMaxItems()}";
            }

            if ($property->needUniqueItems() === true) {
                $rules[] = 'unique';
            }

            // The elements must be some of the choices (a multiple selection).
            $itemChoices = $property->getItems()?->getChoices();
            if ($itemChoices !== null) {
                $rules[] = $this->inRule(array_keys($itemChoices));
            }

            array_push($rules, ...$this->derivesContainsRules($property));
        }

        return $rules;
    }

    /**
     * The rules of `contains`, `minContains` and `maxContains`.
     *
     * Only a `contains` with `const` or `enum` can be derived, because the
     * rules compare the elements with a list of values. Without `minContains`
     * at least one element must match; `minContains: 0` allows none.
     *
     * @param ArraySchemaInterface $property
     * @return list<string>
     */
    private function derivesContainsRules(ArraySchemaInterface $property): array
    {
        $values = $this->containsValues($property->getContains());
        if ($values === null) {
            return [];
        }

        $list = $this->escapedList($values);
        $rules = [];

        $min = $property->getMinContains();
        if ($min === null) {
            $rules[] = "contains:{$list}";
        } elseif ($min > 0) {
            $rules[] = "min_contains:{$min},{$list}";
        }

        $max = $property->getMaxContains();
        if ($max !== null) {
            $rules[] = "max_contains:{$max},{$list}";
        }

        return $rules;
    }

    /**
     * The values of a `contains` schema, when it is a `const` or an `enum`.
     *
     * @param array<string, mixed>|null $contains
     * @return list<string|int|float>|null Null if the values can not be compared.
     */
    private function containsValues(?array $contains): ?array
    {
        if ($contains === null) {
            return null;
        }

        if (array_key_exists('const', $contains)) {
            $values = [$contains['const']];
        } elseif (isset($contains['enum']) && is_array($contains['enum'])) {
            $values = array_values($contains['enum']);
        } else {
            return null;
        }

        // The rules never match a boolean, null or array, and an empty text
        // is not a parameter.
        foreach ($values as $value) {
            if ((!is_string($value) && !is_int($value) && !is_float($value)) || $value === '') {
                return null;
            }
        }

        return $values !== [] ? $values : null;
    }

    /**
     * The limits that exclude their value and the multiple of a number.
     *
     * @param IntegerSchemaInterface|NumberSchemaInterface $property
     * @return list<string>
     */
    private function derivesNumericLimitRules(IntegerSchemaInterface|NumberSchemaInterface $property): array
    {
        $rules = [];

        if ($property->getExclusiveMinimum() !== null) {
            $rules[] = "gt:{$property->getExclusiveMinimum()}";
        }

        if ($property->getExclusiveMaximum() !== null) {
            $rules[] = "lt:{$property->getExclusiveMaximum()}";
        }

        if ($property->getMultipleOf() !== null) {
            $rules[] = "multiple_of:{$property->getMultipleOf()}";
        }

        return $rules;
    }

    /**
     * The rule `in` with some choices.
     *
     * A comma that is in a choice is escaped, because it would split it.
     *
     * @param list<string|int|float> $choices The values that are valid.
     * @return string The rule.
     */
    private function inRule(array $choices): string
    {
        return 'in:' . $this->escapedList($choices);
    }

    /**
     * The values as the parameters of a rule.
     *
     * @param list<string|int|float> $values
     * @return string The values separated by commas.
     */
    private function escapedList(array $values): string
    {
        return implode(',', array_map(
            static fn (string|int|float $value): string => str_replace(',', '\\,', (string) $value),
            $values
        ));
    }

    // =========================================================================
    // UI control derivation helpers
    // =========================================================================

    /**
     * @param ControlInterface $control The UI control element.
     * @return list<string>
     */
    private function derivesControlTransformRules(ControlInterface $control): array
    {
        $rules = [];

        switch ($control->getControlType()) {
            case 'editor':
                $rules[] = 'strip_tags';
                break;
        }

        return $rules;
    }

    /**
     * The rules of the controls that are not an upload, from their type.
     *
     * @param ControlInterface $control The UI control element.
     * @return list<string>
     */
    private function derivesControlValidateRules(ControlInterface $control): array
    {
        $rule = match ($control->getControlType()) {
            'ipv4' => 'ip:v4',
            'ipv6' => 'ip:v6',
            'hostname', 'uuid', 'url', 'time', 'color', 'month', 'week' => $control->getControlType(),
            default => null,
        };

        return $rule !== null ? [$rule] : [];
    }

    /**
     * Resolves the rules of an upload control (`file` or `image`).
     *
     * The rule of the control has the size limits that the control says. The
     * types of files are the union of the ones that the control offers
     * (`accept`) and the ones of the rule `mimetype` that is written in the
     * `rules` of the form, and an empty file has the type of an empty file if
     * the control accepts it (`min_size` is 0).
     *
     * @param ControlInterface $control The upload control.
     * @param RuleSet $additionalRules The extra rules from the form's 'rules' section.
     * @return RuleSet The rules of the field.
     */
    private function resolveUploadRules(ControlInterface $control, RuleSet $additionalRules): RuleSet
    {
        $type = (string) $control->getControlType();
        $options = $control->getOptions();

        $parameters = [];
        foreach (['max_size', 'min_size'] as $option) {
            $size = $options[$option] ?? null;
            if (is_int($size) || is_string($size)) {
                $parameters[] = $option . '=' . $size;
            }
        }
        $validate = [$parameters === [] ? $type : $type . ':' . implode(',', $parameters)];

        // The `mimetype` that was written is not another rule: its types are
        // joined with the ones of `accept`.
        $types = $this->derivesTypesFromAccept($options['accept'] ?? null);
        $explicit = [];
        foreach ($additionalRules->validate as $rule) {
            $written = explode(':', $rule, 2);
            $writtenTypes = $written[0] === 'mimetype'
                ? array_filter(
                    array_map('trim', explode(',', $written[1] ?? '')),
                    static fn (string $mimetype): bool => $mimetype !== ''
                )
                : []
            ;

            // A rule that has no types is left as it is, to fail as it is.
            if ($writtenTypes === []) {
                $explicit[] = $rule;
                continue;
            }

            array_push($types, ...$writtenTypes);
        }
        $types = array_values(array_unique($types));

        if ($types !== []) {
            $minSize = $options['min_size'] ?? null;
            if ((is_int($minSize) || is_string($minSize)) && File::parseSize((string) $minSize) === 0) {
                $types[] = 'application/x-empty';
            }
            $validate[] = 'mimetype:' . implode(',', array_unique($types));
        }

        return $this->mergeAdditionalRules(
            new RuleSet(validate: $validate),
            new RuleSet(
                cast: $additionalRules->cast,
                transform: $additionalRules->transform,
                sanitize: $additionalRules->sanitize,
                validate: $explicit,
            )
        );
    }

    /**
     * Derives the MIME types that a file control receives from its `accept`.
     *
     * Each element of `accept` is a MIME type, a whole type (`image/*`, that the
     * rule `mimetype` understands), or an extension with the types that it is
     * known to have. If one of them is none of those (an extension that is not
     * known, or a wildcard that is not `type/*`), nothing is derived, because the types of the
     * rest would be fewer than the ones that the control offers.
     *
     * @param mixed $accept The option `accept` of the control.
     * @return list<string> The types, or an empty list if they can not be
     * derived.
     */
    private function derivesTypesFromAccept(mixed $accept): array
    {
        if (!is_string($accept)) {
            return [];
        }

        $types = [];
        foreach (explode(',', $accept) as $element) {
            $element = strtolower(trim($element));
            if ($element === '') {
                continue;
            }

            if (str_starts_with($element, '.')) {
                $elementTypes = File::mimetypesFromExtension($element);
            } elseif (preg_match('~^[a-z0-9][a-z0-9!#$&^_.+-]*/(?:\*|[a-z0-9][a-z0-9!#$&^_.+-]*)$~', $element)) {
                $elementTypes = [$element];
            } else {
                $elementTypes = [];
            }

            if ($elementTypes === []) {
                return [];
            }

            array_push($types, ...$elementTypes);
        }

        return array_values(array_unique($types));
    }

    // =========================================================================
    // Format / media-type helpers
    // =========================================================================

    /**
     * @param string $format JSON Schema format.
     * @return string|null Data Processor validation rule, or `null` if the
     * format has no rule (as `password`, that accepts any text).
     */
    private function mapFormatToValidationRule(string $format): ?string
    {
        return match ($format) {
            'email' => 'email',
            'uri' => 'url',
            'url' => 'url',
            'date' => 'date_format:Y-m-d',
            'date-time' => 'datetime_local',
            'time' => 'time',
            'color' => 'color',
            'month' => 'month',
            'week' => 'week',
            'tel' => 'regex:/^[\+]?[0-9\s\-\(\)]+$/',
            'hostname' => 'hostname',
            'ipv4' => 'ip:v4',
            'ipv6' => 'ip:v6',
            'uuid' => 'uuid',
            'regex' => 'regex_pattern',
            'base64' => 'base64',
            'json' => 'json',
            default => null,
        };
    }

    /**
     * @param string $contentMediaType JSON Schema contentMediaType.
     * @return string|null Data Processor validation rule, or `null` if the
     * media type has no rule.
     */
    private function mapContentMediaTypeToValidationRule(string $contentMediaType): ?string
    {
        return match ($contentMediaType) {
            'application/json' => 'json',
            'text/json' => 'json',
            default => null,
        };
    }
}
