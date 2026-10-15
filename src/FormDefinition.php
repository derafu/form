<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form;

use Derafu\Support\ArrayReader;
use Derafu\Support\ArrayType;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;

/**
 * Immutable definition of a form, as it is written in a file or in an array.
 *
 * It is the validated form of the array that a form is created from: each key
 * of the first level is checked and typed when the object is created, so who
 * reads it can trust its properties instead of the shape of the array. What is
 * inside of the schema, of the UI schema and of the rules is checked when the
 * objects that are made of them are created.
 *
 * The keys that it does not declare are ignored. A key whose value is `null` is
 * taken as absent.
 */
final readonly class FormDefinition
{
    /**
     * @param array<string, mixed> $schema The JSON schema of the data (empty
     * if there is none).
     * @param array<string, mixed> $uischema The UI schema (empty if there is
     * none).
     * @param array<string, array<array-key, mixed>> $rules The explicit rules
     * of processing of each field.
     * @param array<string, mixed>|null $data The data of the form, if any.
     * @param array<string, mixed> $options The options of the form.
     * @param array<string, list<string>>|null $errors The errors of each field,
     * if any.
     */
    public function __construct(
        public array $schema = [],
        public array $uischema = [],
        public array $rules = [],
        public ?array $data = null,
        public array $options = [],
        public ?array $errors = null,
    ) {
    }

    /**
     * Creates a definition from a raw array, validating the type of each key.
     *
     * @param array<string, mixed> $definition
     * @throws InvalidArgumentException If a known key has a wrong type.
     */
    public static function fromArray(array $definition): self
    {
        $reader = ArrayReader::from($definition);

        return new self(
            schema: $reader->map('schema') ?? [],
            uischema: $reader->map('uischema') ?? [],
            rules: $reader->mapOf('rules', ArrayType::array()) ?? [],
            data: $reader->map('data'),
            options: $reader->map('options') ?? [],
            errors: $reader->mapOf('errors', ArrayType::listOf(ArrayType::string())),
        );
    }

    /**
     * Returns the definition as an array.
     *
     * Only the keys that are defined are included, in this order: `schema`,
     * `uischema`, `rules`, `data`, `options` and `errors`. The ones that are
     * empty are not included, except the data, which is when it is `null`.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = [
            'schema' => $this->schema !== [] ? $this->schema : null,
            'uischema' => $this->uischema !== [] ? $this->uischema : null,
            'rules' => $this->rules !== [] ? $this->rules : null,
            'data' => $this->data,
            'options' => $this->options !== [] ? $this->options : null,
            'errors' => $this->errors,
        ];

        return array_filter($array, static fn (mixed $value): bool => $value !== null);
    }

    /**
     * Returns the definition with another schema.
     *
     * @param array<string, mixed> $schema
     */
    public function withSchema(array $schema): self
    {
        return new self($schema, $this->uischema, $this->rules, $this->data, $this->options, $this->errors);
    }

    /**
     * Returns the definition with another UI schema.
     *
     * @param array<string, mixed> $uischema
     */
    public function withUiSchema(array $uischema): self
    {
        return new self($this->schema, $uischema, $this->rules, $this->data, $this->options, $this->errors);
    }

    /**
     * Returns the definition with other data.
     *
     * @param array<string, mixed>|null $data
     */
    public function withData(?array $data): self
    {
        return new self($this->schema, $this->uischema, $this->rules, $data, $this->options, $this->errors);
    }
}
