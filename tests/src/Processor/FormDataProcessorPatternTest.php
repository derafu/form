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
use Derafu\Form\Contract\Processor\ProcessResultInterface;
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
use Derafu\Form\Schema\FormSchema;
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
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for JSON Schema 'pattern' validation through the full
 * FormDataProcessor → FormRulesResolver → DataProcessor stack.
 *
 * Each test builds a real Form with a 'pattern' keyword in the property schema,
 * submits values through FormDataProcessor::process(), and asserts that:
 *   - Valid values pass without errors and are included in processed data.
 *   - Invalid values produce a validation error and are NOT silently accepted.
 *
 * These tests verify the complete pipeline:
 *   JSON Schema 'pattern' (ECMA)
 *     → FormRulesResolver adds PCRE delimiters → 'regex:/pattern/'
 *     → DataProcessor RegexRule runs preg_match()
 *     → ValidationException collected as field error
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
#[CoversClass(Control::class)]
#[CoversClass(VerticalLayout::class)]
#[CoversClass(Widget::class)]
#[CoversClass(WidgetFactory::class)]
#[UsesClass(PropertyDefinition::class)]
#[UsesClass(UiSchemaElementDefinition::class)]
#[UsesClass(FormDefinition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Schema\BooleanSchema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Options\FormAttributes::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Schema\Pattern::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Data\FieldPath::class)]
final class FormDataProcessorPatternTest extends TestCase
{
    private FormDataProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new FormDataProcessor(
            new FormRulesResolver(),
            ProcessorFactory::create()
        );
    }

    // =========================================================================
    // Helper
    // =========================================================================

    /**
     * Builds a form with a single string field that has the given pattern.
     *
     * @param string $pattern ECMA regex without delimiters.
     * @param bool $required Whether the field is required.
     */
    private function formWithPattern(string $pattern, bool $required = false): Form
    {
        return Form::fromArray([
            'options' => ['csrf_protection' => false],
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'code' => [
                        'type' => 'string',
                        'pattern' => $pattern,
                    ],
                ],
                'required' => $required ? ['code'] : [],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    ['type' => 'Control', 'scope' => '#/properties/code'],
                ],
            ],
        ]);
    }

    // =========================================================================
    // ISO duration pattern (from 002_control.json)
    // =========================================================================

    /**
     * ISO 8601 duration — valid value must pass.
     */
    public function testDurationPatternAcceptsValidValue(): void
    {
        $pattern = '^P(?:\d+Y)?(?:\d+M)?(?:\d+D)?(?:T(?:\d+H)?(?:\d+M)?(?:\d+S)?)?$';
        $form = $this->formWithPattern($pattern);

        $result = $this->processor->process($form, ['code' => 'P1Y2M3DT4H5M6S']);

        $this->assertTrue($result->isValid());
        $this->assertSame('P1Y2M3DT4H5M6S', $this->processedData($result)['code']);
        $this->assertArrayNotHasKey('code', $result->getErrors());
    }

    /**
     * ISO 8601 duration — invalid value must fail.
     */
    public function testDurationPatternRejectsInvalidValue(): void
    {
        $pattern = '^P(?:\d+Y)?(?:\d+M)?(?:\d+D)?(?:T(?:\d+H)?(?:\d+M)?(?:\d+S)?)?$';
        $form = $this->formWithPattern($pattern);

        $result = $this->processor->process($form, ['code' => 'hola']);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('code', $result->getErrors());
    }

    // =========================================================================
    // Alphanumeric pattern
    // =========================================================================

    /**
     * Alphanumeric pattern — valid value (letters and digits only) must pass.
     */
    public function testAlphanumericPatternAcceptsLettersAndDigits(): void
    {
        $form = $this->formWithPattern('^[A-Za-z0-9]+$');

        $result = $this->processor->process($form, ['code' => 'abcd1234']);

        $this->assertTrue($result->isValid());
        $this->assertSame('abcd1234', $this->processedData($result)['code']);
    }

    /**
     * Alphanumeric pattern — value with spaces must fail.
     */
    public function testAlphanumericPatternRejectsSpaces(): void
    {
        $form = $this->formWithPattern('^[A-Za-z0-9]+$');

        $result = $this->processor->process($form, ['code' => 'abc 123']);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('code', $result->getErrors());
    }

    /**
     * Alphanumeric pattern — value with special characters must fail.
     */
    public function testAlphanumericPatternRejectsSpecialChars(): void
    {
        $form = $this->formWithPattern('^[A-Za-z0-9]+$');

        $result = $this->processor->process($form, ['code' => 'abc!@#']);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('code', $result->getErrors());
    }

    // =========================================================================
    // Pattern with forward slash inside (URL-like)
    // =========================================================================

    /**
     * Pattern containing a literal '/' — must be escaped correctly when
     * FormRulesResolver converts it to a PCRE regex.
     *
     * Pattern: ^https?://[\w.-]+$ (scheme + host, simplified)
     */
    public function testPatternWithForwardSlashAcceptsValidValue(): void
    {
        $form = $this->formWithPattern('^https?://[\w.-]+$');

        $result = $this->processor->process($form, ['code' => 'https://example.com']);

        $this->assertTrue($result->isValid());
    }

    /**
     * Pattern with '/' — invalid value must still fail.
     */
    public function testPatternWithForwardSlashRejectsInvalidValue(): void
    {
        $form = $this->formWithPattern('^https?://[\w.-]+$');

        $result = $this->processor->process($form, ['code' => 'ftp://example.com']);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('code', $result->getErrors());
    }

    // =========================================================================
    // Pattern + required
    // =========================================================================

    /**
     * Required field with pattern — empty value must fail with "required" error,
     * not a pattern error (required runs first).
     */
    public function testRequiredPatternFieldEmptyValueFailsWithRequiredFirst(): void
    {
        $form = $this->formWithPattern('^[A-Za-z]+$', required: true);

        $result = $this->processor->process($form, ['code' => '']);

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        $this->assertArrayHasKey('code', $errors);
        // The error is about "required", not about the pattern.
        $this->assertStringContainsStringIgnoringCase('required', $errors['code'][0]);
    }

    /**
     * Required field with pattern — valid non-empty value must pass both checks.
     */
    public function testRequiredPatternFieldValidValuePasses(): void
    {
        $form = $this->formWithPattern('^[A-Za-z]+$', required: true);

        $result = $this->processor->process($form, ['code' => 'HelloWorld']);

        $this->assertTrue($result->isValid());
        $this->assertSame('HelloWorld', $this->processedData($result)['code']);
    }

    /**
     * Required field with pattern — non-empty value that doesn't match the
     * pattern must fail with the pattern error (required was satisfied).
     */
    public function testRequiredPatternFieldInvalidNonEmptyValueFailsWithPatternError(): void
    {
        $form = $this->formWithPattern('^[A-Za-z]+$', required: true);

        $result = $this->processor->process($form, ['code' => '12345']);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('code', $result->getErrors());
    }

    /**
     * @return array<string, mixed>
     */
    private function processedData(ProcessResultInterface $result): array
    {
        $data = $result->getProcessedData();
        $this->assertIsArray($data);

        return $data;
    }

    // =========================================================================
    // Boolean that is fixed to a value
    // =========================================================================

    /**
     * @param array<string, mixed> $property
     */
    private function formWithBoolean(array $property, bool $required): Form
    {
        return Form::fromArray([
            'options' => ['csrf_protection' => false],
            'schema' => [
                'type' => 'object',
                'properties' => ['terms' => ['type' => 'boolean'] + $property],
                'required' => $required ? ['terms'] : [],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [['type' => 'Control', 'scope' => '#/properties/terms']],
            ],
        ]);
    }

    public function testABooleanThatMustBeTrueFailsWhenItIsNotChecked(): void
    {
        foreach ([['const' => true], ['enum' => [true]]] as $property) {
            $form = $this->formWithBoolean($property, true);

            $this->assertTrue($this->processor->process($form, [])->hasErrors());
            $this->assertTrue($this->processor->process($form, ['terms' => '0'])->hasErrors());

            $result = $this->processor->process($form, ['terms' => '1']);
            $this->assertFalse($result->hasErrors());
            $this->assertSame(['terms' => true], $result->getProcessedData());
        }
    }

    public function testABooleanThatMustBeFalseFailsWhenItIsChecked(): void
    {
        $form = $this->formWithBoolean(['const' => false], false);

        $this->assertTrue($this->processor->process($form, ['terms' => '1'])->hasErrors());
        $this->assertFalse($this->processor->process($form, [])->hasErrors());
    }

    public function testABooleanThatIsRequiredOnlyNeedsTheKey(): void
    {
        $form = $this->formWithBoolean([], true);

        $this->assertFalse($this->processor->process($form, ['terms' => '0'])->hasErrors());
        $this->assertFalse($this->processor->process($form, [])->hasErrors());
    }

    /**
     * A slash that is escaped in the pattern is still a slash of the pattern,
     * not the end of it.
     */
    public function testPatternWithAnEscapedSlashIsProcessed(): void
    {
        $form = $this->formWithPattern('^a\\/b$');

        $this->assertTrue($this->processor->process($form, ['code' => 'a/b'])->isValid());
        $this->assertFalse($this->processor->process($form, ['code' => 'ab'])->isValid());
    }

    /**
     * A field with the format `regex` must have a regular expression.
     */
    public function testFormatRegexNeedsARegularExpression(): void
    {
        $form = Form::fromArray([
            'options' => ['csrf_protection' => false],
            'schema' => [
                'type' => 'object',
                'properties' => ['rule' => ['type' => 'string', 'format' => 'regex']],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [['type' => 'Control', 'scope' => '#/properties/rule']],
            ],
        ]);

        $this->assertTrue($this->processor->process($form, ['rule' => '^[a-z]+/\\d$'])->isValid());
        $this->assertFalse($this->processor->process($form, ['rule' => '(['])->isValid());
    }

    /**
     * Processing a form again starts from the rules that its definition
     * declares, so nothing is added to the ones of the first time.
     */
    public function testAFormThatIsProcessedAgainHasTheSameRules(): void
    {
        $form = Form::fromArray([
            'options' => ['csrf_protection' => false],
            'rules' => ['name' => ['transform' => ['uppercase']]],
            'schema' => ['type' => 'object', 'required' => ['name'], 'properties' => ['name' => ['type' => 'string']]],
            'uischema' => ['type' => 'VerticalLayout', 'elements' => [['type' => 'Control', 'scope' => '#/properties/name']]],
        ]);

        $this->processor->process($form, ['name' => ' ana ']);
        $first = $form->getRules()['name'];
        $result = $this->processor->process($form, ['name' => ' ana ']);

        $this->assertSame($first, $form->getRules()['name']);
        $this->assertSame(['uppercase'], $first['transform'] ?? null);
        $this->assertSame(['trim'], $first['sanitize'] ?? null);
        $this->assertSame(['name' => 'ANA'], $result->getProcessedData());
        $this->assertSame(['name' => ['transform' => ['uppercase']]], $form->toArray()['rules']);
    }
}
