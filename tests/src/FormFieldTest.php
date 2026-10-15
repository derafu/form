<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm;

use Derafu\Form\Factory\PropertySchemaFactory;
use Derafu\Form\FormField;
use Derafu\Form\Schema\PropertyDefinition;
use Derafu\Form\Schema\StringSchema;
use Derafu\Form\UiSchema\Control;
use Derafu\Form\UiSchema\UiSchemaElementDefinition;
use Derafu\Form\Widget\Widget;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * A field joins a property of the schema with the control that shows it.
 */
#[CoversClass(FormField::class)]
#[UsesClass(StringSchema::class)]
#[UsesClass(PropertyDefinition::class)]
#[UsesClass(UiSchemaElementDefinition::class)]
#[UsesClass(Control::class)]
#[UsesClass(Widget::class)]
#[UsesClass(PropertySchemaFactory::class)]
final class FormFieldTest extends TestCase
{
    private function field(): FormField
    {
        $property = PropertySchemaFactory::create(['type' => 'string', 'name' => 'email']);
        $control = new Control(['type' => 'Control', 'scope' => '#/properties/email']);

        return new FormField($property, $control);
    }

    #[Test]
    public function shouldStartWithoutDataNorErrors(): void
    {
        $field = $this->field();

        $this->assertSame('email', $field->getName());
        $this->assertSame('#/properties/email', $field->getControl()->getScope());
        $this->assertSame('email', $field->getProperty()->getName());
        $this->assertFalse($field->isRequired());
        $this->assertFalse($field->isRendered());
        $this->assertNull($field->getData());
        $this->assertSame([], $field->getErrors());
        $this->assertTrue($field->isValid());
    }

    #[Test]
    public function shouldKeepWhatIsSet(): void
    {
        $widget = new Widget('text');
        $field = $this->field()
            ->setWidget($widget)
            ->setNamePattern('contact[%s]')
            ->setRequired()
            ->setRendered()
            ->setData('a@b.c')
            ->setErrors(['Invalid.']);

        $this->assertSame($widget, $field->getWidget());
        $this->assertSame('contact[email]', $field->getName());
        $this->assertTrue($field->isRequired());
        $this->assertTrue($field->isRendered());
        $this->assertSame('a@b.c', $field->getData());
        $this->assertSame(['Invalid.'], $field->getErrors());
        $this->assertFalse($field->isValid());

        $field->setRequired(false)->setRendered(false);
        $this->assertFalse($field->isRequired());
        $this->assertFalse($field->isRendered());
    }

    #[Test]
    public function shouldKeepWhatThePresentationOfTheRequiredFieldsNeeds(): void
    {
        $field = $this->field();
        $this->assertFalse($field->isNativeRequired());
        $this->assertFalse($field->isRequiredAsteriskHidden());

        $field->setNativeRequired()->setRequiredAsteriskHidden();
        $this->assertTrue($field->isNativeRequired());
        $this->assertTrue($field->isRequiredAsteriskHidden());

        $field->setNativeRequired(false)->setRequiredAsteriskHidden(false);
        $this->assertFalse($field->isNativeRequired());
        $this->assertFalse($field->isRequiredAsteriskHidden());
    }

    #[Test]
    public function shouldBeWrittenAsArrayAndAsJson(): void
    {
        $field = $this->field()->setRequired()->setData('x')->setErrors(['e']);

        $array = $field->toArray();
        $this->assertSame(['property', 'control', 'required', 'rendered', 'data', 'errors'], array_keys($array));
        $this->assertSame($field->getProperty()->toArray(), $array['property']);
        $this->assertTrue($array['required']);
        $this->assertSame('x', $array['data']);
        $this->assertSame(['e'], $array['errors']);

        $this->assertSame(array_keys($array), array_keys($field->jsonSerialize()));
        $decoded = json_decode($field->toJson(), true);
        $this->assertIsArray($decoded);
        $this->assertSame('x', $decoded['data']);
        $this->assertSame(['e'], $decoded['errors']);
        $this->assertTrue($decoded['required']);
    }
}
