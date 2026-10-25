<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\UiSchema;

use Derafu\Array\ArrayReader;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;

/**
 * Immutable definition of an element of the UI schema.
 *
 * It is the validated form of the array that an element is created from: each
 * key is checked and typed when the object is created, so the element can
 * trust its properties instead of the shape of the array.
 *
 * The keys that it does not declare are ignored, as they are in the definition
 * of a property: what a control needs to be configured goes in `options`, and a
 * key of the root that a feature needs is declared here when it is added.
 */
final readonly class UiSchemaElementDefinition
{
    /**
     * @param string|null $type The type of the element ("Control", "Group"...).
     * @param string|null $scope The scope of the property, in a control.
     * @param string|null $label The label of the element.
     * @param array<string, mixed> $options The options of the element.
     * @param array<string, mixed>|null $rule The rule of the element, as it was
     * defined (it is turned into a rule when the element is asked for it).
     */
    public function __construct(
        public ?string $type = null,
        public ?string $scope = null,
        public ?string $label = null,
        public array $options = [],
        public ?array $rule = null,
    ) {
    }

    /**
     * Creates a definition from a raw array, validating the type of each key.
     *
     * Unknown keys are ignored. A key whose value is `null` is taken as absent.
     *
     * @param array<string, mixed> $definition
     * @throws InvalidArgumentException If a known key has a wrong type.
     */
    public static function fromArray(array $definition): self
    {
        $reader = ArrayReader::fromArray($definition);

        return new self(
            type: $reader->getString('type'),
            scope: $reader->getString('scope'),
            label: $reader->getString('label'),
            options: $reader->getMap('options') ?? [],
            rule: $reader->getMap('rule'),
        );
    }

    /**
     * Returns the definition as an array.
     *
     * Only the keys that are defined are included, in this order: `type`,
     * `scope`, `label`, `options` (if there are any) and `rule`.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = [
            'type' => $this->type,
            'scope' => $this->scope,
            'label' => $this->label,
            'options' => $this->options !== [] ? $this->options : null,
            'rule' => $this->rule,
        ];

        return array_filter($array, static fn (mixed $value): bool => $value !== null);
    }
}
