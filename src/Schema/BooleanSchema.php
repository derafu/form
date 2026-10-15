<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Schema;

use Derafu\Form\Abstract\AbstractPropertySchema;
use Derafu\Form\Contract\Schema\BooleanSchemaInterface;

/**
 * Implementation of BooleanSchemaInterface.
 *
 * This class represents a boolean schema.
 */
final class BooleanSchema extends AbstractPropertySchema implements BooleanSchemaInterface
{
    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return 'boolean';
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $definition
     */
    public static function fromArray(array $definition): static
    {
        $def = PropertyDefinition::fromArray($definition);
        $schema = new static($def->name);

        // Set common properties.
        if ($def->title !== null) {
            $schema->setTitle($def->title);
        }

        if ($def->description !== null) {
            $schema->setDescription($def->description);
        }

        if ($def->default !== null) {
            $schema->setDefault($def->default);
        }

        if ($def->deprecated !== null) {
            $schema->setDeprecated($def->deprecated);
        }

        if ($def->examples !== null) {
            $schema->setExamples($def->examples);
        }

        if ($def->readOnly !== null) {
            $schema->setReadOnly($def->readOnly);
        }

        if ($def->writeOnly !== null) {
            $schema->setWriteOnly($def->writeOnly);
        }

        if ($def->enum !== null) {
            $schema->setEnum($def->enum);
        }

        if ($def->const !== null) {
            $schema->setConst($def->const);
        }

        if ($def->allOf !== null) {
            $schema->setAllOf($def->allOf);
        }

        if ($def->anyOf !== null) {
            $schema->setAnyOf($def->anyOf);
        }

        if ($def->oneOf !== null) {
            $schema->setOneOf($def->oneOf);
        }

        return $schema;
    }
}
