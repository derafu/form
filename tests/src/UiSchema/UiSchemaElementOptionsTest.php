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
use Derafu\Form\UiSchema\Categorization;
use Derafu\Form\UiSchema\Category;
use Derafu\Form\UiSchema\Group;
use Derafu\Form\UiSchema\HorizontalLayout;
use Derafu\Form\UiSchema\VerticalLayout;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractUiSchemaElement::class)]
#[CoversClass(Group::class)]
#[CoversClass(Category::class)]
#[CoversClass(Categorization::class)]
#[CoversClass(HorizontalLayout::class)]
#[CoversClass(VerticalLayout::class)]
final class UiSchemaElementOptionsTest extends TestCase
{
    public function testGroupConstructorOptions(): void
    {
        $group = new Group('Test', [], ['collapsible' => true, 'id' => 'my-group']);

        $this->assertSame(['collapsible' => true, 'id' => 'my-group'], $group->getOptions());
    }

    public function testGroupFromArrayOptions(): void
    {
        $group = Group::fromArray([
            'type' => 'Group',
            'label' => 'Test',
            'elements' => [],
            'options' => ['collapsible' => true, 'id' => 'my-group'],
        ]);

        $this->assertSame(['collapsible' => true, 'id' => 'my-group'], $group->getOptions());
    }

    public function testGroupToArrayIncludesOptions(): void
    {
        $group = new Group('Test', [], ['collapsible' => true]);

        $array = $group->toArray();

        $this->assertArrayHasKey('options', $array);
        $this->assertSame(['collapsible' => true], $array['options']);
    }

    public function testGroupToArrayOmitsEmptyOptions(): void
    {
        $group = new Group('Test');

        $array = $group->toArray();

        $this->assertArrayNotHasKey('options', $array);
    }

    public function testGroupFromArrayRoundtrip(): void
    {
        $definition = [
            'type' => 'Group',
            'label' => 'Test',
            'elements' => [],
            'options' => ['collapsible' => true, 'id' => 'my-group'],
        ];

        $array = Group::fromArray($definition)->toArray();

        $this->assertSame($definition['options'], $array['options']);
    }

    public function testCategoryConstructorOptions(): void
    {
        $category = new Category('Tab', [], null, ['id' => 'personal']);

        $this->assertSame(['id' => 'personal'], $category->getOptions());
    }

    public function testCategoryFromArrayOptions(): void
    {
        $category = Category::fromArray([
            'type' => 'Category',
            'label' => 'Tab',
            'elements' => [],
            'options' => ['id' => 'personal'],
        ]);

        $this->assertSame(['id' => 'personal'], $category->getOptions());
    }

    public function testCategoryToArrayIncludesOptions(): void
    {
        $category = new Category('Tab', [], null, ['id' => 'personal']);

        $array = $category->toArray();

        $this->assertArrayHasKey('options', $array);
        $this->assertSame(['id' => 'personal'], $array['options']);
    }

    public function testCategoryToArrayOmitsEmptyOptions(): void
    {
        $category = new Category('Tab');

        $array = $category->toArray();

        $this->assertArrayNotHasKey('options', $array);
    }

    public function testCategorizationConstructorOptions(): void
    {
        $cat = new Categorization([], ['tab_style' => 'vertical', 'id' => 'main-tabs']);

        $this->assertSame(['tab_style' => 'vertical', 'id' => 'main-tabs'], $cat->getOptions());
    }

    public function testCategorizationFromArrayOptions(): void
    {
        $cat = Categorization::fromArray([
            'type' => 'Categorization',
            'elements' => [],
            'options' => ['tab_style' => 'vertical'],
        ]);

        $this->assertSame(['tab_style' => 'vertical'], $cat->getOptions());
    }

    public function testHorizontalLayoutConstructorOptions(): void
    {
        $layout = new HorizontalLayout([], ['id' => 'my-row']);

        $this->assertSame(['id' => 'my-row'], $layout->getOptions());
    }

    public function testHorizontalLayoutFromArrayOptions(): void
    {
        $layout = HorizontalLayout::fromArray([
            'type' => 'HorizontalLayout',
            'elements' => [],
            'options' => ['id' => 'my-row'],
        ]);

        $this->assertSame(['id' => 'my-row'], $layout->getOptions());
    }

    public function testVerticalLayoutConstructorOptions(): void
    {
        $layout = new VerticalLayout([], ['id' => 'my-col']);

        $this->assertSame(['id' => 'my-col'], $layout->getOptions());
    }

    public function testVerticalLayoutFromArrayOptions(): void
    {
        $layout = VerticalLayout::fromArray([
            'type' => 'VerticalLayout',
            'elements' => [],
            'options' => ['id' => 'my-col'],
        ]);

        $this->assertSame(['id' => 'my-col'], $layout->getOptions());
    }

    public function testGetIdReturnsExplicitId(): void
    {
        $group = new Group('Test', [], ['id' => 'datos-personales']);

        $this->assertSame('datos-personales', $group->getId());
    }

    public function testGetIdFromArrayReturnsExplicitId(): void
    {
        $group = Group::fromArray([
            'type' => 'Group',
            'label' => 'Test',
            'elements' => [],
            'options' => ['id' => 'mi-grupo'],
        ]);

        $this->assertSame('mi-grupo', $group->getId());
    }

    public function testGetIdFallbackIsNonEmptyString(): void
    {
        $group = new Group('Test');

        $id = $group->getId();

        $this->assertIsString($id);
        $this->assertNotEmpty($id);
    }

    public function testGetIdFallbackUsesTypePrefixForGroup(): void
    {
        $group = new Group('Test');

        $this->assertStringStartsWith('group-', $group->getId());
    }

    public function testGetIdFallbackUsesTypePrefixForCategory(): void
    {
        $category = new Category('Tab');

        $this->assertStringStartsWith('category-', $category->getId());
    }

    public function testGetIdFallbackUsesTypePrefixForCategorization(): void
    {
        $cat = new Categorization();

        $this->assertStringStartsWith('categorization-', $cat->getId());
    }

    public function testGetIdIsStableWithinSameInstance(): void
    {
        $group = new Group('Test');

        $this->assertSame($group->getId(), $group->getId());
    }

    public function testCategoryGetIdReturnsExplicitId(): void
    {
        $category = new Category('Personal', [], null, ['id' => 'personal']);

        $this->assertSame('personal', $category->getId());
    }

    public function testCategorizationGetIdReturnsExplicitId(): void
    {
        $cat = new Categorization([], ['id' => 'main-tabs']);

        $this->assertSame('main-tabs', $cat->getId());
    }
}
