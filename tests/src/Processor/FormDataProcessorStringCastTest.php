<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Tests\Processor;

use Derafu\DataProcessor\Contract\ProcessorInterface;
use Derafu\DataProcessor\ProcessorFactory;
use Derafu\Form\Abstract\AbstractPropertySchema;
use Derafu\Form\Abstract\AbstractType;
use Derafu\Form\Abstract\AbstractUiSchemaElement;
use Derafu\Form\Contract\Processor\FormDataProcessorInterface;
use Derafu\Form\Factory\FormFactory;
use Derafu\Form\Factory\FormUiSchemaFactory;
use Derafu\Form\Factory\UiSchemaElementFactory;
use Derafu\Form\Form;
use Derafu\Form\FormField;
use Derafu\Form\Options\FormOptions;
use Derafu\Form\Processor\FormDataProcessor;
use Derafu\Form\Processor\ProcessResult;
use Derafu\Form\Processor\SchemaToRulesMapper;
use Derafu\Form\Schema\FormSchema;
use Derafu\Form\Schema\ObjectSchemaTrait;
use Derafu\Form\Schema\StringSchema;
use Derafu\Form\Type\BooleanType;
use Derafu\Form\Type\ChoiceType;
use Derafu\Form\Type\ColorType;
use Derafu\Form\Type\DatetimeType;
use Derafu\Form\Type\DateType;
use Derafu\Form\Type\EmailType;
use Derafu\Form\Type\FloatType;
use Derafu\Form\Type\IntegerType;
use Derafu\Form\Type\Ipv4Type;
use Derafu\Form\Type\Ipv6Type;
use Derafu\Form\Type\MonthType;
use Derafu\Form\Type\TextareaType;
use Derafu\Form\Type\TextType;
use Derafu\Form\Type\TimeType;
use Derafu\Form\Type\TypeProvider;
use Derafu\Form\Type\TypeRegistry;
use Derafu\Form\Type\TypeResolver;
use Derafu\Form\Type\UriType;
use Derafu\Form\Type\UrlType;
use Derafu\Form\Type\UuidType;
use Derafu\Form\Type\WeekType;
use Derafu\Form\UiSchema\Control;
use Derafu\Form\UiSchema\VerticalLayout;
use Derafu\Form\Widget\Widget;
use Derafu\Form\Widget\WidgetFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Test to reproduce the "Caster rule 'string' not found" error.
 */
#[CoversClass(FormDataProcessor::class)]
#[CoversClass(SchemaToRulesMapper::class)]
#[CoversClass(AbstractPropertySchema::class)]
#[CoversClass(AbstractType::class)]
#[CoversClass(AbstractUiSchemaElement::class)]
#[CoversClass(FormFactory::class)]
#[CoversClass(FormUiSchemaFactory::class)]
#[CoversClass(UiSchemaElementFactory::class)]
#[CoversClass(Form::class)]
#[CoversClass(FormField::class)]
#[CoversClass(FormOptions::class)]
#[CoversClass(ProcessResult::class)]
#[CoversClass(FormSchema::class)]
#[CoversClass(ObjectSchemaTrait::class)]
#[CoversClass(StringSchema::class)]
#[CoversClass(BooleanType::class)]
#[CoversClass(ChoiceType::class)]
#[CoversClass(ColorType::class)]
#[CoversClass(DateType::class)]
#[CoversClass(DatetimeType::class)]
#[CoversClass(EmailType::class)]
#[CoversClass(FloatType::class)]
#[CoversClass(IntegerType::class)]
#[CoversClass(Ipv4Type::class)]
#[CoversClass(Ipv6Type::class)]
#[CoversClass(MonthType::class)]
#[CoversClass(TextType::class)]
#[CoversClass(TextareaType::class)]
#[CoversClass(TimeType::class)]
#[CoversClass(TypeProvider::class)]
#[CoversClass(TypeRegistry::class)]
#[CoversClass(TypeResolver::class)]
#[CoversClass(UriType::class)]
#[CoversClass(UrlType::class)]
#[CoversClass(UuidType::class)]
#[CoversClass(WeekType::class)]
#[CoversClass(Control::class)]
#[CoversClass(VerticalLayout::class)]
#[CoversClass(WidgetFactory::class)]
#[CoversClass(Widget::class)]
final class FormDataProcessorStringCastTest extends TestCase
{
    private FormDataProcessorInterface $processor;

