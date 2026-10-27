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

use Derafu\DataProcessor\Processor;
use Derafu\DataProcessor\Registrar\DefaultRuleRegistrar;
use Derafu\DataProcessor\RuleParser;
use Derafu\DataProcessor\RuleRegistry;
use Derafu\DataProcessor\RuleResolver;
use Derafu\Form\Abstract\AbstractPropertySchema;
use Derafu\Form\Abstract\AbstractType;
use Derafu\Form\Abstract\AbstractUiSchemaElement;
use Derafu\Form\Contract\Processor\FormDataProcessorInterface;
use Derafu\Form\Contract\Processor\ProcessResultInterface;
use Derafu\Form\Factory\ChoicesShorthand;
use Derafu\Form\Factory\FormFactory;
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
use Derafu\Form\Type\BooleanType;
use Derafu\Form\Type\ChoiceType;
use Derafu\Form\Type\ColorType;
use Derafu\Form\Type\DatetimeType;
use Derafu\Form\Type\DateType;
use Derafu\Form\Type\EmailType;
use Derafu\Form\Type\FileType;
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
use Derafu\Form\UiSchema\UiSchemaElementDefinition;
use Derafu\Form\UiSchema\VerticalLayout;
use Derafu\Form\Widget\Widget;
use Derafu\Form\Widget\WidgetFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end tests for file upload field processing through FormDataProcessor.
 *
 * Uses a real FormFactory to produce forms and a real DataProcessor stack, so
 * every layer (mapper → processor → validator) is exercised together.
 *
 * Each test creates actual temporary files on disk because AbstractFileRule
 * calls file_exists() to verify the uploaded file is present.
 */
#[CoversClass(FormDataProcessor::class)]
#[CoversClass(PropertySchemaFactory::class)]
#[CoversClass(FormRulesResolver::class)]
#[CoversClass(FormRules::class)]
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
#[CoversTrait(ObjectSchemaTrait::class)]
#[CoversClass(StringSchema::class)]
#[CoversClass(BooleanType::class)]
#[CoversClass(ChoiceType::class)]
#[CoversClass(ColorType::class)]
#[CoversClass(DateType::class)]
#[CoversClass(DatetimeType::class)]
#[CoversClass(EmailType::class)]
#[CoversClass(FileType::class)]
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
#[UsesClass(PropertyDefinition::class)]
#[UsesClass(UiSchemaElementDefinition::class)]
#[UsesClass(ChoicesShorthand::class)]
#[UsesClass(FormDefinition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Options\FormAttributes::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Data\FieldPath::class)]
final class FormDataProcessorFileTest extends TestCase
{
    private FormDataProcessorInterface $processor;

    private FormFactory $formFactory;

    private string $tempDir;

    /** @var string[] */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        $dataProcessorRegistry = new RuleRegistry();
        (new DefaultRuleRegistrar())->register($dataProcessorRegistry);
        $dataProcessor = new Processor(new RuleResolver($dataProcessorRegistry), new RuleParser());
        $this->processor = new FormDataProcessor(new FormRulesResolver(), $dataProcessor);
        $this->formFactory = new FormFactory(
            new TypeResolver(new TypeRegistry(new TypeProvider()))
        );

        $this->tempDir = sys_get_temp_dir() . '/derafu_form_file_test_' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createTempFile(string $name, string $content = 'dummy'): string
    {
        $path = $this->tempDir . '/' . $name;
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;
        return $path;
    }

    /**
     * Returns a valid $_FILES-style array for a single file upload.
     *
     * The content is a real file of the fixtures that has the type that is
     * declared (a PDF for `application/pdf`, an image for any other), so its
     * type is detected from its content.
     *
     * @return array<string, mixed>
     */
    private function validFileArray(string $filename, string $mime = 'image/jpeg'): array
    {
        $file = $this->fixtureFileArray($mime === 'application/pdf' ? 'document.pdf' : 'image.jpeg', $filename);
        $file['type'] = $mime;

        return $file;
    }

    /**
     * Returns a $_FILES-style array for a file of the fixtures.
     *
     * @return array<string, mixed>
     */
    private function fixtureFileArray(string $fixture, ?string $filename = null): array
    {
        $content = (string) file_get_contents(dirname(__DIR__, 2) . '/fixtures/files/' . $fixture);
        $filename ??= $fixture;

        return [
            'name'     => $filename,
            'type'     => 'application/octet-stream',
            'tmp_name' => $this->createTempFile($filename, $content),
            'error'    => UPLOAD_ERR_OK,
            'size'     => strlen($content),
        ];
    }

    /**
     * Returns a $_FILES-style array for a file that is empty.
     *
     * @return array<string, mixed>
     */
    private function emptyFileArray(string $filename = 'empty.pdf'): array
    {
        return [
            'name'     => $filename,
            'type'     => 'application/pdf',
            'tmp_name' => $this->createTempFile($filename, ''),
            'error'    => UPLOAD_ERR_OK,
            'size'     => 0,
        ];
    }

