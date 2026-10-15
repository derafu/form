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

use Derafu\Form\Abstract\AbstractPropertySchema;
use Derafu\Form\Abstract\AbstractUiSchemaElement;
use Derafu\Form\Data\FormData;
use Derafu\Form\Factory\FormUiSchemaFactory;
use Derafu\Form\Factory\PropertySchemaFactory;
use Derafu\Form\Factory\UiSchemaElementFactory;
use Derafu\Form\Form;
use Derafu\Form\FormDefinition;
use Derafu\Form\Options\FormOptions;
use Derafu\Form\Rules\FormRules;
use Derafu\Form\Schema\FormSchema;
use Derafu\Form\Schema\ObjectSchemaTrait;
use Derafu\Form\Schema\PropertyDefinition;
use Derafu\Form\Schema\StringSchema;
use Derafu\Form\UiSchema\Control;
use Derafu\Form\UiSchema\UiSchemaElementDefinition;
use Derafu\Form\UiSchema\VerticalLayout;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * A form is written back as an array and as JSON.
 */
#[CoversClass(Form::class)]
#[UsesClass(FormDefinition::class)]
#[UsesClass(AbstractPropertySchema::class)]
#[UsesClass(AbstractUiSchemaElement::class)]
#[UsesClass(FormData::class)]
#[UsesClass(FormUiSchemaFactory::class)]
#[UsesClass(PropertySchemaFactory::class)]
#[UsesClass(UiSchemaElementFactory::class)]
#[UsesClass(FormOptions::class)]
#[UsesClass(FormRules::class)]
#[UsesClass(FormSchema::class)]
#[UsesClass(PropertyDefinition::class)]
#[UsesClass(StringSchema::class)]
#[UsesClass(Control::class)]
#[UsesClass(UiSchemaElementDefinition::class)]
#[UsesClass(VerticalLayout::class)]
#[UsesTrait(ObjectSchemaTrait::class)]
final class FormSerializationTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function definition(): array
    {
        return [
            'schema' => ['type' => 'object', 'properties' => ['name' => ['type' => 'string']]],
            'uischema' => ['type' => 'VerticalLayout', 'elements' => [['type' => 'Control', 'scope' => '#/properties/name']]],
            'data' => ['name' => 'Ana'],
        ];
    }

    #[Test]
    public function shouldWriteTheSectionsAsArray(): void
    {
        $array = Form::fromArray($this->definition())->toArray();

        $this->assertSame(['schema', 'uischema', 'rules', 'data', 'options'], array_keys($array));
        $this->assertSame(['name' => 'Ana'], $array['data']);
    }

    #[Test]
    public function shouldWriteJsonWithoutTheRulesWhenThereAreNone(): void
    {
        $form = Form::fromArray($this->definition());

        $this->assertSame(['schema', 'uischema', 'data', 'options'], array_keys($form->jsonSerialize()));

        $decoded = json_decode($form->toJson(), true);
        $this->assertIsArray($decoded);
        $this->assertSame(['name' => 'Ana'], $decoded['data']);
        $this->assertArrayNotHasKey('rules', $decoded);
    }

    #[Test]
    public function shouldWriteTheRulesWhenThereAreSome(): void
    {
        $form = Form::fromArray($this->definition() + ['rules' => ['name' => ['validate' => ['required']]]]);

        $this->assertSame(['schema', 'uischema', 'rules', 'data', 'options'], array_keys($form->jsonSerialize()));
        $decoded = json_decode($form->toJson(), true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('rules', $decoded);
    }

    #[Test]
    public function shouldWriteTheDefinitionForJsonForms(): void
    {
        $definition = Form::fromArray($this->definition())->toJsonFormDefinition();

        $this->assertSame(['schema', 'uischema', 'data'], array_keys($definition));
        $this->assertSame(['name' => 'Ana'], $definition['data']);
    }
}
