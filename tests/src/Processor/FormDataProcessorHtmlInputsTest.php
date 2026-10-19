<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Tests\Processor;

use Derafu\DataProcessor\ProcessorFactory;
use Derafu\Form\Abstract\AbstractPropertySchema;
use Derafu\Form\Abstract\AbstractUiSchemaElement;
use Derafu\Form\Factory\FormUiSchemaFactory;
use Derafu\Form\Factory\PropertySchemaFactory;
use Derafu\Form\Factory\UiSchemaElementFactory;
use Derafu\Form\Form;
use Derafu\Form\FormDefinition;
use Derafu\Form\FormField;
use Derafu\Form\Options\FormOptions;
use Derafu\Form\Processor\FormDataProcessor;
use Derafu\Form\Processor\FormRulesResolver;
use Derafu\Form\Processor\ProcessResult;
use Derafu\Form\Rules\FormRules;
use Derafu\Form\Schema\ArraySchema;
use Derafu\Form\Schema\BooleanSchema;
use Derafu\Form\Schema\FormSchema;
use Derafu\Form\Schema\IntegerSchema;
use Derafu\Form\Schema\NumberSchema;
use Derafu\Form\Schema\ObjectSchemaTrait;
use Derafu\Form\Schema\PropertyDefinition;
use Derafu\Form\Schema\StringSchema;
use Derafu\Form\UiSchema\Control;
use Derafu\Form\UiSchema\UiSchemaElementDefinition;
use Derafu\Form\UiSchema\VerticalLayout;
use Derafu\Form\Widget\Widget;
use Derafu\Form\Widget\WidgetFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * What a browser sends is accepted by the rules that the form derives, and what
 * it does not allow is rejected: the values of the HTML inputs, the checkbox
 * that is not checked, the fields left empty and the limits of the schema.
 */
