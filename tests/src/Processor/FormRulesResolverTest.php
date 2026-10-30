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

use Derafu\File\Mime;
use Derafu\Form\Abstract\AbstractPropertySchema;
use Derafu\Form\Abstract\AbstractUiSchemaElement;
use Derafu\Form\Factory\FormUiSchemaFactory;
use Derafu\Form\Factory\PropertySchemaFactory;
use Derafu\Form\Factory\UiSchemaElementFactory;
use Derafu\Form\Form;
use Derafu\Form\FormDefinition;
use Derafu\Form\FormField;
use Derafu\Form\Options\FormOptions;
use Derafu\Form\Processor\FormRulesResolver;
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
 * Unit and integration tests for FormRulesResolver.
 *
 * Tests use real Form objects built via Form::fromArray() — no mocks.
 */
#[CoversClass(FormRulesResolver::class)]
#[CoversClass(PropertySchemaFactory::class)]
#[CoversClass(FormRules::class)]
#[CoversClass(AbstractPropertySchema::class)]
#[CoversClass(AbstractUiSchemaElement::class)]
#[CoversClass(FormUiSchemaFactory::class)]
#[CoversClass(UiSchemaElementFactory::class)]
#[CoversClass(Form::class)]
#[CoversClass(FormField::class)]
#[CoversClass(FormOptions::class)]
#[CoversClass(ArraySchema::class)]
#[CoversClass(BooleanSchema::class)]
#[CoversClass(FormSchema::class)]
#[CoversClass(IntegerSchema::class)]
#[CoversClass(NumberSchema::class)]
#[CoversTrait(ObjectSchemaTrait::class)]
#[CoversClass(StringSchema::class)]
#[CoversClass(Control::class)]
#[CoversClass(VerticalLayout::class)]
#[CoversClass(Widget::class)]
#[CoversClass(WidgetFactory::class)]
#[UsesClass(PropertyDefinition::class)]
#[UsesClass(UiSchemaElementDefinition::class)]
#[UsesClass(FormDefinition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Options\FormAttributes::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Schema\Pattern::class)]
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

    /**
     * @param array<string, mixed> $expectedRules
     * @param array<string, mixed> $schema
     */
    #[DataProvider('stringTypeProvider')]
    public function testStringTypeMapping(array $schema, array $expectedRules): void
    {
        $this->assertSame(
            $expectedRules,
            $this->resolver->mapSchemaToRules(PropertySchemaFactory::create($schema))
        );
    }

    /**
     * @return array<int|string, array<int, mixed>>
     */
    public static function stringTypeProvider(): array
    {
        return [
            'basic_string' => [
                ['type' => 'string'],
                ['sanitize' => ['trim'], 'cast' => 'string'],
            ],
            'string_with_length_constraints' => [
                ['type' => 'string', 'minLength' => 3, 'maxLength' => 100],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['min_length:3', 'max_length:100']],
            ],
            'email_string' => [
                ['type' => 'string', 'format' => 'email', 'minLength' => 3, 'maxLength' => 80],
                ['transform' => ['lowercase'], 'sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['min_length:3', 'max_length:80', 'email']],
            ],
            'string_with_pattern' => [
                ['type' => 'string', 'pattern' => '^[A-Za-z]+$'],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['regex:/^[A-Za-z]+$/']],
            ],
            'string_with_enum' => [
                ['type' => 'string', 'enum' => ['pending', 'approved', 'rejected']],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['in:pending,approved,rejected']],
            ],
            'string_with_oneOf' => [
                [
                    'type' => 'string',
                    'oneOf' => [
                        ['const' => 'draft', 'title' => 'Draft'],
                        ['const' => 'active', 'title' => 'Active'],
                        ['const' => 'archived', 'title' => 'Archived'],
                    ],
                ],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['in:draft,active,archived']],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $expectedRules
     * @param array<string, mixed> $schema
     */
    #[DataProvider('numericTypeProvider')]
    public function testNumericTypeMapping(array $schema, array $expectedRules): void
    {
        $this->assertSame(
            $expectedRules,
            $this->resolver->mapSchemaToRules(PropertySchemaFactory::create($schema))
        );
    }

    /**
     * @return array<int|string, array<int, mixed>>
     */
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
            'integer_with_exclusive_limits' => [
                ['type' => 'integer', 'exclusiveMinimum' => 0, 'exclusiveMaximum' => 10],
                ['cast' => 'integer', 'validate' => ['int', 'gt:0', 'lt:10']],
            ],
            'integer_multiple_of' => [
                ['type' => 'integer', 'multipleOf' => 5],
                ['cast' => 'integer', 'validate' => ['int', 'multiple_of:5']],
            ],
            'number_with_exclusive_limits' => [
                ['type' => 'number', 'exclusiveMinimum' => 0.5, 'exclusiveMaximum' => 9.5],
                ['cast' => 'float', 'validate' => ['numeric', 'gt:0.5', 'lt:9.5']],
            ],
            'number_multiple_of' => [
                ['type' => 'number', 'multipleOf' => 0.25],
                ['cast' => 'float', 'validate' => ['numeric', 'multiple_of:0.25']],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $expectedRules
     * @param array<string, mixed> $schema
     */
    #[DataProvider('arrayTypeProvider')]
    public function testArrayTypeMapping(array $schema, array $expectedRules): void
    {
        $this->assertSame(
            $expectedRules,
            $this->resolver->mapSchemaToRules(PropertySchemaFactory::create($schema))
        );
    }

    /**
     * @return array<int|string, array<int, mixed>>
     */
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
            'array_of_choices' => [
                ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a', 'b']]],
                ['validate' => ['in:a,b']],
            ],
            'array_of_items_without_choices' => [
                ['type' => 'array', 'items' => ['type' => 'string']],
                [],
            ],
            'array_contains_const' => [
                ['type' => 'array', 'contains' => ['const' => 'terms']],
                ['validate' => ['contains:terms']],
            ],
            'array_contains_enum' => [
                ['type' => 'array', 'contains' => ['enum' => ['a', 'b']]],
                ['validate' => ['contains:a,b']],
            ],
            'array_contains_escapes_commas' => [
                ['type' => 'array', 'contains' => ['const' => 'a,b']],
                ['validate' => ['contains:a\\,b']],
            ],
            'array_contains_number' => [
                ['type' => 'array', 'contains' => ['const' => 2]],
                ['validate' => ['contains:2']],
            ],
            'array_min_contains' => [
                ['type' => 'array', 'contains' => ['const' => 'a'], 'minContains' => 2],
                ['validate' => ['min_contains:2,a']],
            ],
            'array_min_contains_zero' => [
                ['type' => 'array', 'contains' => ['const' => 'a'], 'minContains' => 0],
                [],
            ],
            'array_max_contains' => [
                ['type' => 'array', 'contains' => ['const' => 'a'], 'maxContains' => 3],
                ['validate' => ['contains:a', 'max_contains:3,a']],
            ],
            'array_min_contains_zero_and_max_contains' => [
                ['type' => 'array', 'contains' => ['const' => 'a'], 'minContains' => 0, 'maxContains' => 1],
                ['validate' => ['max_contains:1,a']],
            ],
            'array_min_and_max_contains' => [
                ['type' => 'array', 'contains' => ['enum' => ['a', 'b']], 'minContains' => 1, 'maxContains' => 2],
                ['validate' => ['min_contains:1,a,b', 'max_contains:2,a,b']],
            ],
            'array_contains_without_const_or_enum' => [
                ['type' => 'array', 'contains' => ['type' => 'string']],
                [],
            ],
            'array_contains_boolean' => [
                ['type' => 'array', 'contains' => ['const' => true]],
                [],
            ],
            'array_contains_enum_with_null' => [
                ['type' => 'array', 'contains' => ['enum' => ['a', null]]],
                [],
            ],
            'array_contains_empty_text' => [
                ['type' => 'array', 'contains' => ['const' => '']],
                [],
            ],
            'array_contains_empty_enum' => [
                ['type' => 'array', 'contains' => ['enum' => []]],
                [],
            ],
            'array_min_contains_without_contains' => [
                ['type' => 'array', 'minContains' => 2, 'maxContains' => 3],
                [],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $expectedRules
     * @param array<string, mixed> $schema
     */
    #[DataProvider('booleanTypeProvider')]
    public function testBooleanTypeMapping(array $schema, array $expectedRules): void
    {
        $this->assertSame(
            $expectedRules,
            $this->resolver->mapSchemaToRules(PropertySchemaFactory::create($schema))
        );
    }

    /**
     * @return array<int|string, array<int, mixed>>
     */
    public static function booleanTypeProvider(): array
    {
        return [
            'boolean_type' => [['type' => 'boolean'], ['cast' => 'boolean']],
            'boolean_const_true' => [['type' => 'boolean', 'const' => true], ['cast' => 'boolean', 'validate' => ['accepted']]],
            'boolean_enum_true' => [['type' => 'boolean', 'enum' => [true]], ['cast' => 'boolean', 'validate' => ['accepted']]],
            'boolean_const_false' => [['type' => 'boolean', 'const' => false], ['cast' => 'boolean', 'validate' => ['declined']]],
            'boolean_enum_false' => [['type' => 'boolean', 'enum' => [false]], ['cast' => 'boolean', 'validate' => ['declined']]],
            'boolean_enum_both' => [['type' => 'boolean', 'enum' => [true, false]], ['cast' => 'boolean']],
        ];
    }

    /**
     * @param array<string, mixed> $expectedRules
     * @param array<string, mixed> $schema
     */
    #[DataProvider('formatMappingProvider')]
    public function testFormatMapping(array $schema, array $expectedRules): void
    {
        $this->assertSame(
            $expectedRules,
            $this->resolver->mapSchemaToRules(PropertySchemaFactory::create($schema))
        );
    }

    /**
     * @return array<int|string, array<int, mixed>>
     */
    public static function formatMappingProvider(): array
    {
        return [
            'email_format' => [
                ['type' => 'string', 'format' => 'email'],
                ['transform' => ['lowercase'], 'sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['email']],
            ],
            'regex_format' => [
                ['type' => 'string', 'format' => 'regex'],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['regex_pattern']],
            ],
            'url_format' => [
                ['type' => 'string', 'format' => 'url'],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['url']],
            ],
            'uri_format' => [
                ['type' => 'string', 'format' => 'uri'],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['url']],
            ],
            'date_format' => [
                ['type' => 'string', 'format' => 'date'],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['date_format:Y-m-d']],
            ],
            'date_time_format' => [
                ['type' => 'string', 'format' => 'date-time'],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['datetime_local']],
            ],
            'tel_format' => [
                ['type' => 'string', 'format' => 'tel'],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['regex:/^[\+]?[0-9\s\-\(\)]+$/']],
            ],
            'base64_format' => [
                ['type' => 'string', 'format' => 'base64'],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['base64']],
            ],
            'json_format' => [
                ['type' => 'string', 'format' => 'json'],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['json']],
            ],
            'json_content_media_type' => [
                ['type' => 'string', 'contentMediaType' => 'application/json'],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['json']],
            ],
            'time' => [
                ['type' => 'string', 'format' => 'time'],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['time']],
            ],
            'hostname' => [
                ['type' => 'string', 'format' => 'hostname'],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['hostname']],
            ],
            'ipv4' => [
                ['type' => 'string', 'format' => 'ipv4'],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['ip:v4']],
            ],
            'ipv6' => [
                ['type' => 'string', 'format' => 'ipv6'],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['ip:v6']],
            ],
            'color' => [
                ['type' => 'string', 'format' => 'color'],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['color']],
            ],
            'month' => [
                ['type' => 'string', 'format' => 'month'],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['month']],
            ],
            'week' => [
                ['type' => 'string', 'format' => 'week'],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['week']],
            ],
            'password_is_not_trimmed' => [
                ['type' => 'string', 'format' => 'password'],
                ['cast' => 'string'],
            ],
            'format_without_rule' => [
                ['type' => 'string', 'format' => 'duration'],
                ['sanitize' => ['trim'], 'cast' => 'string'],
            ],
            'content_media_type_without_rule' => [
                ['type' => 'string', 'contentMediaType' => 'text/plain'],
                ['sanitize' => ['trim'], 'cast' => 'string'],
            ],
            'const_string' => [
                ['type' => 'string', 'const' => 'yes'],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['in:yes']],
            ],
            'const_with_a_comma' => [
                ['type' => 'string', 'const' => 'a,b'],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['in:a\\,b']],
            ],
            'enum_with_a_comma' => [
                ['type' => 'string', 'enum' => ['a,b', 'c']],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['in:a\\,b,c']],
            ],
            'const_integer' => [
                ['type' => 'integer', 'const' => 5],
                ['cast' => 'integer', 'validate' => ['in:5', 'int']],
            ],
            'const_number' => [
                ['type' => 'number', 'const' => 1.5],
                ['cast' => 'float', 'validate' => ['in:1.5', 'numeric']],
            ],
            'const_that_is_not_scalar' => [
                ['type' => 'string', 'const' => ['a']],
                ['sanitize' => ['trim'], 'cast' => 'string'],
            ],
            'uuid' => [
                ['type' => 'string', 'format' => 'uuid'],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['uuid']],
            ],
            'text_json_content_media_type' => [
                ['type' => 'string', 'contentMediaType' => 'text/json'],
                ['sanitize' => ['trim'], 'cast' => 'string', 'validate' => ['json']],
            ],
        ];
    }

    // =========================================================================
    // resolve() — UI control options via real Form objects
    // =========================================================================

    /**
     * @param array<string, mixed> $controlOptions
     * @param array<string, mixed> $schemaProperty
     */
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

    /**
     * @param array<string, mixed> $controlOptions
     * @param array<string, mixed> $schemaProperty
     */
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

    public function testPasswordControlHasCastButItIsNotTrimmed(): void
    {
        $form = $this->formWith('pass', ['type' => 'string'], ['type' => 'password']);
        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $this->assertSame('string', $rules->getCastRule('pass'));
        $this->assertNotContains('trim', $rules->getSanitizeRules('pass'));
    }

    /**
     * @param array<string, mixed> $schema
     * @param list<string> $expected
     */
    #[DataProvider('controlTypeRulesProvider')]
    public function testControlTypeDerivesItsValidateRule(array $schema, string $controlType, array $expected): void
    {
        $form = $this->formWith('field', $schema, ['type' => $controlType]);
        $this->resolver->resolve($form);

        $this->assertSame($expected, $form->getRules()->getValidateRules('field'));
    }

    /**
     * @return array<string, array{array<string, mixed>, string, list<string>}>
     */
    public static function controlTypeRulesProvider(): array
    {
        $string = ['type' => 'string'];

        return [
            'ipv4' => [$string, 'ipv4', ['ip:v4']],
            'ipv6' => [$string, 'ipv6', ['ip:v6']],
            'hostname' => [$string, 'hostname', ['hostname']],
            'uuid' => [$string, 'uuid', ['uuid']],
            'url' => [$string, 'url', ['url']],
            'time' => [$string, 'time', ['time']],
            'color' => [$string, 'color', ['color']],
            'month' => [$string, 'month', ['month']],
            'week' => [$string, 'week', ['week']],
            'control and format of the same rule' => [['type' => 'string', 'format' => 'ipv4'], 'ipv4', ['ip:v4']],
            'control with the rules of the schema' => [['type' => 'string', 'minLength' => 3], 'uuid', ['min_length:3', 'uuid']],
            'control without rule' => [$string, 'text', []],
        ];
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

    /**
     * @param array<string, mixed> $properties
     * @param list<string> $required
     * @param array<string, mixed> $rules
     * @param list<array<string, mixed>> $uiElements
     */
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
        $direct = $this->resolver->mapSchemaToRules(
            PropertySchemaFactory::create(['type' => 'integer', 'minimum' => 0])
        );

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

    /**
     * @param array<string, mixed> $options
     * @param list<string> $expected
     */
    #[DataProvider('fileControlRulesProvider')]
    public function testFileControlRulesComeFromItsOptions(array $options, array $expected): void
    {
        $form = $this->formWithRules(
            ['document' => ['type' => 'string']],
            [['type' => 'Control', 'scope' => '#/properties/document', 'options' => $options]],
            []
        );

        $this->resolver->resolve($form);

        $this->assertSame($expected, $form->getRules()->getValidateRules('document'));
    }

    /**
     * @param array<string, mixed> $options
     * @param list<string> $explicit
     * @param list<string> $expected
     */
    #[DataProvider('fileControlWithExplicitRulesProvider')]
    public function testFileControlJoinsTheExplicitMimetypeWithItsAccept(array $options, array $explicit, array $expected): void
    {
        $form = $this->formWithRules(
            ['document' => ['type' => 'string']],
            [['type' => 'Control', 'scope' => '#/properties/document', 'options' => $options]],
            ['document' => ['validate' => $explicit]]
        );

        $this->resolver->resolve($form);

        $this->assertSame($expected, $form->getRules()->getValidateRules('document'));
    }

    /**
     * @return array<string, array{array<string, mixed>, list<string>, list<string>}>
     */
    public static function fileControlWithExplicitRulesProvider(): array
    {
        $pdf = implode(',', Mime::typesOfExtension('pdf'));

        return [
            'explicit types are added to the ones of accept' => [
                ['type' => 'file', 'accept' => '.pdf'],
                ['mimetype:image/png'],
                ['file', 'mimetype:' . $pdf . ',image/png'],
            ],
            'explicit types are added to a wildcard' => [
                ['type' => 'file', 'accept' => 'image/*'],
                ['mimetype:application/pdf, image/png'],
                ['file', 'mimetype:image/*,application/pdf,image/png'],
            ],
            'explicit types alone when accept can not be derived' => [
                ['type' => 'file', 'accept' => '.derafu'],
                ['mimetype:application/pdf'],
                ['file', 'mimetype:application/pdf'],
            ],
            'explicit types alone without accept' => [
                ['type' => 'file'],
                ['mimetype:application/pdf'],
                ['file', 'mimetype:application/pdf'],
            ],
            'explicit types with a minimum size of zero' => [
                ['type' => 'file', 'min_size' => 0],
                ['mimetype:application/pdf'],
                ['file:min_size=0', 'mimetype:application/pdf,application/x-empty'],
            ],
            'a type that is in both is only once' => [
                ['type' => 'file', 'accept' => 'application/pdf'],
                ['mimetype:application/pdf'],
                ['file', 'mimetype:application/pdf'],
            ],
            'the other explicit rules are kept after' => [
                ['type' => 'file', 'accept' => 'image/*'],
                ['mimetype:image/png', 'no_whitespace'],
                ['file', 'mimetype:image/*,image/png', 'no_whitespace'],
            ],
            'a mimetype without types is left to fail' => [
                ['type' => 'file', 'accept' => 'image/*'],
                ['mimetype'],
                ['file', 'mimetype:image/*', 'mimetype'],
            ],
            'a rule with a name that only starts as mimetype is another rule' => [
                ['type' => 'file'],
                ['mimetype_other:a'],
                ['file', 'mimetype_other:a'],
            ],
        ];
    }

    /**
     * @return array<string, array{array<string, mixed>, list<string>}>
     */
    public static function fileControlRulesProvider(): array
    {
        // The types that an extension has are the ones that `File` knows.
        $pdf = implode(',', Mime::typesOfExtension('pdf'));
        $jpg = implode(',', Mime::typesOfExtension('jpg'));
        $png = implode(',', Mime::typesOfExtension('png'));

        return [
            'file without options' => [['type' => 'file'], ['file']],
            'maximum size' => [['type' => 'file', 'max_size' => '2M'], ['file:max_size=2M']],
            'both sizes' => [['type' => 'file', 'min_size' => 0, 'max_size' => '2M'], ['file:max_size=2M,min_size=0']],
            'sizes that are not text or integers' => [['type' => 'file', 'min_size' => true, 'max_size' => ['2M']], ['file']],
            'image control' => [['type' => 'image', 'max_size' => 1024], ['image:max_size=1024']],
            'accept with a type' => [['type' => 'file', 'accept' => 'application/pdf'], ['file', 'mimetype:application/pdf']],
            'accept with an extension' => [['type' => 'file', 'accept' => '.pdf'], ['file', 'mimetype:' . $pdf]],
            'accept with several' => [
                ['type' => 'file', 'accept' => ' .pdf, application/pdf ,Image/PNG,'],
                ['file', 'mimetype:' . $pdf . ',image/png'],
            ],
            'accept with the types of an extension' => [['type' => 'file', 'accept' => '.jpg'], ['file', 'mimetype:' . $jpg]],
            'accept with a wildcard' => [['type' => 'file', 'accept' => '.pdf,image/*'], ['file', 'mimetype:' . $pdf . ',image/*']],
            'accept with a wildcard of the whole type and subtype' => [['type' => 'file', 'accept' => '.pdf,*/*'], ['file']],
            'accept with an extension that is not known' => [['type' => 'file', 'accept' => '.pdf,.derafu'], ['file']],
            'accept with a word' => [['type' => 'file', 'accept' => 'pdf'], ['file']],
            'accept that is empty' => [['type' => 'file', 'accept' => ' , '], ['file']],
            'accept that is not text' => [['type' => 'file', 'accept' => ['.pdf']], ['file']],
            'accept and a minimum size of zero' => [
                ['type' => 'file', 'accept' => '.pdf', 'min_size' => 0],
                ['file:min_size=0', 'mimetype:' . $pdf . ',application/x-empty'],
            ],
            'accept and a minimum size that is zero with a unit' => [
                ['type' => 'file', 'accept' => '.pdf', 'min_size' => '0K'],
                ['file:min_size=0K', 'mimetype:' . $pdf . ',application/x-empty'],
            ],
            'accept and a minimum size that is not zero' => [
                ['type' => 'file', 'accept' => '.pdf', 'min_size' => '1K'],
                ['file:min_size=1K', 'mimetype:' . $pdf],
            ],
            'wildcard and a minimum size of zero' => [
                ['type' => 'file', 'accept' => 'image/*', 'min_size' => 0],
                ['file:min_size=0', 'mimetype:image/*,application/x-empty'],
            ],
            'image control with accept' => [
                ['type' => 'image', 'accept' => '.png'],
                ['image', 'mimetype:' . $png],
            ],
        ];
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

    public function testExplicitRulesCanBeStringsSeparatedByPipes(): void
    {
        $form = $this->formWithRules(
            ['sku' => ['type' => 'string', 'title' => 'SKU']],
            [['type' => 'Control', 'scope' => '#/properties/sku']],
            ['sku' => ['sanitize' => 'spaces|strip_tags', 'validate' => 'alpha_num']]
        );

        $this->resolver->resolve($form);
        $rules = $form->getRules();

        $this->assertSame(['trim', 'spaces', 'strip_tags'], $rules->getSanitizeRules('sku'));
        $this->assertSame(['alpha_num'], $rules->getValidateRules('sku'));
    }
}
