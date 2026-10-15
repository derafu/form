<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Widget;

use Derafu\Form\Abstract\AbstractPropertySchema;
use Derafu\Form\Abstract\AbstractUiSchemaElement;
use Derafu\Form\FormField;
use Derafu\Form\Schema\NullSchema;
use Derafu\Form\Schema\StringSchema;
use Derafu\Form\UiSchema\Control;
use Derafu\Form\UiSchema\UiSchemaElementDefinition;
use Derafu\Form\Widget\Widget;
use Derafu\Form\Widget\WidgetFactory;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * The type of widget of a field is the one that the control says, if it says
 * one: a "widget" option that is not a string is an error of the definition.
 */
#[CoversClass(WidgetFactory::class)]
#[UsesClass(Widget::class)]
#[UsesClass(FormField::class)]
#[UsesClass(StringSchema::class)]
#[UsesClass(NullSchema::class)]
#[UsesClass(AbstractPropertySchema::class)]
#[UsesClass(Control::class)]
#[UsesClass(AbstractUiSchemaElement::class)]
#[UsesClass(UiSchemaElementDefinition::class)]
final class WidgetFactoryTest extends TestCase
{
    private function field(mixed $widget): FormField
    {
        return new FormField(
            new StringSchema('name'),
            Control::fromArray(['scope' => '#/properties/name', 'options' => ['widget' => $widget]])
        );
    }

    #[Test]
    public function shouldUseTheWidgetThatTheControlSays(): void
    {
        $widget = (new WidgetFactory())->create($this->field('textarea'));

        $this->assertSame('textarea', $widget->getType());
    }

    #[Test]
    public function shouldRejectAWidgetOptionThatIsNotAString(): void
    {
        $this->expectException(TranslatableInvalidArgumentException::class);
        $this->expectExceptionMessage('The "widget" option of the control "#/properties/name" must be a string, int given.');

        (new WidgetFactory())->create($this->field(5));
    }

    #[Test]
    public function shouldFallBackToTheFormatOfTheControlForATypeWithoutWidget(): void
    {
        $control = fn (array $options) => Control::fromArray(['scope' => '#/properties/name', 'options' => $options]);
        $factory = new WidgetFactory();

        $this->assertSame(
            'checkboxes',
            $factory->create(new FormField(new NullSchema('name'), $control(['format' => 'checkbox'])))->getType()
        );
        $this->assertSame(
            'text',
            $factory->create(new FormField(new NullSchema('name'), $control([])))->getType()
        );
    }
}
