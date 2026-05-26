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

use Derafu\Form\Abstract\AbstractPropertySchema;
use Derafu\Form\Abstract\AbstractUiSchemaElement;
use Derafu\Form\Factory\FormUiSchemaFactory;
use Derafu\Form\Factory\UiSchemaElementFactory;
use Derafu\Form\Form;
use Derafu\Form\FormField;
use Derafu\Form\Options\FormOptions;
use Derafu\Form\Processor\FormRulesResolver;
use Derafu\Form\Rules\FormRules;
use Derafu\Form\Schema\BooleanSchema;
use Derafu\Form\Schema\FormSchema;
use Derafu\Form\Schema\IntegerSchema;
use Derafu\Form\Schema\NumberSchema;
use Derafu\Form\Schema\ObjectSchemaTrait;
use Derafu\Form\Schema\StringSchema;
use Derafu\Form\UiSchema\Control;
use Derafu\Form\UiSchema\VerticalLayout;
use Derafu\Form\Widget\Widget;
use Derafu\Form\Widget\WidgetFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit and integration tests for FormRulesResolver.
 *
 * Tests use real Form objects built via Form::fromArray() — no mocks.
 */
#[CoversClass(FormRulesResolver::class)]
#[CoversClass(FormRules::class)]
#[CoversClass(AbstractPropertySchema::class)]
#[CoversClass(AbstractUiSchemaElement::class)]
#[CoversClass(FormUiSchemaFactory::class)]
#[CoversClass(UiSchemaElementFactory::class)]
#[CoversClass(Form::class)]
#[CoversClass(FormField::class)]
#[CoversClass(FormOptions::class)]
#[CoversClass(BooleanSchema::class)]
#[CoversClass(FormSchema::class)]
#[CoversClass(IntegerSchema::class)]
#[CoversClass(NumberSchema::class)]
#[CoversClass(ObjectSchemaTrait::class)]
#[CoversClass(StringSchema::class)]
#[CoversClass(Control::class)]
#[CoversClass(VerticalLayout::class)]
#[CoversClass(Widget::class)]
#[CoversClass(WidgetFactory::class)]
final class FormRulesResolverTest extends TestCase
{
    private FormRulesResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new FormRulesResolver();
    }

    // =========================================================================
    // mapSchemaToRules — pure schema derivation (no form needed)
    // =========================================================================

    #[DataProvider('stringTypeProvider')]
    public function testStringTypeMapping(array $schema, array $expectedRules): void
    {
        $this->assertSame($expectedRules, $this->resolver->mapSchemaToRules($schema));
    }

    public static function stringTypeProvider(): array
    {
        return [
            'basic_string' => [
                ['type' => 'string'],
                ['cast' => 'string', 'sanitize' => ['trim']],
            ],
            'string_with_length_constraints' => [
                ['type' => 'string', 'minLength' => 3, 'maxLength' => 100],
                ['cast' => 'string', 'sanitize' => ['trim'], 'validate' => ['min_length:3', 'max_length:100']],
            ],
            'email_string' => [
                ['type' => 'string', 'format' => 'email', 'minLength' => 3, 'maxLength' => 80],
                ['cast' => 'string', 'sanitize' => ['trim'], 'transform' => ['lowercase'], 'validate' => ['min_length:3', 'max_length:80', 'email']],
            ],
            'required_string' => [
                ['type' => 'string', 'required' => true],
                ['cast' => 'string', 'sanitize' => ['trim'], 'validate' => ['required']],
            ],
            'string_with_pattern' => [
                ['type' => 'string', 'pattern' => '^[A-Za-z]+$'],
                ['cast' => 'string', 'sanitize' => ['trim'], 'validate' => ['regex:/^[A-Za-z]+$/']],
            ],
            'string_with_enum' => [
                ['type' => 'string', 'enum' => ['pending' => 'pending', 'approved' => 'approved', 'rejected' => 'rejected']],
                ['cast' => 'string', 'sanitize' => ['trim'], 'validate' => ['in:pending,approved,rejected']],
            ],
        ];
    }

    #[DataProvider('numericTypeProvider')]
    public function testNumericTypeMapping(array $schema, array $expectedRules): void
    {
        $this->assertSame($expectedRules, $this->resolver->mapSchemaToRules($schema));
    }

    public static function numericTypeProvider(): array
    {
        return [
            'integer_type' => [
                ['type' => 'integer'],
                ['cast' => 'integer', 'validate' => ['int']],
            ],
            'integer_with_range' => [
                ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                ['cast' => 'integer', 'validate' => ['int', 'gte:0', 'lte:100']],
            ],
            'number_type' => [
                ['type' => 'number'],
                ['cast' => 'float', 'validate' => ['numeric']],
            ],
            'number_with_range' => [
                ['type' => 'number', 'minimum' => 0.0, 'maximum' => 100.0],
                ['cast' => 'float', 'validate' => ['numeric', 'gte:0', 'lte:100']],
            ],
        ];
    }

    #[DataProvider('arrayTypeProvider')]
    public function testArrayTypeMapping(array $schema, array $expectedRules): void
    {
        $this->assertSame($expectedRules, $this->resolver->mapSchemaToRules($schema));
    }

    public static function arrayTypeProvider(): array
    {
        return [
            'array_type' => [['type' => 'array'], []],
            'array_with_size_constraints' => [
                ['type' => 'array', 'minItems' => 1, 'maxItems' => 10],
                ['validate' => ['min_items:1', 'max_items:10']],
            ],
            'array_with_unique_items' => [
                ['type' => 'array', 'uniqueItems' => true],
                ['validate' => ['unique']],
            ],
        ];
    }

    #[DataProvider('booleanTypeProvider')]
    public function testBooleanTypeMapping(array $schema, array $expectedRules): void
    {
        $this->assertSame($expectedRules, $this->resolver->mapSchemaToRules($schema));
    }

    public static function booleanTypeProvider(): array
    {
        return [
            'boolean_type' => [['type' => 'boolean'], ['cast' => 'boolean']],
        ];
    }

    #[DataProvider('formatMappingProvider')]
    public function testFormatMapping(array $schema, array $expectedRules): void
    {
        $this->assertSame($expectedRules, $this->resolver->mapSchemaToRules($schema));
    }

    public static function formatMappingProvider(): array
    {
        return [
            'email_format' => [
                ['type' => 'string', 'format' => 'email'],
                ['cast' => 'string', 'sanitize' => ['trim'], 'transform' => ['lowercase'], 'validate' => ['email']],
            ],
            'uri_format' => [
                ['type' => 'string', 'format' => 'uri'],
                ['cast' => 'string', 'sanitize' => ['trim'], 'validate' => ['url']],
            ],
            'date_format' => [
                ['type' => 'string', 'format' => 'date'],
                ['cast' => 'string', 'sanitize' => ['trim'], 'validate' => ['date_format:Y-m-d']],
            ],
            'date_time_format' => [
                ['type' => 'string', 'format' => 'date-time'],
                ['cast' => 'string', 'sanitize' => ['trim'], 'validate' => ['date_format:Y-m-d H:i:s']],
            ],
            'tel_format' => [
                ['type' => 'string', 'format' => 'tel'],
                ['cast' => 'string', 'sanitize' => ['trim'], 'validate' => ['regex:/^[\+]?[0-9\s\-\(\)]+$/']],
            ],
            'base64_format' => [
                ['type' => 'string', 'format' => 'base64'],
                ['cast' => 'string', 'sanitize' => ['trim'], 'validate' => ['base64']],
            ],
            'json_format' => [
                ['type' => 'string', 'format' => 'json'],
                ['cast' => 'string', 'sanitize' => ['trim'], 'validate' => ['json']],
            ],
            'json_content_media_type' => [
                ['type' => 'string', 'contentMediaType' => 'application/json'],
                ['cast' => 'string', 'sanitize' => ['trim'], 'validate' => ['json']],
            ],
        ];
    }

    public function testEmptySchemaReturnsEmptyRules(): void
    {
        $this->assertSame([], $this->resolver->mapSchemaToRules([]));
    }

    public function testSchemaWithoutTypeReturnsEmptyRules(): void
    {
        $this->assertSame([], $this->resolver->mapSchemaToRules(['title' => 'Test Field']));
    }

    // =========================================================================
    // resolve() — UI control options via real Form objects
    // =========================================================================

    private function formWith(string $fieldName, array $schemaProperty, array $controlOptions = []): Form
    {
        $element = ['type' => 'Control', 'scope' => "#/properties/{$fieldName}"];
        if (!empty($controlOptions)) {
            $element['options'] = $controlOptions;
        }

        return Form::fromArray([
            'schema' => [
                'type' => 'object',
                'properties' => [$fieldName => $schemaProperty],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [$element],
            ],
        ]);
    }

    private function formWithRequired(string $fieldName, array $schemaProperty, array $controlOptions = []): Form
    {
        $element = ['type' => 'Control', 'scope' => "#/properties/{$fieldName}"];
        if (!empty($controlOptions)) {
            $element['options'] = $controlOptions;
        }

        return Form::fromArray([
            'schema' => [
                'type' => 'object',
                'properties' => [$fieldName => $schemaProperty],
                'required' => [$fieldName],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [$element],
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Editor control
    // -------------------------------------------------------------------------

    public function testEditorControlAddsStripTagsTransform(): void
    {
        $form = $this->formWith('content', ['type' => 'string'], ['type' => 'editor']);
        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $this->assertContains('strip_tags', $rules->getTransformRules('content'));
    }

    public function testEditorControlKeepsCastAndSanitize(): void
    {
        $form = $this->formWith('content', ['type' => 'string'], ['type' => 'editor']);
        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $this->assertSame('string', $rules->getCastRule('content'));
        $this->assertContains('trim', $rules->getSanitizeRules('content'));
    }

    // -------------------------------------------------------------------------
    // File control
    // -------------------------------------------------------------------------

    public function testFileControlHasNoCastRule(): void
    {
        $form = $this->formWith('avatar', ['type' => 'string'], ['type' => 'file']);
        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $this->assertNull($rules->getCastRule('avatar'));
    }

    public function testFileControlHasNoSanitizeRules(): void
    {
        $form = $this->formWith('avatar', ['type' => 'string'], ['type' => 'file']);
        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $this->assertSame([], $rules->getSanitizeRules('avatar'));
    }

    public function testFileControlHasNoTransformRules(): void
    {
        $form = $this->formWith('avatar', ['type' => 'string'], ['type' => 'file']);
        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $this->assertSame([], $rules->getTransformRules('avatar'));
    }

    public function testFileControlHasFileValidation(): void
    {
        $form = $this->formWith('avatar', ['type' => 'string'], ['type' => 'file']);
        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $this->assertContains('file', $rules->getValidateRules('avatar'));
    }

    public function testFileControlRulesAreExactly(): void
    {
        $form = $this->formWith('avatar', ['type' => 'string'], ['type' => 'file']);
        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $this->assertSame(['validate' => ['file']], $rules['avatar']);
    }

    // -------------------------------------------------------------------------
    // Image control
    // -------------------------------------------------------------------------

    public function testImageControlHasNoCastRule(): void
    {
        $form = $this->formWith('photo', ['type' => 'string'], ['type' => 'image']);
        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $this->assertNull($rules->getCastRule('photo'));
    }

    public function testImageControlHasNoSanitizeRules(): void
    {
        $form = $this->formWith('photo', ['type' => 'string'], ['type' => 'image']);
        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $this->assertSame([], $rules->getSanitizeRules('photo'));
    }

    public function testImageControlHasImageValidation(): void
    {
        $form = $this->formWith('photo', ['type' => 'string'], ['type' => 'image']);
        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $this->assertContains('image', $rules->getValidateRules('photo'));
    }

    public function testImageControlRulesAreExactly(): void
    {
        $form = $this->formWith('photo', ['type' => 'string'], ['type' => 'image']);
        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $this->assertSame(['validate' => ['image']], $rules['photo']);
    }

    // -------------------------------------------------------------------------
    // Required fields — 'required' always first
    // -------------------------------------------------------------------------

    public function testRequiredFileFieldHasRequiredBeforeFileRule(): void
    {
        $form = $this->formWithRequired('avatar', ['type' => 'string'], ['type' => 'file']);
        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $validate = $rules->getValidateRules('avatar');
        $this->assertContains('required', $validate);
        $this->assertContains('file', $validate);
        $this->assertLessThan(
            array_search('file', $validate),
            array_search('required', $validate),
            '"required" must appear before "file"'
        );
    }

    public function testRequiredFileFieldHasNoCastOrSanitize(): void
    {
        $form = $this->formWithRequired('avatar', ['type' => 'string'], ['type' => 'file']);
        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $this->assertNull($rules->getCastRule('avatar'));
        $this->assertSame([], $rules->getSanitizeRules('avatar'));
    }

    public function testRequiredImageFieldHasRequiredBeforeImageRule(): void
    {
        $form = $this->formWithRequired('photo', ['type' => 'string'], ['type' => 'image']);
        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $validate = $rules->getValidateRules('photo');
        $this->assertLessThan(
            array_search('image', $validate),
            array_search('required', $validate),
            '"required" must appear before "image"'
        );
    }

    public function testRequiredStringFieldHasRequiredFirstInValidate(): void
    {
        $form = $this->formWithRequired('name', ['type' => 'string', 'minLength' => 3]);
        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $validate = $rules->getValidateRules('name');
        $this->assertSame('required', $validate[0]);
        $this->assertContains('min_length:3', $validate);
    }

    // -------------------------------------------------------------------------
    // Plain controls must be unaffected by upload logic
    // -------------------------------------------------------------------------

    public function testPlainStringControlHasCastAndSanitize(): void
    {
        $form = $this->formWith('name', ['type' => 'string']);
        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $this->assertSame('string', $rules->getCastRule('name'));
        $this->assertContains('trim', $rules->getSanitizeRules('name'));
    }

    public function testPasswordControlHasCastAndSanitize(): void
    {
        $form = $this->formWith('pass', ['type' => 'string'], ['type' => 'password']);
        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $this->assertSame('string', $rules->getCastRule('pass'));
        $this->assertContains('trim', $rules->getSanitizeRules('pass'));
    }

    public function testIntegerControlHasCastButNoSanitize(): void
    {
        $form = $this->formWith('age', ['type' => 'integer']);
        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $this->assertSame('integer', $rules->getCastRule('age'));
        $this->assertSame([], $rules->getSanitizeRules('age'));
    }

    public function testBooleanControlHasCastButNoSanitize(): void
    {
        $form = $this->formWith('active', ['type' => 'boolean']);
        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $this->assertSame('boolean', $rules->getCastRule('active'));
        $this->assertSame([], $rules->getSanitizeRules('active'));
    }

    // =========================================================================
    // resolve() — explicit rules section (merge logic)
    // =========================================================================

    private function formWithRules(array $properties, array $uiElements, array $rules, array $required = []): Form
    {
        $schema = ['type' => 'object', 'properties' => $properties];
        if (!empty($required)) {
            $schema['required'] = $required;
        }

        return Form::fromArray([
            'schema' => $schema,
            'uischema' => ['type' => 'VerticalLayout', 'elements' => $uiElements],
            'rules' => $rules,
        ]);
    }

    public function testExplicitCastOverridesDerived(): void
    {
        // sort_order is string in schema but the rules section casts it to integer.
        $form = $this->formWithRules(
            ['sort_order' => ['type' => 'string', 'title' => 'Sort Order']],
            [['type' => 'Control', 'scope' => '#/properties/sort_order']],
            ['sort_order' => ['cast' => 'integer']]
        );

        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $this->assertSame('integer', $rules->getCastRule('sort_order'));
        // When cast changes type, string-derived sanitizers (trim) are cleared
        // because they would fail on the newly cast integer value.
        $this->assertSame([], $rules->getSanitizeRules('sort_order'));
    }

    public function testExplicitTransformAppendedAfterDerived(): void
    {
        // slug field: no derived transform, explicit adds lowercase+slug.
        $form = $this->formWithRules(
            ['slug' => ['type' => 'string', 'title' => 'URL Slug']],
            [['type' => 'Control', 'scope' => '#/properties/slug']],
            ['slug' => ['transform' => ['lowercase', 'slug']]]
        );

        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $this->assertSame(['lowercase', 'slug'], $rules->getTransformRules('slug'));
    }

    public function testExplicitSanitizeAppendedAfterDerived(): void
    {
        // sku: derived adds 'trim', explicit adds 'spaces'.
        $form = $this->formWithRules(
            ['sku' => ['type' => 'string', 'title' => 'SKU']],
            [['type' => 'Control', 'scope' => '#/properties/sku']],
            ['sku' => ['sanitize' => ['spaces']]]
        );

        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $this->assertSame(['trim', 'spaces'], $rules->getSanitizeRules('sku'));
    }

    public function testExplicitValidateAppendedAfterDerived(): void
    {
        // price: derived adds 'numeric', explicit adds 'gt:0'.
        $form = $this->formWithRules(
            ['price' => ['type' => 'number', 'title' => 'Price']],
            [['type' => 'Control', 'scope' => '#/properties/price']],
            ['price' => ['validate' => ['gt:0']]]
        );

        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $validate = $rules->getValidateRules('price');
        $this->assertContains('numeric', $validate);
        $this->assertContains('gt:0', $validate);
        $this->assertLessThan(
            array_search('gt:0', $validate),
            array_search('numeric', $validate),
            'Derived rules must precede explicit rules'
        );
    }

    public function testRequiredDeduplicationWhenUserAddsItExplicitly(): void
    {
        // Field is schema-required AND user adds 'required' in rules section.
        // 'required' must appear exactly once at position 0.
        $form = $this->formWithRules(
            ['email' => ['type' => 'string', 'format' => 'email']],
            [['type' => 'Control', 'scope' => '#/properties/email']],
            ['email' => ['validate' => ['required']]],
            ['email']
        );

        $this->resolver->resolve($form);
        $rules = $form->getRules();
        $validate = $rules->getValidateRules('email');

        $this->assertSame('required', $validate[0]);
        $this->assertCount(
            1,
            array_filter($validate, fn (string $r) => $r === 'required'),
            "'required' must appear exactly once"
        );
    }

    public function testFieldWithNoExplicitRulesIsUnchanged(): void
    {
        // stock has no entry in rules section — must equal a direct schema derivation.
        $form = $this->formWithRules(
            ['stock' => ['type' => 'integer', 'minimum' => 0]],
            [['type' => 'Control', 'scope' => '#/properties/stock']],
            []   // no rules for stock
        );

        $this->resolver->resolve($form);
        $rules = $form->getRules();
        $direct = $this->resolver->mapSchemaToRules(['type' => 'integer', 'minimum' => 0]);

        $this->assertSame($direct, $rules['stock']);
    }

    public function testImageControlWithAdditionalValidateRule(): void
    {
        $form = $this->formWithRules(
            ['photo' => ['type' => 'string']],
            [['type' => 'Control', 'scope' => '#/properties/photo', 'options' => ['type' => 'image']]],
            ['photo' => ['validate' => ['mimetype:image/png,image/jpeg']]]
        );

        $this->resolver->resolve($form);
        $rules = $form->getRules();
        $validate = $rules->getValidateRules('photo');

        $this->assertContains('image', $validate);
        $this->assertContains('mimetype:image/png,image/jpeg', $validate);
        $this->assertNull($rules->getCastRule('photo'));
    }

    // =========================================================================
    // FormRules interface behaviour
    // =========================================================================

    public function testFormGetRulesReflectsResolvedStateAfterResolve(): void
    {
        // After resolve(), $form->getRules() must already contain the derived
        // rules — no second call to resolve() should be needed.
        $form = $this->formWith('name', ['type' => 'string']);
        $this->resolver->resolve($form);

        $this->assertSame('string', $form->getRules()->getCastRule('name'));
        $this->assertContains('trim', $form->getRules()->getSanitizeRules('name'));
    }

    public function testArrayAccessReturnsFieldRulesArray(): void
    {
        $form = $this->formWith('name', ['type' => 'string', 'minLength' => 3]);
        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $fieldRules = $rules['name'];
        $this->assertIsArray($fieldRules);
        $this->assertArrayHasKey('cast', $fieldRules);
        $this->assertArrayHasKey('validate', $fieldRules);
    }

    public function testArrayAccessReturnEmptyArrayForUnknownField(): void
    {
        $form = $this->formWith('name', ['type' => 'string']);
        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $this->assertSame([], $rules['nonexistent_field']);
    }

    public function testToArrayRoundTripsBack(): void
    {
        $form = $this->formWith('name', ['type' => 'string', 'minLength' => 3]);
        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $array = $rules->toArray();
        $rebuilt = FormRules::fromArray($array);

        $this->assertSame($rules->toArray(), $rebuilt->toArray());
    }

    public function testOffsetSetThrows(): void
    {
        $rules = FormRules::fromArray(['name' => ['cast' => 'string']]);

        $this->expectException(\BadMethodCallException::class);
        $rules['name'] = ['cast' => 'integer'];
    }

    public function testOffsetUnsetThrows(): void
    {
        $rules = FormRules::fromArray(['name' => ['cast' => 'string']]);

        $this->expectException(\BadMethodCallException::class);
        unset($rules['name']);
    }
}