#[CoversClass(FormDataProcessor::class)]
#[CoversClass(PropertySchemaFactory::class)]
#[CoversClass(FormRulesResolver::class)]
#[CoversClass(FormRules::class)]
#[CoversClass(ProcessResult::class)]
#[CoversClass(AbstractPropertySchema::class)]
#[CoversClass(AbstractUiSchemaElement::class)]
#[CoversClass(Form::class)]
#[CoversClass(FormField::class)]
#[CoversClass(FormOptions::class)]
#[CoversClass(FormSchema::class)]
#[CoversClass(FormUiSchemaFactory::class)]
#[CoversClass(UiSchemaElementFactory::class)]
#[CoversTrait(ObjectSchemaTrait::class)]
#[CoversClass(StringSchema::class)]
#[CoversClass(ArraySchema::class)]
#[CoversClass(BooleanSchema::class)]
#[CoversClass(IntegerSchema::class)]
#[CoversClass(NumberSchema::class)]
#[CoversClass(Control::class)]
#[CoversClass(VerticalLayout::class)]
#[CoversClass(Widget::class)]
#[CoversClass(WidgetFactory::class)]
#[UsesClass(PropertyDefinition::class)]
#[UsesClass(UiSchemaElementDefinition::class)]
#[UsesClass(FormDefinition::class)]
#[UsesClass(\Derafu\Form\Options\FormAttributes::class)]
#[UsesClass(\Derafu\Form\Schema\Pattern::class)]
#[UsesClass(\Derafu\Form\Data\FieldPath::class)]
final class FormDataProcessorHtmlInputsTest extends TestCase
{
    private FormDataProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new FormDataProcessor(
            new FormRulesResolver(),
            ProcessorFactory::create()
        );
    }

    /**
     * Processes one field.
     *
     * @param array<string, mixed> $property The schema of the field.
     * @param array<string, mixed> $data What the browser sent.
     * @param array<string, mixed> $options The options of the control.
     * @param bool $required If the field is required.
     */
    private function process(array $property, array $data, array $options = [], bool $required = false): ProcessResult
    {
        $control = ['type' => 'Control', 'scope' => '#/properties/field'];
        if ($options !== []) {
            $control['options'] = $options;
        }

        return $this->processor->process(Form::fromArray([
            'options' => ['csrf_protection' => false],
            'schema' => [
                'type' => 'object',
                'properties' => ['field' => $property],
                'required' => $required ? ['field'] : [],
            ],
            'uischema' => ['type' => 'VerticalLayout', 'elements' => [$control]],
        ]), $data);
    }

    /**
     * The processed value of the field.
     */
    private function processedField(ProcessResult $result): mixed
    {
        $data = $result->getProcessedData();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('field', $data);

        return $data['field'];
    }

    /**
     * @param array<string, mixed> $property
     * @param array<string, mixed> $options
     */
    #[DataProvider('browserValuesProvider')]
    public function testTheValuesOfTheHtmlInputsAreAccepted(array $property, string $value, array $options = []): void
    {
        $result = $this->process($property, ['field' => $value], $options);

        $this->assertSame([], $result->getErrors());
        $this->assertSame($value, $this->processedField($result));
    }

    /**
     * @return array<string, array{array<string, mixed>, string}|array{array<string, mixed>, string, array<string, mixed>}>
     */
    public static function browserValuesProvider(): array
    {
        return [
            'color' => [['type' => 'string', 'format' => 'color'], '#1a2b3c'],
            'month' => [['type' => 'string', 'format' => 'month'], '2024-05'],
            'week' => [['type' => 'string', 'format' => 'week'], '2024-W22'],
            'time' => [['type' => 'string', 'format' => 'time'], '10:30'],
            'time with seconds' => [['type' => 'string', 'format' => 'time'], '10:30:15'],
            'datetime-local' => [['type' => 'string', 'format' => 'date-time'], '2024-05-01T10:30'],
            'datetime-local with seconds' => [['type' => 'string', 'format' => 'date-time'], '2024-05-01T10:30:15'],
            'ipv4' => [['type' => 'string', 'format' => 'ipv4'], '192.168.0.1'],
            'ipv6' => [['type' => 'string', 'format' => 'ipv6'], '2001:db8::1'],
            'format without a rule' => [['type' => 'string', 'format' => 'duration'], 'P1D'],
            'ipv4 control' => [['type' => 'string'], '192.168.0.1', ['type' => 'ipv4']],
            'month control' => [['type' => 'string'], '2024-05', ['type' => 'month']],
        ];
    }

    /**
     * @param array<string, mixed> $property
     * @param array<string, mixed> $options
     */
    #[DataProvider('rejectedValuesProvider')]
    public function testWhatTheRulesDoNotAllowIsRejected(array $property, string $value, array $options = []): void
    {
        $result = $this->process($property, ['field' => $value], $options);

        $this->assertTrue($result->hasErrors());
        $this->assertArrayHasKey('field', $result->getErrors());
    }

    /**
     * @return array<string, array{array<string, mixed>, string}|array{array<string, mixed>, string, array<string, mixed>}>
     */
    public static function rejectedValuesProvider(): array
    {
        return [
            'color in short form' => [['type' => 'string', 'format' => 'color'], '#fff'],
            'week that the year does not have' => [['type' => 'string', 'format' => 'week'], '2024-W53'],
            'ipv4 that is ipv6' => [['type' => 'string', 'format' => 'ipv4'], '2001:db8::1'],
            'ipv6 that is ipv4' => [['type' => 'string', 'format' => 'ipv6'], '192.168.0.1'],
            'ipv4 control with an ipv6' => [['type' => 'string'], '2001:db8::1', ['type' => 'ipv4']],
            'const that is another value' => [['type' => 'string', 'const' => 'yes'], 'no'],
            'number equal to its exclusive minimum' => [['type' => 'integer', 'exclusiveMinimum' => 5], '5'],
            'number equal to its exclusive maximum' => [['type' => 'integer', 'exclusiveMaximum' => 5], '5'],
            'number that is not a multiple' => [['type' => 'integer', 'multipleOf' => 5], '7'],
            'a text in a field of numbers' => [['type' => 'number'], 'abc'],
        ];
    }

    public function testAnOptionalNumberThatIsLeftEmptyIsValidAndIsNull(): void
    {
        foreach (['integer', 'number'] as $type) {
            $result = $this->process(['type' => $type], ['field' => '']);

            $this->assertSame([], $result->getErrors());
            $this->assertNull($this->processedField($result));
        }
    }

    public function testAnOptionalTextThatIsLeftEmptyIsNull(): void
    {
        $result = $this->process(['type' => 'string'], ['field' => '  ']);

        // The spaces are not an empty text for the processor, only `''` is.
        $this->assertSame([], $result->getErrors());
        $this->assertSame('', $this->processedField($result));

        $result = $this->process(['type' => 'string'], ['field' => '']);

        $this->assertSame([], $result->getErrors());
        $this->assertNull($this->processedField($result));
    }

    public function testARequiredFieldThatIsLeftEmptyFails(): void
    {
        $result = $this->process(['type' => 'integer'], ['field' => ''], [], true);

        $this->assertArrayHasKey('field', $result->getErrors());
    }

    public function testAPasswordIsNotTrimmed(): void
    {
        foreach ([['format' => 'password'], []] as $schema) {
            $options = $schema === [] ? ['type' => 'password'] : [];
            $result = $this->process(['type' => 'string'] + $schema, ['field' => '  secret '], $options);

            $this->assertSame([], $result->getErrors());
            $this->assertSame('  secret ', $this->processedField($result));
        }
    }

    public function testAnUncheckedCheckboxIsFalse(): void
    {
        foreach ([[], ['field' => ''], ['field' => null]] as $data) {
            $result = $this->process(['type' => 'boolean', 'default' => true], $data);

            $this->assertSame([], $result->getErrors());
            $this->assertFalse($this->processedField($result));
        }

        $result = $this->process(['type' => 'boolean'], ['field' => '1']);
        $this->assertTrue($this->processedField($result));
    }

    public function testACheckboxThatMustBeCheckedFailsWhenItIsNotSent(): void
    {
        $result = $this->process(['type' => 'boolean', 'const' => true], []);

        $this->assertArrayHasKey('field', $result->getErrors());
        $this->assertFalse($this->processedField($result));
    }

    public function testAMultipleSelectionAcceptsOnlyItsChoices(): void
    {
        $property = ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a', 'b', 'c']]];

        $this->assertSame([], $this->process($property, ['field' => ['a', 'c']])->getErrors());
        $this->assertArrayHasKey('field', $this->process($property, ['field' => ['a', 'x']])->getErrors());
    }

    public function testAChoiceWithACommaIsOneChoice(): void
    {
        $property = ['type' => 'string', 'enum' => ['a,b', 'c']];

        $this->assertSame([], $this->process($property, ['field' => 'a,b'])->getErrors());
        $this->assertArrayHasKey('field', $this->process($property, ['field' => 'a'])->getErrors());
    }
}