    private SchemaToRulesMapper $mapper;

    private ProcessorInterface $dataProcessor;

    private FormFactory $formFactory;

    protected function setUp(): void
    {
        $this->mapper = new SchemaToRulesMapper();
        $this->dataProcessor = (new ProcessorFactory())->create();
        $this->processor = new FormDataProcessor(
            $this->mapper,
            $this->dataProcessor
        );
        $this->formFactory = new FormFactory(new TypeResolver(new TypeRegistry(new TypeProvider())));
    }

    public function testSimpleStringFieldShouldNotThrowCasterError(): void
    {
        // Create a simple form with one string field
        $formDefinition = [
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                    ],
                ],
                'required' => ['name'],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    [
                        'type' => 'Control',
                        'scope' => '#/properties/name',
                    ],
                ],
            ],
        ];

        $form = $this->formFactory->create($formDefinition);

        $data = ['name' => 'John Doe'];

        $result = $this->processor->process($form, $data);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
        $this->assertSame('John Doe', $result->getProcessedData()['name']);
    }

    public function testStringFieldWithEmptyValueShouldNotThrowCasterError(): void
    {
        // Create a simple form with one string field
        $formDefinition = [
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                    ],
                ],
                'required' => ['name'],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    [
                        'type' => 'Control',
                        'scope' => '#/properties/name',
                    ],
                ],
            ],
        ];

        $form = $this->formFactory->create($formDefinition);

        $data = ['name' => ''];

        $result = $this->processor->process($form, $data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
        $this->assertArrayHasKey('name', $result->getErrors());
        $this->assertSame('', $result->getProcessedData()['name']);
    }

    public function testStringFieldWithNullValueShouldNotThrowCasterError(): void
    {
        // Create a simple form with one string field
        $formDefinition = [
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                    ],
                ],
                'required' => ['name'],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    [
                        'type' => 'Control',
                        'scope' => '#/properties/name',
                    ],
                ],
            ],
        ];

        $form = $this->formFactory->create($formDefinition);

        $data = ['name' => null];

        $result = $this->processor->process($form, $data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
        $this->assertArrayHasKey('name', $result->getErrors());
        $this->assertNull($result->getProcessedData()['name']);
    }

    public function testStringFieldWithMissingValueShouldNotThrowCasterError(): void
    {
        // Create a simple form with one string field
        $formDefinition = [
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                    ],
                ],
                'required' => ['name'],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    [
                        'type' => 'Control',
                        'scope' => '#/properties/name',
                    ],
                ],
            ],
        ];

        $form = $this->formFactory->create($formDefinition);

        $data = [];

        $result = $this->processor->process($form, $data);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
        $this->assertArrayHasKey('name', $result->getErrors());
        $this->assertArrayHasKey('name', $result->getProcessedData());
        $this->assertNull($result->getProcessedData()['name']);
    }

    public function testOptionalStringFieldShouldNotThrowCasterError(): void
    {
        // Create a simple form with one optional string field
        $formDefinition = [
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                    ],
                ],
                // No required
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    [
                        'type' => 'Control',
                        'scope' => '#/properties/name',
                    ],
                ],
            ],
        ];

        $form = $this->formFactory->create($formDefinition);

        $data = [];

        $result = $this->processor->process($form, $data);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
        $this->assertArrayHasKey('name', $result->getProcessedData());
        $value = $result->getProcessedData()['name'];
        $this->assertTrue($value === '' || $value === null, 'El campo opcional debe ser string vacío o null');
    }
}
