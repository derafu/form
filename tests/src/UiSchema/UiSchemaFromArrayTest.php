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

use Closure;
use Derafu\Form\Abstract\AbstractUiSchemaElement;
use Derafu\Form\Factory\UiSchemaElementFactory;
use Derafu\Form\UiSchema\Categorization;
use Derafu\Form\UiSchema\Category;
use Derafu\Form\UiSchema\Group;
use Derafu\Form\UiSchema\HorizontalLayout;
use Derafu\Form\UiSchema\Label;
use Derafu\Form\UiSchema\UiSchemaElementDefinition;
use Derafu\Form\UiSchema\VerticalLayout;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * The elements of the UI schema read their definition by key, with the type
 * that each one must have: a key that is missing or is of another type is an
 * error that says which one.
 */
#[CoversClass(Label::class)]
#[CoversClass(Group::class)]
#[CoversClass(HorizontalLayout::class)]
#[CoversClass(VerticalLayout::class)]
#[CoversClass(Categorization::class)]
#[CoversClass(Category::class)]
#[UsesClass(AbstractUiSchemaElement::class)]
#[UsesClass(UiSchemaElementFactory::class)]
#[UsesClass(UiSchemaElementDefinition::class)]
final class UiSchemaFromArrayTest extends TestCase
{
    /**
     * @return array<string, array{Closure(): mixed, string}>
     */
    public static function wrongDefinitionsProvider(): array
    {
        return [
            'label without text' => [fn () => Label::fromArray([]), 'The key "text" is required.'],
            'label with a text that is not a string' => [fn () => Label::fromArray(['text' => 1]), 'The key "text" must be of type string, int given.'],
            'group without label' => [fn () => Group::fromArray(['elements' => []]), 'The key "label" is required.'],
            'group without elements' => [fn () => Group::fromArray(['label' => 'A']), 'The key "elements" is required.'],
            'group with elements that are not a list' => [fn () => Group::fromArray(['label' => 'A', 'elements' => 'x']), 'The key "elements" must be of type list<object>, string given.'],
            'group with an element that is not an object' => [fn () => Group::fromArray(['label' => 'A', 'elements' => ['x']]), 'The key "elements.0" must be of type object, string given.'],
            'group with options that are not an object' => [fn () => Group::fromArray(['label' => 'A', 'elements' => [], 'options' => 'x']), 'The key "options" must be of type object, string given.'],
            'horizontal layout without elements' => [fn () => HorizontalLayout::fromArray([]), 'The key "elements" is required.'],
            'vertical layout without elements' => [fn () => VerticalLayout::fromArray([]), 'The key "elements" is required.'],
            'categorization without elements' => [fn () => Categorization::fromArray([]), 'The key "elements" is required.'],
            'category without label' => [fn () => Category::fromArray(['elements' => []]), 'The key "label" is required.'],
            'category with a rule that is not an object' => [fn () => Category::fromArray(['label' => 'A', 'elements' => [], 'rule' => 'x']), 'The key "rule" must be of type object, string given.'],
        ];
    }

    /**
     * @param Closure(): mixed $create
     */
    #[Test]
    #[DataProvider('wrongDefinitionsProvider')]
    public function shouldRejectADefinitionThatIsMissingAKeyOrHasTheWrongType(Closure $create, string $message): void
    {
        $this->expectException(TranslatableInvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $create();
    }

    #[Test]
    public function shouldCreateTheElementsThatAreDefined(): void
    {
        $group = Group::fromArray([
            'label' => 'Group',
            'elements' => [['type' => 'Label', 'text' => 'Text']],
            'options' => ['id' => 'g'],
        ]);

        $this->assertSame('Group', $group->getLabel());
        $this->assertSame(['id' => 'g'], $group->getOptions());
        $this->assertCount(1, $group->getElements());
        $this->assertSame('Text', Label::fromArray(['text' => 'Text'])->getText());
    }
}
