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

use Derafu\Form\Contract\FormFieldInterface;
use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\Schema\FormSchemaInterface;
use Derafu\Form\Contract\Schema\PropertySchemaInterface;
use Derafu\Form\Contract\UiSchema\ControlInterface;
use Derafu\Form\Processor\SchemaToRulesMapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SchemaToRulesMapper::mapFieldToRules() expected behavior.
 *
 * Specifically covers the interaction between schema-based rules (cast,
 * sanitize, transform, validate) and UI control types (file, image, editor,
 * password, plain text). The key invariant is that upload controls (file,
 * image) must never receive cast or sanitize rules because their runtime
 * value is an array ($_FILES-style) or a PSR-7 UploadedFileInterface, not
 * a string.
 */
#[CoversClass(SchemaToRulesMapper::class)]
final class SchemaToRulesMapperFileFieldTest extends TestCase
{
    private SchemaToRulesMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new SchemaToRulesMapper();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeField(array $propertySchema, array $controlOptions): FormFieldInterface
    {
        $property = $this->createMock(PropertySchemaInterface::class);
        $property->method('toArray')->willReturn($propertySchema);

        $control = $this->createMock(ControlInterface::class);
        $control->method('getOptions')->willReturn($controlOptions);

        $field = $this->createMock(FormFieldInterface::class);
        $field->method('getProperty')->willReturn($property);
        $field->method('getControl')->willReturn($control);

        return $field;
    }

    private function makeForm(array $fieldMap, array $requiredFields = []): FormInterface
    {
        $schema = $this->createMock(FormSchemaInterface::class);
        $schema->method('getRequired')->willReturn($requiredFields);

        $form = $this->createMock(FormInterface::class);
        $form->method('getSchema')->willReturn($schema);
        $form->method('getFields')->willReturn($fieldMap);

        return $form;
    }

    // -------------------------------------------------------------------------
    // file control
    // -------------------------------------------------------------------------

    public function testFileControlShouldNotHaveCastRule(): void
    {
        $field = $this->makeField(
            ['type' => 'string', 'title' => 'Avatar'],
            ['type' => 'file']
        );

        $rules = $this->mapper->mapFieldToRules($field);

        $this->assertArrayNotHasKey(
            'cast',
            $rules,
            'A file control must not have a cast rule: its value is an array, not a string.'
        );
    }

    public function testFileControlShouldNotHaveSanitizeRule(): void
    {
        $field = $this->makeField(
            ['type' => 'string', 'title' => 'Avatar'],
            ['type' => 'file']
        );

        $rules = $this->mapper->mapFieldToRules($field);

        $this->assertArrayNotHasKey(
            'sanitize',
            $rules,
            'A file control must not have sanitize rules: trim on an array causes errors.'
        );
    }

    public function testFileControlShouldNotHaveTransformRule(): void
    {
        $field = $this->makeField(
            ['type' => 'string', 'title' => 'Avatar'],
            ['type' => 'file']
        );

        $rules = $this->mapper->mapFieldToRules($field);

        $this->assertArrayNotHasKey(
            'transform',
            $rules,
            'A file control must not have transform rules.'
        );
    }

    public function testFileControlShouldHaveFileValidationRule(): void
    {
        $field = $this->makeField(
            ['type' => 'string', 'title' => 'Avatar'],
            ['type' => 'file']
        );

        $rules = $this->mapper->mapFieldToRules($field);

        $this->assertArrayHasKey('validate', $rules);
        $this->assertContains('file', $rules['validate']);
    }

    public function testFileControlRulesAreExactly(): void
    {
        // The complete expected rule set for a file control is just validate:[file].
        $field = $this->makeField(
            ['type' => 'string', 'title' => 'Avatar'],
            ['type' => 'file']
        );

        $rules = $this->mapper->mapFieldToRules($field);

        $this->assertSame(['validate' => ['file']], $rules);
    }

    // -------------------------------------------------------------------------
    // image control
    // -------------------------------------------------------------------------

    public function testImageControlShouldNotHaveCastRule(): void
    {
        $field = $this->makeField(
            ['type' => 'string', 'title' => 'Photo'],
            ['type' => 'image']
        );

        $rules = $this->mapper->mapFieldToRules($field);

        $this->assertArrayNotHasKey(
            'cast',
            $rules,
            'An image control must not have a cast rule.'
        );
    }

    public function testImageControlShouldNotHaveSanitizeRule(): void
    {
        $field = $this->makeField(
            ['type' => 'string', 'title' => 'Photo'],
            ['type' => 'image']
        );

        $rules = $this->mapper->mapFieldToRules($field);

        $this->assertArrayNotHasKey(
            'sanitize',
            $rules,
            'An image control must not have sanitize rules.'
        );
    }

    public function testImageControlShouldHaveImageValidationRule(): void
    {
        $field = $this->makeField(
            ['type' => 'string', 'title' => 'Photo'],
            ['type' => 'image']
        );

        $rules = $this->mapper->mapFieldToRules($field);

        $this->assertArrayHasKey('validate', $rules);
        $this->assertContains('image', $rules['validate']);
    }

