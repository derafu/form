<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Schema;

use Derafu\Form\Schema\PropertyDefinition;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PropertyDefinition::class)]
final class PropertyDefinitionTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private static function fullDefinition(): array
    {
        return [
            'type' => 'object',
            'title' => 'Title',
            'description' => 'Description',
            'deprecated' => false,
            'examples' => ['a', 1],
            'readOnly' => true,
            'writeOnly' => false,
            'enum' => ['a', 1, 1.5, true],
            'allOf' => [['type' => 'string']],
            'anyOf' => [['type' => 'string'], ['type' => 'integer']],
            'oneOf' => [['const' => 'a', 'title' => 'A']],
            'format' => 'email',
            'maxLength' => 10,
            'minLength' => 1,
            'pattern' => '^a',
            'contentMediaType' => 'text/plain',
            'minimum' => 1,
            'maximum' => 9.5,
            'exclusiveMinimum' => 0,
            'exclusiveMaximum' => 10,
            'multipleOf' => 0.5,
            'items' => ['type' => 'string'],
            'maxItems' => 5,
            'minItems' => 1,
            'contains' => ['type' => 'string'],
            'maxContains' => 2,
            'minContains' => 1,
            'uniqueItems' => true,
            'required' => ['a', 'b'],
            'dependentRequired' => ['a' => ['b', 'c']],
            'maxProperties' => 3,
            'minProperties' => 1,
            'additionalProperties' => ['type' => 'string'],
            'properties' => ['a' => ['type' => 'string']],
            '$defs' => ['d' => ['type' => 'string']],
            'definitions' => ['e' => ['type' => 'string']],
            'default' => 'x',
            'const' => 'y',
        ];
    }

    #[Test]
    public function shouldReadEveryKey(): void
    {
        $definition = PropertyDefinition::fromArray(['name' => 'field'] + self::fullDefinition());

        $this->assertSame('field', $definition->name);
        $this->assertSame('object', $definition->type);
        $this->assertSame('Title', $definition->title);
        $this->assertSame('Description', $definition->description);
        $this->assertFalse($definition->deprecated);
        $this->assertSame(['a', 1], $definition->examples);
        $this->assertTrue($definition->readOnly);
        $this->assertFalse($definition->writeOnly);
        $this->assertSame(['a', 1, 1.5, true], $definition->enum);
        $this->assertSame([['type' => 'string']], $definition->allOf);
        $this->assertSame([['type' => 'string'], ['type' => 'integer']], $definition->anyOf);
        $this->assertSame([['const' => 'a', 'title' => 'A']], $definition->oneOf);
        $this->assertSame('email', $definition->format);
        $this->assertSame(10, $definition->maxLength);
        $this->assertSame(1, $definition->minLength);
        $this->assertSame('^a', $definition->pattern);
        $this->assertSame('text/plain', $definition->contentMediaType);
        $this->assertSame(1, $definition->minimum);
        $this->assertSame(9.5, $definition->maximum);
        $this->assertSame(0, $definition->exclusiveMinimum);
        $this->assertSame(10, $definition->exclusiveMaximum);
        $this->assertSame(0.5, $definition->multipleOf);
        $this->assertSame(['type' => 'string'], $definition->items);
        $this->assertSame(5, $definition->maxItems);
        $this->assertSame(1, $definition->minItems);
        $this->assertSame(['type' => 'string'], $definition->contains);
        $this->assertSame(2, $definition->maxContains);
        $this->assertSame(1, $definition->minContains);
        $this->assertTrue($definition->uniqueItems);
        $this->assertSame(['a', 'b'], $definition->required);
        $this->assertSame(['a' => ['b', 'c']], $definition->dependentRequired);
        $this->assertSame(3, $definition->maxProperties);
        $this->assertSame(1, $definition->minProperties);
        $this->assertSame(['type' => 'string'], $definition->additionalProperties);
        $this->assertSame(['a' => ['type' => 'string']], $definition->properties);
        $this->assertSame(['d' => ['type' => 'string']], $definition->defs);
        $this->assertSame(['e' => ['type' => 'string']], $definition->definitions);
        $this->assertSame('x', $definition->default);
        $this->assertTrue($definition->hasDefault);
        $this->assertSame('y', $definition->const);
        $this->assertTrue($definition->hasConst);
    }

    #[Test]
    public function shouldAcceptAListOfTypes(): void
    {
        $definition = PropertyDefinition::fromArray(['type' => ['string', 'null']]);

        $this->assertSame(['string', 'null'], $definition->type);
        $this->assertSame(['type' => ['string', 'null']], $definition->toArray());
    }

    #[Test]
    public function shouldWriteBackWhatWasRead(): void
    {
        $this->assertSame(
            self::fullDefinition(),
            PropertyDefinition::fromArray(self::fullDefinition())->toArray()
        );
    }

    #[Test]
    public function shouldHaveNoValuesWhenTheDefinitionIsEmpty(): void
    {
        $definition = PropertyDefinition::fromArray([]);

        $this->assertSame('', $definition->name);
        $this->assertNull($definition->type);
        $this->assertFalse($definition->hasDefault);
        $this->assertFalse($definition->hasConst);
        $this->assertSame([], $definition->toArray());
    }

    #[Test]
    public function shouldTellNullFromAbsentInDefaultAndConst(): void
    {
        $definition = PropertyDefinition::fromArray(['default' => null, 'const' => null]);

        $this->assertNull($definition->default);
        $this->assertTrue($definition->hasDefault);
        $this->assertNull($definition->const);
        $this->assertTrue($definition->hasConst);
        $this->assertSame(['default' => null, 'const' => null], $definition->toArray());
    }

    #[Test]
    public function shouldTakeANullKeyAsAbsentAndIgnoreUnknownKeys(): void
    {
        $definition = PropertyDefinition::fromArray(['title' => null, 'minLength' => null, 'x-custom' => 1]);

        $this->assertNull($definition->title);
        $this->assertNull($definition->minLength);
        $this->assertSame([], $definition->toArray());
    }

    #[Test]
    public function shouldAcceptABooleanOrAnObjectInAdditionalProperties(): void
    {
        $this->assertFalse(PropertyDefinition::fromArray(['additionalProperties' => false])->additionalProperties);
        $this->assertTrue(PropertyDefinition::fromArray(['additionalProperties' => true])->additionalProperties);
        $this->assertSame(
            ['type' => 'string'],
            PropertyDefinition::fromArray(['additionalProperties' => ['type' => 'string']])->additionalProperties
        );
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function wrongDefinitionsProvider(): array
    {
        return [
            'name' => [['name' => 1], 'The key "name" must be of type string, int given.'],
            'type' => [['type' => 1], 'The key "type" must be of type string|list<string>, int given.'],
            'type with an item that is not a string' => [['type' => ['string', 1]], 'The key "type" must be of type string|list<string>, array given.'],
            'minLength as a string' => [['minLength' => '3'], 'The key "minLength" must be of type int, string given.'],
            'maxLength as a float' => [['maxLength' => 3.0], 'The key "maxLength" must be of type int, float given.'],
            'deprecated' => [['deprecated' => 'no'], 'The key "deprecated" must be of type bool, string given.'],
            'minimum' => [['minimum' => '1'], 'The key "minimum" must be of type float|int, string given.'],
            'examples' => [['examples' => ['a' => 1]], 'The key "examples" must be of type list, array given.'],
            'enum with an array' => [['enum' => ['a', ['b']]], 'The key "enum.1" must be of type bool|float|int|string, array given.'],
            'allOf with a string' => [['allOf' => ['string']], 'The key "allOf.0" must be of type object, string given.'],
            'items' => [['items' => 'string'], 'The key "items" must be of type object|bool, string given.'],
            'contains with a list' => [['contains' => ['a']], 'The key "contains" must be of type object, array given.'],
            'required with an integer' => [['required' => ['a', 1]], 'The key "required.1" must be of type string, int given.'],
            'dependentRequired' => [['dependentRequired' => ['a' => ['b', 2]]], 'The key "dependentRequired.a.1" must be of type string, int given.'],
            'additionalProperties' => [['additionalProperties' => 'yes'], 'The key "additionalProperties" must be of type bool|object, string given.'],
            'properties' => [['properties' => 'x'], 'The key "properties" must be of type object<object>, string given.'],
            'a property that is not an object' => [['properties' => ['a' => 'x']], 'The key "properties.a" must be of type object, string given.'],
            '$defs' => [['$defs' => ['a' => ['b']]], 'The key "$defs.a" must be of type object, array given.'],
            'definitions' => [['definitions' => ['a' => 1]], 'The key "definitions.a" must be of type object, int given.'],
        ];
    }

    /**
     * @param array<string, mixed> $definition
     */
    #[Test]
    #[DataProvider('wrongDefinitionsProvider')]
    public function shouldRejectAKeyOfAnotherType(array $definition, string $message): void
    {
        $this->expectException(TranslatableInvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        PropertyDefinition::fromArray($definition);
    }
}
