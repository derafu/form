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

use Derafu\Form\Abstract\AbstractPropertySchema;
use Derafu\Form\Factory\PropertySchemaFactory;
use Derafu\Form\Schema\ArraySchema;
use Derafu\Form\Schema\BooleanSchema;
use Derafu\Form\Schema\FormSchema;
use Derafu\Form\Schema\IntegerSchema;
use Derafu\Form\Schema\NullSchema;
use Derafu\Form\Schema\NumberSchema;
use Derafu\Form\Schema\ObjectSchema;
use Derafu\Form\Schema\ObjectSchemaTrait;
use Derafu\Form\Schema\PropertyDefinition;
use Derafu\Form\Schema\StringSchema;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The schemas read their definition through PropertyDefinition: each key has the
 * type that JSON Schema says, and nothing is coerced.
 */
#[CoversClass(PropertySchemaFactory::class)]
#[CoversClass(AbstractPropertySchema::class)]
#[CoversClass(StringSchema::class)]
#[CoversClass(IntegerSchema::class)]
#[CoversClass(NumberSchema::class)]
#[CoversClass(BooleanSchema::class)]
#[CoversClass(NullSchema::class)]
#[CoversClass(ArraySchema::class)]
#[CoversClass(ObjectSchema::class)]
#[CoversClass(FormSchema::class)]
#[CoversTrait(ObjectSchemaTrait::class)]
#[CoversClass(PropertyDefinition::class)]
final class SchemaFromArrayTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function wrongDefinitionsProvider(): array
    {
        return [
            'string' => [['type' => 'string', 'minLength' => '3'], 'The key "minLength" must be of type int, string given.'],
            'integer' => [['type' => 'integer', 'maximum' => '10'], 'The key "maximum" must be of type float|int, string given.'],
            'number' => [['type' => 'number', 'minimum' => '1.5'], 'The key "minimum" must be of type float|int, string given.'],
            'boolean' => [['type' => 'boolean', 'readOnly' => 'yes'], 'The key "readOnly" must be of type bool, string given.'],
            'null' => [['type' => 'null', 'title' => 1], 'The key "title" must be of type string, int given.'],
            'array' => [['type' => 'array', 'maxItems' => '2'], 'The key "maxItems" must be of type int, string given.'],
            'object' => [['type' => 'object', 'required' => 'a'], 'The key "required" must be of type list<string>, string given.'],
            'property of an object' => [
                ['type' => 'object', 'properties' => ['a' => ['type' => 'string', 'maxLength' => 'x']]],
                'The key "maxLength" must be of type int, string given.',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $definition
     */
    #[Test]
    #[DataProvider('wrongDefinitionsProvider')]
    public function shouldRejectADefinitionWithAKeyOfAnotherType(array $definition, string $message): void
    {
        $this->expectException(TranslatableInvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        PropertySchemaFactory::create($definition);
    }

    #[Test]
    public function shouldTreatAListOfTypesAsAString(): void
    {
        $schema = PropertySchemaFactory::create(['type' => ['string', 'null'], 'title' => 'Name']);

        $this->assertInstanceOf(StringSchema::class, $schema);
        $this->assertSame('Name', $schema->getTitle());
    }

    #[Test]
    public function shouldCastTheLimitsOfAnIntegerToInteger(): void
    {
        $schema = PropertySchemaFactory::create(['type' => 'integer', 'maximum' => 5.7, 'minimum' => 1]);

        $this->assertInstanceOf(IntegerSchema::class, $schema);
        $this->assertSame(5, $schema->getMaximum());
        $this->assertSame(1, $schema->getMinimum());
    }

    #[Test]
    public function shouldIgnoreADefaultThatIsNull(): void
    {
        $schema = PropertySchemaFactory::create(['type' => 'string', 'default' => null]);

        $this->assertNull($schema->getDefault());
        $this->assertArrayNotHasKey('default', $schema->toArray());
    }

    #[Test]
    public function shouldIgnoreAnItemsThatIsABoolean(): void
    {
        $schema = PropertySchemaFactory::create(['type' => 'array', 'items' => false]);

        $this->assertInstanceOf(ArraySchema::class, $schema);
        $this->assertNull($schema->getItems());
    }
}
