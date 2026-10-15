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
use Derafu\Form\Contract\Schema\PropertySchemaInterface;
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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A schema is created from a full definition, gives back each one of its
 * values, and writes the definition that it was created from.
 */
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
#[CoversClass(PropertySchemaFactory::class)]
#[CoversClass(PropertyDefinition::class)]
final class SchemaAccessorsTest extends TestCase
{
    /**
     * The keys that every schema has.
     *
     * @return array<string, mixed>
     */
    private static function common(): array
    {
        return [
            'title' => 'Title',
            'description' => 'Description',
            'default' => 'x',
            'deprecated' => true,
            'examples' => ['a', 'b'],
            'readOnly' => true,
            'writeOnly' => false,
            'enum' => ['a', 'b'],
            'const' => 'a',
            'allOf' => [['type' => 'string']],
            'anyOf' => [['type' => 'string'], ['type' => 'integer']],
            'oneOf' => [['const' => 'a', 'title' => 'A']],
        ];
    }

    /**
     * @return array<string, array{array<string, mixed>, class-string<PropertySchemaInterface>, array<string, mixed>}>
     */
    public static function schemasProvider(): array
    {
        $common = self::common();

        return [
            'string' => [
                $common + ['type' => 'string', 'format' => 'email', 'maxLength' => 10, 'minLength' => 1, 'pattern' => '^a', 'contentMediaType' => 'text/plain'],
                StringSchema::class,
                ['getFormat' => 'email', 'getMaxLength' => 10, 'getMinLength' => 1, 'getPattern' => '^a', 'getContentMediaType' => 'text/plain'],
            ],
            'integer' => [
                $common + ['type' => 'integer', 'exclusiveMaximum' => 10, 'exclusiveMinimum' => 0, 'maximum' => 9, 'minimum' => 1, 'multipleOf' => 2],
                IntegerSchema::class,
                ['getExclusiveMaximum' => 10, 'getExclusiveMinimum' => 0, 'getMaximum' => 9, 'getMinimum' => 1, 'getMultipleOf' => 2],
            ],
            'number' => [
                $common + ['type' => 'number', 'exclusiveMaximum' => 10.5, 'exclusiveMinimum' => 0.5, 'maximum' => 9.5, 'minimum' => 1.5, 'multipleOf' => 0.5],
                NumberSchema::class,
                ['getExclusiveMaximum' => 10.5, 'getExclusiveMinimum' => 0.5, 'getMaximum' => 9.5, 'getMinimum' => 1.5, 'getMultipleOf' => 0.5],
            ],
            'boolean' => [
                $common + ['type' => 'boolean'],
                BooleanSchema::class,
                [],
            ],
            'array' => [
                $common + ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 5, 'minItems' => 1, 'contains' => ['type' => 'string'], 'maxContains' => 2, 'minContains' => 1, 'uniqueItems' => true],
                ArraySchema::class,
                ['getMaxItems' => 5, 'getMinItems' => 1, 'getContains' => ['type' => 'string'], 'getMaxContains' => 2, 'getMinContains' => 1, 'needUniqueItems' => true],
            ],
            'object' => [
                $common + [
                    'type' => 'object',
                    'required' => ['a'],
                    'dependentRequired' => ['a' => ['b']],
                    'maxProperties' => 3,
                    'minProperties' => 1,
                    'additionalProperties' => false,
                    'properties' => ['a' => ['type' => 'string'], 'b' => ['type' => 'integer']],
                ],
                ObjectSchema::class,
                ['getRequired' => ['a'], 'getDependentRequired' => ['a' => ['b']], 'getMaxProperties' => 3, 'getMinProperties' => 1, 'getAdditionalProperties' => false],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $definition
     * @param class-string<PropertySchemaInterface> $class
     * @param array<string, mixed> $getters
     */
    #[Test]
    #[DataProvider('schemasProvider')]
    public function shouldGiveBackEveryValueAndWriteTheDefinition(array $definition, string $class, array $getters): void
    {
        $schema = PropertySchemaFactory::create($definition + ['name' => 'field']);

        $this->assertInstanceOf($class, $schema);
        $this->assertSame('field', $schema->getName());
        $this->assertSame($definition['type'], $schema->getType());
        $this->assertSame('Title', $schema->getTitle());
        $this->assertSame('Description', $schema->getDescription());
        $this->assertSame('x', $schema->getDefault());
        $this->assertTrue($schema->isDeprecated());
        $this->assertSame(['a', 'b'], $schema->getExamples());
        $this->assertTrue($schema->isReadOnly());
        $this->assertFalse($schema->isWriteOnly());
        $this->assertSame(['a', 'b'], $schema->getEnum());
        $this->assertSame('a', $schema->getConst());
        $this->assertSame([['type' => 'string']], $schema->getAllOf());
        $this->assertSame([['type' => 'string'], ['type' => 'integer']], $schema->getAnyOf());
        $this->assertSame([['const' => 'a', 'title' => 'A']], $schema->getOneOf());

        foreach ($getters as $method => $expected) {
            $this->assertSame($expected, $schema->$method(), $method);
        }

        // The order of the keys is the one of the schema, not the one of the definition.
        $expected = $definition;
        $written = $schema->toArray();
        ksort($expected);
        ksort($written);
        $this->assertSame($expected, $written);
        $this->assertSame($schema->toArray(), $schema->jsonSerialize());
        $this->assertJsonStringEqualsJsonString((string) json_encode($schema->toArray()), $schema->toJson());
    }

    #[Test]
    public function shouldGiveTheItemsAndThePropertiesThatAreSchemasToo(): void
    {
        $array = PropertySchemaFactory::create(['type' => 'array', 'items' => ['type' => 'string']]);
        $this->assertInstanceOf(ArraySchema::class, $array);
        $this->assertInstanceOf(StringSchema::class, $array->getItems());

        $object = PropertySchemaFactory::create([
            'type' => 'object',
            'properties' => ['a' => ['type' => 'string'], 'b' => ['type' => 'integer']],
        ]);
        $this->assertInstanceOf(ObjectSchema::class, $object);
        $this->assertSame(['a', 'b'], array_keys($object->getProperties()));
        $this->assertTrue($object->hasProperty('a'));
        $this->assertFalse($object->hasProperty('z'));
        $this->assertInstanceOf(IntegerSchema::class, $object->getProperty('b'));
        $this->assertNull($object->getProperty('z'));
    }

    #[Test]
    public function shouldNotTakeWhatANullSchemaDoesNotHave(): void
    {
        $schema = PropertySchemaFactory::create(['type' => 'null', 'title' => 'Nothing', 'default' => 'x', 'enum' => ['a'], 'const' => 'a']);

        $this->assertInstanceOf(NullSchema::class, $schema);
        $this->assertSame('null', $schema->getType());
        $this->assertSame('Nothing', $schema->getTitle());
        $this->assertNull($schema->getDefault());
        $this->assertNull($schema->getEnum());
        $this->assertNull($schema->getConst());
    }

    #[Test]
    public function shouldAllowToChangeTheNameAndTheValuesAfterCreatingTheSchema(): void
    {
        $schema = (new StringSchema('first'))
            ->setName('second')
            ->setTitle('Title')
            ->setAllOf([['type' => 'string']])
            ->setAnyOf([['type' => 'string']])
            ->setExamples(['a'])
            ->setDeprecated(true)
            ->setReadOnly(true)
            ->setWriteOnly(true)
            ->setConst('c');

        $this->assertSame('second', $schema->getName());
        $this->assertSame([['type' => 'string']], $schema->getAllOf());
        $this->assertSame([['type' => 'string']], $schema->getAnyOf());
        $this->assertSame(['a'], $schema->getExamples());
        $this->assertTrue($schema->isDeprecated());
        $this->assertTrue($schema->isReadOnly());
        $this->assertTrue($schema->isWriteOnly());
        $this->assertSame('c', $schema->getConst());
    }

    #[Test]
    public function shouldGiveTheDefinitionsOfTheFormAndTheOnesOfTheLegacyKey(): void
    {
        $schema = FormSchema::fromArray([
            'type' => 'object',
            'properties' => ['a' => ['type' => 'string']],
            '$defs' => ['d' => ['type' => 'string']],
            'definitions' => ['e' => ['type' => 'integer']],
        ]);

        $this->assertSame(['d', 'e'], array_keys($schema->getDefinitions()));
        $this->assertSame(
            ['type' => 'object', 'properties' => ['a' => ['type' => 'string']], '$defs' => ['d' => ['type' => 'string'], 'e' => ['type' => 'integer']]],
            $schema->toArray()
        );

        $schema->addDefinition('f', PropertySchemaFactory::create(['type' => 'boolean']));
        $this->assertSame(['d', 'e', 'f'], array_keys($schema->getDefinitions()));

        $schema->setDefinitions([]);
        $this->assertSame([], $schema->getDefinitions());
    }

    #[Test]
    public function shouldCreateTheFormSchemaWithEveryValueOfTheObject(): void
    {
        $definition = self::common() + [
            'type' => 'object',
            'required' => ['a'],
            'dependentRequired' => ['a' => ['b']],
            'maxProperties' => 3,
            'minProperties' => 1,
            'additionalProperties' => false,
            'properties' => ['a' => ['type' => 'string'], 'b' => ['type' => 'integer']],
        ];

        $schema = FormSchema::fromArray($definition);

        $this->assertSame('Title', $schema->getTitle());
        $this->assertSame('Description', $schema->getDescription());
        $this->assertSame('x', $schema->getDefault());
        $this->assertTrue($schema->isDeprecated());
        $this->assertSame(['a', 'b'], $schema->getExamples());
        $this->assertTrue($schema->isReadOnly());
        $this->assertFalse($schema->isWriteOnly());
        $this->assertSame(['a', 'b'], $schema->getEnum());
        $this->assertSame('a', $schema->getConst());
        $this->assertSame([['type' => 'string']], $schema->getAllOf());
        $this->assertSame([['type' => 'string'], ['type' => 'integer']], $schema->getAnyOf());
        $this->assertSame([['const' => 'a', 'title' => 'A']], $schema->getOneOf());
        $this->assertSame(['a' => ['b']], $schema->getDependentRequired());
        $this->assertSame(3, $schema->getMaxProperties());
        $this->assertSame(1, $schema->getMinProperties());
        $this->assertFalse($schema->getAdditionalProperties());

        $expected = $definition;
        $written = $schema->toArray();
        ksort($expected);
        ksort($written);
        $this->assertSame($expected, $written);
    }

    #[Test]
    public function shouldCreateTheNullSchemaWithTheValuesThatItAccepts(): void
    {
        $schema = PropertySchemaFactory::create(self::common() + ['type' => 'null']);

        $this->assertInstanceOf(NullSchema::class, $schema);
        $this->assertSame('Description', $schema->getDescription());
        $this->assertTrue($schema->isDeprecated());
        $this->assertSame(['a', 'b'], $schema->getExamples());
        $this->assertTrue($schema->isReadOnly());
        $this->assertFalse($schema->isWriteOnly());
        $this->assertSame([['type' => 'string']], $schema->getAllOf());
        $this->assertSame([['type' => 'string'], ['type' => 'integer']], $schema->getAnyOf());
        $this->assertSame([['const' => 'a', 'title' => 'A']], $schema->getOneOf());
    }

    #[Test]
    public function shouldFindThePropertiesByThePointerOrByThePath(): void
    {
        $schema = FormSchema::fromArray([
            'type' => 'object',
            'properties' => [
                'a' => ['type' => 'object', 'properties' => ['b' => ['type' => 'string']]],
                'c' => ['type' => 'string'],
            ],
        ]);

        $this->assertInstanceOf(StringSchema::class, $schema->getProperty('#/properties/a/properties/b'));
        $this->assertInstanceOf(StringSchema::class, $schema->getProperty('a.b'));
        $this->assertNull($schema->getProperty('a.missing'));
        $this->assertNull($schema->getProperty('c.b'), 'A property that is not an object has no children.');
        $this->assertNull($schema->getProperty('missing'));
    }
}