    /**
     * Returns a $_FILES-style array that represents an upload error.
     *
     * @return array<string, mixed>
     */
    private function errorFileArray(int $errorCode = UPLOAD_ERR_NO_FILE): array
    {
        return [
            'name'     => '',
            'type'     => '',
            'tmp_name' => '',
            'error'    => $errorCode,
            'size'     => 0,
        ];
    }

    /**
     * Creates a form with a single file field.
     *
     * @param bool $required Whether the file field is required.
     * @param array<string, mixed> $options Options of the control.
     */
    private function createFormWithFileField(bool $required = false, array $options = []): \Derafu\Form\Contract\FormInterface
    {
        $definition = [
            'options' => ['csrf_protection' => false],
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'avatar' => [
                        'type' => 'string',
                    ],
                ],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    [
                        'type'    => 'Control',
                        'scope'   => '#/properties/avatar',
                        'options' => ['type' => 'file'] + $options,
                    ],
                ],
            ],
        ];

        if ($required) {
            $definition['schema']['required'] = ['avatar'];
        }

        return $this->formFactory->create($definition);
    }

    /**
     * Creates a form with a single image field.
     *
     * @param bool $required Whether the image field is required.
     * @param array<string, mixed> $options Options of the control.
     */
    private function createFormWithImageField(bool $required = false, array $options = []): \Derafu\Form\Contract\FormInterface
    {
        $definition = [
            'options' => ['csrf_protection' => false],
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'photo' => [
                        'type' => 'string',
                    ],
                ],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    [
                        'type'    => 'Control',
                        'scope'   => '#/properties/photo',
                        'options' => ['type' => 'image'] + $options,
                    ],
                ],
            ],
        ];

        if ($required) {
            $definition['schema']['required'] = ['photo'];
        }

        return $this->formFactory->create($definition);
    }

    // -------------------------------------------------------------------------
    // Optional file field
    // -------------------------------------------------------------------------

    public function testOptionalFileFieldWithNoFileIsValid(): void
    {
        // When a file field is not required, submitting no file should be valid.
        $form = $this->createFormWithFileField(required: false);

        $result = $this->processor->process($form, ['avatar' => null]);

        $this->assertTrue(
            $result->isValid(),
            'An optional file field with no file submitted must be valid.'
        );
        $this->assertEmpty($result->getErrors());
    }

    public function testOptionalFileFieldWithValidFileIsValid(): void
    {
        $form = $this->createFormWithFileField(required: false);
        $file = $this->validFileArray('photo.jpg');

        $result = $this->processor->process($form, ['avatar' => $file]);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
        // The processed value should be the original file array, unchanged.
        $this->assertSame($file, $this->processedData($result)['avatar']);
    }

    // -------------------------------------------------------------------------
    // Required file field
    // -------------------------------------------------------------------------

    public function testRequiredFileFieldWithValidFileIsValid(): void
    {
        $form = $this->createFormWithFileField(required: true);
        $file = $this->validFileArray('document.pdf', 'application/pdf');

        $result = $this->processor->process($form, ['avatar' => $file]);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testRequiredFileFieldWithMissingFileIsInvalid(): void
    {
        // Submitting null for a required file field must fail validation.
        $form = $this->createFormWithFileField(required: true);

        $result = $this->processor->process($form, ['avatar' => null]);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
        $this->assertArrayHasKey('avatar', $result->getErrors());
    }

    public function testRequiredFileFieldWithUploadErrorIsInvalid(): void
    {
        $form = $this->createFormWithFileField(required: true);
        $file = $this->errorFileArray(UPLOAD_ERR_NO_FILE);

        $result = $this->processor->process($form, ['avatar' => $file]);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
        $this->assertArrayHasKey('avatar', $result->getErrors());
    }

    public function testRequiredFileFieldWithPartialUploadErrorIsInvalid(): void
    {
        $form = $this->createFormWithFileField(required: true);
        $file = $this->errorFileArray(UPLOAD_ERR_PARTIAL);

        $result = $this->processor->process($form, ['avatar' => $file]);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('avatar', $result->getErrors());
    }

    // -------------------------------------------------------------------------
    // image control (same invariants, different rule name)
    // -------------------------------------------------------------------------

    public function testOptionalImageFieldWithNoFileIsValid(): void
    {
        $form = $this->createFormWithImageField(required: false);

        $result = $this->processor->process($form, ['photo' => null]);

        $this->assertTrue($result->isValid());
    }

    public function testRequiredImageFieldWithValidImageIsValid(): void
    {
        $form = $this->createFormWithImageField(required: true);
        $file = $this->validFileArray('portrait.jpg', 'image/jpeg');

        $result = $this->processor->process($form, ['photo' => $file]);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testRequiredImageFieldWithMissingFileIsInvalid(): void
    {
        $form = $this->createFormWithImageField(required: true);

        $result = $this->processor->process($form, ['photo' => null]);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('photo', $result->getErrors());
    }

    // -------------------------------------------------------------------------
    // The rules that the options of the control derive
    // -------------------------------------------------------------------------

    public function testEmptyFileIsInvalidByDefault(): void
    {
        $form = $this->createFormWithFileField();

        $result = $this->processor->process($form, ['avatar' => $this->emptyFileArray()]);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('avatar', $result->getErrors());
    }

    public function testEmptyFileIsValidWithAMinimumSizeOfZero(): void
    {
        $form = $this->createFormWithFileField(options: ['min_size' => 0]);

        $result = $this->processor->process($form, ['avatar' => $this->emptyFileArray()]);

        $this->assertTrue($result->isValid());
    }

    public function testEmptyFileIsValidWithAMinimumSizeOfZeroAndAnAcceptedType(): void
    {
        $form = $this->createFormWithFileField(options: ['min_size' => 0, 'accept' => '.pdf']);

        $result = $this->processor->process($form, ['avatar' => $this->emptyFileArray()]);

        $this->assertTrue($result->isValid());
    }

    public function testEmptyFileIsInvalidWithAnAcceptedTypeAndNoMinimumSize(): void
    {
        $form = $this->createFormWithFileField(options: ['accept' => '.pdf']);

        $result = $this->processor->process($form, ['avatar' => $this->emptyFileArray()]);

        $this->assertFalse($result->isValid());
    }

    public function testFileOfAnAcceptedTypeIsValid(): void
    {
        $form = $this->createFormWithFileField(options: ['accept' => '.pdf,image/png']);

        $result = $this->processor->process($form, ['avatar' => $this->fixtureFileArray('document.pdf')]);

        $this->assertTrue($result->isValid());
    }

    public function testFileOfATypeThatIsNotAcceptedIsInvalidWhateverTheClientSays(): void
    {
        $form = $this->createFormWithFileField(options: ['accept' => '.pdf']);
        $file = $this->fixtureFileArray('image.jpeg', 'document.pdf');
        $file['type'] = 'application/pdf';

        $result = $this->processor->process($form, ['avatar' => $file]);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('avatar', $result->getErrors());
    }

    public function testAcceptWithAWildcardDoesNotRestrictTheTypes(): void
    {
        $form = $this->createFormWithFileField(options: ['accept' => '.pdf,image/*']);

        $result = $this->processor->process($form, ['avatar' => $this->fixtureFileArray('image.jpeg')]);

        $this->assertTrue($result->isValid());
    }

    public function testFileLargerThanTheMaximumSizeIsInvalid(): void
    {
        $form = $this->createFormWithFileField(options: ['max_size' => 100]);
        $file = $this->fixtureFileArray('document.pdf');

        $result = $this->processor->process($form, ['avatar' => $file]);

        $this->assertGreaterThan(100, $file['size']);
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('avatar', $result->getErrors());
    }

    public function testFileSmallerThanTheMinimumSizeIsInvalid(): void
    {
        $form = $this->createFormWithFileField(options: ['min_size' => '1M']);

        $result = $this->processor->process($form, ['avatar' => $this->fixtureFileArray('document.pdf')]);

        $this->assertFalse($result->isValid());
    }

    public function testFileThatIsNotAnImageIsInvalidInAnImageControl(): void
    {
        $form = $this->createFormWithImageField();

        $result = $this->processor->process($form, ['photo' => $this->fixtureFileArray('text.txt', 'photo.jpg')]);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('photo', $result->getErrors());
    }

    public function testImageIsValidInAnImageControlWithSizes(): void
    {
        $form = $this->createFormWithImageField(options: ['min_size' => 0, 'max_size' => '1M']);

        $result = $this->processor->process($form, ['photo' => $this->fixtureFileArray('image.jpeg')]);

        $this->assertTrue($result->isValid());
    }

    // -------------------------------------------------------------------------
    // Regression: non-upload fields must not be broken by the fix
    // -------------------------------------------------------------------------

    public function testStringFieldStillProcessesNormally(): void
    {
        $definition = [
            'options' => ['csrf_protection' => false],
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'name'   => ['type' => 'string'],
                    'avatar' => ['type' => 'string'],
                ],
                'required' => ['name', 'avatar'],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    ['type' => 'Control', 'scope' => '#/properties/name'],
                    [
                        'type'    => 'Control',
                        'scope'   => '#/properties/avatar',
                        'options' => ['type' => 'file'],
                    ],
                ],
            ],
        ];

        $form  = $this->formFactory->create($definition);
        $file  = $this->validFileArray('cv.pdf', 'application/pdf');

        $result = $this->processor->process($form, [
            'name'   => '  John Doe  ',
            'avatar' => $file,
        ]);

        $this->assertTrue($result->isValid());
        // The text field must still be trimmed.
        $this->assertSame('John Doe', $this->processedData($result)['name']);
        // The file field must pass through unchanged.
        $this->assertSame($file, $this->processedData($result)['avatar']);
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
}
