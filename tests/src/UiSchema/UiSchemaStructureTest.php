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
use Derafu\Form\UiSchema\ConditionSchema;
use Derafu\Form\UiSchema\Group;
use Derafu\Form\UiSchema\HorizontalLayout;
use Derafu\Form\UiSchema\Label;
use Derafu\Form\UiSchema\UiSchemaCompositeCondition;
use Derafu\Form\UiSchema\UiSchemaCondition;
use Derafu\Form\UiSchema\UiSchemaElementDefinition;
use Derafu\Form\UiSchema\UiSchemaRule;
use Derafu\Form\UiSchema\UiSchemaRuleEffect;
use Derafu\Form\UiSchema\VerticalLayout;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * The elements of the UI schema are built and written back with their children,
 * their options and their rules.
 */
#[CoversClass(Label::class)]
#[CoversClass(Group::class)]
#[CoversClass(HorizontalLayout::class)]
#[CoversClass(VerticalLayout::class)]
#[CoversClass(Categorization::class)]
#[CoversClass(Category::class)]
#[CoversClass(ConditionSchema::class)]
#[CoversClass(UiSchemaRule::class)]
#[CoversClass(UiSchemaCondition::class)]
#[CoversClass(UiSchemaCompositeCondition::class)]
#[CoversClass(AbstractUiSchemaElement::class)]
#[UsesClass(UiSchemaRuleEffect::class)]
#[UsesClass(UiSchemaElementDefinition::class)]
final class UiSchemaStructureTest extends TestCase
{
    private function rule(): UiSchemaRule
    {
        return UiSchemaRule::fromArray([
            'effect' => 'SHOW',
            'condition' => ['scope' => '#/properties/a', 'schema' => ['const' => 'x']],
        ]);
    }

    #[Test]
    public function shouldAddTheCategoriesOfACategorization(): void
    {
        $first = new Category('First');
        $second = new Category('Second', [], $this->rule(), ['icon' => 'x']);
        $categorization = new Categorization([$first], ['tabs' => true]);

        $this->assertSame($categorization, $categorization->addElement($second));
        $this->assertSame($categorization, $categorization->addCategory(new Category('Third')));

        $this->assertSame([$first, $second], array_slice($categorization->getCategories(), 0, 2));
        $this->assertCount(3, $categorization->getElements());
        $this->assertNull($first->getRule());
        $this->assertInstanceOf(UiSchemaRule::class, $second->getRule());

        $array = $categorization->toArray();
        $this->assertSame('Categorization', $array['type']);
        $this->assertSame(['tabs' => true], $array['options']);
        $this->assertSame('Second', $second->toArray()['label']);
        $this->assertIsArray($array['elements']);
        $this->assertCount(3, $array['elements']);
        $this->assertSame($array, $categorization->jsonSerialize());
        $this->assertJsonStringEqualsJsonString((string) json_encode($array), $categorization->toJson());
    }

    #[Test]
    public function shouldAddTheElementsOfACategoryAndOfTheLayouts(): void
    {
        $label = new Label('Text');
        $category = new Category('A');
        $group = new Group('G', []);
        $horizontal = new HorizontalLayout([], ['gap' => 2]);
        $vertical = new VerticalLayout([], ['gap' => 3]);

        foreach ([$category, $group, $horizontal, $vertical] as $container) {
            $this->assertSame($container, $container->addElement($label));
            $this->assertSame([$label], $container->getElements());
        }

        $this->assertSame(['gap' => 2], $horizontal->toArray()['options']);
        $this->assertSame(['gap' => 3], $vertical->toArray()['options']);
        $this->assertSame('Text', $label->getText());
        $this->assertSame($label->toArray(), $label->jsonSerialize());
    }

    #[Test]
    public function shouldWriteTheRuleAndItsConditionsAsJson(): void
    {
        $rule = $this->rule();
        $this->assertSame(UiSchemaRuleEffect::SHOW, $rule->getEffect());

        $condition = $rule->getCondition();
        $this->assertSame($rule->toArray(), $rule->jsonSerialize());
        $this->assertJsonStringEqualsJsonString((string) json_encode($rule->toArray()), $rule->toJson());
        $this->assertSame($condition->toArray(), $condition->jsonSerialize());
        $this->assertJsonStringEqualsJsonString((string) json_encode($condition->toArray()), $condition->toJson());

        $single = new UiSchemaCondition('#/properties/a', ConditionSchema::fromArray(['const' => 'x']));
        $this->assertSame($single->toArray(), $single->jsonSerialize());
        $this->assertJsonStringEqualsJsonString((string) json_encode($single->toArray()), $single->toJson());
    }

    #[Test]
    public function shouldWriteEveryKeyOfAConditionSchema(): void
    {
        $definition = [
            'const' => 'a',
            'enum' => ['a', 'b'],
            'contains' => ['const' => 'a'],
            'not' => ['const' => 'b'],
            'minimum' => 1,
            'maximum' => 9,
            'exclusiveMinimum' => 0,
            'exclusiveMaximum' => 10,
            'pattern' => '^a',
        ];

        $schema = ConditionSchema::fromArray($definition);

        $this->assertSame(1, $schema->getMinimum());
        $this->assertSame(9, $schema->getMaximum());
        $this->assertSame(0, $schema->getExclusiveMinimum());
        $this->assertSame(10, $schema->getExclusiveMaximum());
        $this->assertSame('^a', $schema->getPattern());
        $this->assertSame($definition, $schema->toArray());
    }

    #[Test]
    public function shouldBeAStringWithTheTextOfTheLabel(): void
    {
        $this->assertSame('Text', (string) new Label('Text'));
    }
}
