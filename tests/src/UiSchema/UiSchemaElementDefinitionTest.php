<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\UiSchema;

use Derafu\Form\Abstract\AbstractUiSchemaElement;
use Derafu\Form\UiSchema\Control;
use Derafu\Form\UiSchema\UiSchemaElementDefinition;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The definition of an element is read with the type that each key has, and a
 * control is not created without the scope of its property.
 */
#[CoversClass(UiSchemaElementDefinition::class)]
#[CoversClass(Control::class)]
#[CoversClass(AbstractUiSchemaElement::class)]
final class UiSchemaElementDefinitionTest extends TestCase
{
    #[Test]
    public function shouldReadEveryKey(): void
    {
        $definition = UiSchemaElementDefinition::fromArray([
            'type' => 'Control',
            'scope' => '#/properties/name',
            'label' => 'Name',
            'options' => ['placeholder' => 'Your name'],
            'rule' => ['effect' => 'HIDE', 'condition' => ['scope' => '#/properties/a', 'schema' => ['const' => 1]]],
        ]);

        $this->assertSame('Control', $definition->type);
        $this->assertSame('#/properties/name', $definition->scope);
        $this->assertSame('Name', $definition->label);
        $this->assertSame(['placeholder' => 'Your name'], $definition->options);
        $this->assertSame('HIDE', $definition->rule['effect'] ?? null);
    }

    #[Test]
    public function shouldHaveNoValuesWhenTheDefinitionIsEmpty(): void
    {
        $definition = UiSchemaElementDefinition::fromArray([]);

        $this->assertNull($definition->type);
        $this->assertNull($definition->scope);
        $this->assertNull($definition->label);
        $this->assertSame([], $definition->options);
        $this->assertNull($definition->rule);
        $this->assertSame([], $definition->toArray());
    }

    #[Test]
    public function shouldWriteTheKeysInTheOrderOfTheDefinition(): void
    {
        $definition = UiSchemaElementDefinition::fromArray([
            'rule' => ['effect' => 'SHOW'],
            'options' => ['a' => 1],
            'label' => 'Name',
            'scope' => '#/properties/name',
            'type' => 'Control',
        ]);

        $this->assertSame(['type', 'scope', 'label', 'options', 'rule'], array_keys($definition->toArray()));
    }

    #[Test]
    public function shouldIgnoreTheKeysThatItDoesNotDeclareAndTheNullOnes(): void
    {
        $definition = UiSchemaElementDefinition::fromArray([
            'type' => 'Control',
            'scope' => '#/properties/name',
            'label' => null,
            'i18n' => 'custom',
            'customFlag' => true,
        ]);

        $this->assertSame(['type' => 'Control', 'scope' => '#/properties/name'], $definition->toArray());
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function wrongDefinitionsProvider(): array
    {
        return [
            'type' => [['type' => 1], 'The key "type" must be of type string, int given.'],
            'scope' => [['scope' => ['a']], 'The key "scope" must be of type string, array given.'],
            'label' => [['label' => true], 'The key "label" must be of type string, bool given.'],
            'options' => [['options' => 'x'], 'The key "options" must be of type object, string given.'],
            'rule' => [['rule' => ['a']], 'The key "rule" must be of type object, array given.'],
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

        UiSchemaElementDefinition::fromArray($definition);
    }

    #[Test]
    public function shouldNotCreateAControlWithoutAScope(): void
    {
        $this->expectException(TranslatableInvalidArgumentException::class);
        $this->expectExceptionMessage('The key "scope" is required.');

        Control::fromArray(['type' => 'Control']);
    }

    #[Test]
    public function shouldNotCreateAControlWithAKeyOfAnotherType(): void
    {
        $this->expectException(TranslatableInvalidArgumentException::class);
        $this->expectExceptionMessage('The key "label" must be of type string, int given.');

        new Control(['scope' => '#/properties/name', 'label' => 5]);
    }

    #[Test]
    public function shouldGiveTheValuesOfAControl(): void
    {
        $control = new Control([
            'type' => 'Control',
            'scope' => '#/properties/user/properties/name',
            'label' => 'Name',
            'options' => ['id' => 'my-id', 'type' => 'password', 'name' => 'custom.name'],
        ]);

        $this->assertSame('#/properties/user/properties/name', $control->getScope());
        $this->assertSame('Name', $control->getLabel());
        $this->assertSame('my-id', $control->getId());
        $this->assertSame('password', $control->getControlType());
        $this->assertSame('custom.name', $control->getPropertyName());
        $this->assertSame(
            ['type' => 'Control', 'scope' => '#/properties/user/properties/name', 'label' => 'Name', 'options' => ['id' => 'my-id', 'type' => 'password', 'name' => 'custom.name']],
            $control->toArray()
        );
    }

    #[Test]
    public function shouldTakeTheNameOfThePropertyFromTheScopeWhenThereIsNoNameOption(): void
    {
        $control = new Control(['scope' => '#/properties/user/properties/name']);

        $this->assertSame('user.name', $control->getPropertyName());
        $this->assertNull($control->getLabel());
        $this->assertNull($control->getControlType());
        $this->assertSame([], $control->getOptions());
        $this->assertStringStartsWith('control-', $control->getId());
    }

    #[Test]
    public function shouldRejectAnIdOrANameInTheOptionsThatIsNotAString(): void
    {
        $control = new Control(['scope' => '#/properties/name', 'options' => ['id' => 5, 'name' => ['a']]]);

        try {
            $control->getId();
            $this->fail('An id that is not a string must be rejected.');
        } catch (TranslatableInvalidArgumentException $e) {
            $this->assertSame('The key "id" must be of type string, int given.', $e->getMessage());
        }

        $this->expectException(TranslatableInvalidArgumentException::class);
        $this->expectExceptionMessage('The key "name" must be of type string, array given.');

        $control->getPropertyName();
    }
}