    public function testImageControlRulesAreExactly(): void
    {
        $field = $this->makeField(
            ['type' => 'string', 'title' => 'Photo'],
            ['type' => 'image']
        );

        $rules = $this->mapper->mapFieldToRules($field);

        $this->assertSame(['validate' => ['image']], $rules);
    }

    // -------------------------------------------------------------------------
    // required file / image fields (via mapFormToRules)
    // -------------------------------------------------------------------------

    public function testRequiredFileFieldHasRequiredBeforeFileRule(): void
    {
        // Required should appear before file in validate so that a missing
        // upload yields a "required" error rather than "invalid file format".
        $field = $this->makeField(
            ['type' => 'string'],
            ['type' => 'file']
        );

        $form = $this->makeForm(['avatar' => $field], ['avatar']);
        $rules = $this->mapper->mapFormToRules($form);

        $this->assertArrayHasKey('validate', $rules['avatar']);
        $validate = $rules['avatar']['validate'];

        $this->assertContains('required', $validate);
        $this->assertContains('file', $validate);

        $requiredPos = array_search('required', $validate);
        $filePos = array_search('file', $validate);
        $this->assertLessThan(
            $filePos,
            $requiredPos,
            '"required" must appear before "file" so the error message is meaningful.'
        );
    }

    public function testRequiredFileFieldHasNoCastNorSanitize(): void
    {
        $field = $this->makeField(
            ['type' => 'string'],
            ['type' => 'file']
        );

        $form = $this->makeForm(['avatar' => $field], ['avatar']);
        $rules = $this->mapper->mapFormToRules($form);

        $this->assertArrayNotHasKey('cast', $rules['avatar']);
        $this->assertArrayNotHasKey('sanitize', $rules['avatar']);
    }

    public function testRequiredImageFieldHasRequiredBeforeImageRule(): void
    {
        $field = $this->makeField(
            ['type' => 'string'],
            ['type' => 'image']
        );

        $form = $this->makeForm(['photo' => $field], ['photo']);
        $rules = $this->mapper->mapFormToRules($form);

        $validate = $rules['photo']['validate'];
        $this->assertContains('required', $validate);
        $this->assertContains('image', $validate);

        $requiredPos = array_search('required', $validate);
        $imagePos = array_search('image', $validate);
        $this->assertLessThan($imagePos, $requiredPos);
    }

    // -------------------------------------------------------------------------
    // Non-upload controls must be unaffected
    // -------------------------------------------------------------------------

    public function testPlainStringControlHasCastAndSanitize(): void
    {
        $field = $this->makeField(
            ['type' => 'string'],
            [] // no specific control type → plain text
        );

        $rules = $this->mapper->mapFieldToRules($field);

        $this->assertArrayHasKey('cast', $rules);
        $this->assertSame('string', $rules['cast']);
        $this->assertArrayHasKey('sanitize', $rules);
        $this->assertContains('trim', $rules['sanitize']);
    }

    public function testPasswordControlHasCastAndSanitize(): void
    {
        // Passwords are strings at the data level, so cast/sanitize must remain.
        $field = $this->makeField(
            ['type' => 'string'],
            ['type' => 'password']
        );

        $rules = $this->mapper->mapFieldToRules($field);

        $this->assertArrayHasKey('cast', $rules);
        $this->assertSame('string', $rules['cast']);
        $this->assertArrayHasKey('sanitize', $rules);
        $this->assertContains('trim', $rules['sanitize']);
    }

    public function testEditorControlHasCastSanitizeAndStripTags(): void
    {
        $field = $this->makeField(
            ['type' => 'string'],
            ['type' => 'editor']
        );

        $rules = $this->mapper->mapFieldToRules($field);

        $this->assertArrayHasKey('cast', $rules);
        $this->assertSame('string', $rules['cast']);
        $this->assertArrayHasKey('sanitize', $rules);
        $this->assertContains('trim', $rules['sanitize']);
        $this->assertArrayHasKey('transform', $rules);
        $this->assertContains('strip_tags', $rules['transform']);
    }

    public function testIntegerControlHasCastButNoSanitize(): void
    {
        $field = $this->makeField(
            ['type' => 'integer'],
            []
        );

        $rules = $this->mapper->mapFieldToRules($field);

        $this->assertArrayHasKey('cast', $rules);
        $this->assertSame('integer', $rules['cast']);
        $this->assertArrayNotHasKey('sanitize', $rules);
    }

    public function testBooleanControlHasCastButNoSanitize(): void
    {
        $field = $this->makeField(
            ['type' => 'boolean'],
            []
        );

        $rules = $this->mapper->mapFieldToRules($field);

        $this->assertArrayHasKey('cast', $rules);
        $this->assertSame('boolean', $rules['cast']);
        $this->assertArrayNotHasKey('sanitize', $rules);
    }
}
