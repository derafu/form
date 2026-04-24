<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Tests\Form\Processor;

use Derafu\DataProcessor\Exception\ValidationException;
use Derafu\DataProcessor\ProcessorFactory;
use Derafu\Form\Processor\SchemaToRulesMapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SchemaToRulesMapper::class)]
final class SchemaToRulesMapperIntegrationTest extends TestCase
{
    private SchemaToRulesMapper $mapper;

    private $processor;

    protected function setUp(): void
    {
        $this->mapper = new SchemaToRulesMapper();
        $this->processor = ProcessorFactory::create();
    }

    public function testEmailFieldProcessing(): void
    {
        $emailSchema = [
            'type' => 'string',
            'format' => 'email',
            'minLength' => 3,
            'maxLength' => 80,
        ];

        $rules = $this->mapper->mapSchemaToRules($emailSchema);

        // Test valid email.
        $result = $this->processor->process(' TEST@EXAMPLE.COM ', $rules);
        $this->assertSame('test@example.com', $result);

        // Test invalid email.
        $this->expectException(ValidationException::class);
        $this->processor->process('invalid-email', $rules);
    }

    public function testStringFieldWithLengthConstraints(): void
    {
        $nameSchema = [
            'type' => 'string',
            'minLength' => 3,
            'maxLength' => 100,
        ];

        $rules = $this->mapper->mapSchemaToRules($nameSchema);

        // Test valid name.
        $result = $this->processor->process('  John Doe  ', $rules);
        $this->assertSame('John Doe', $result);

        // Test too short.
        $this->expectException(ValidationException::class);
        $this->processor->process('Jo', $rules);
    }

    public function testIntegerFieldWithRange(): void
    {
        $ageSchema = [
            'type' => 'integer',
            'minimum' => 18,
            'maximum' => 99,
        ];

        $rules = $this->mapper->mapSchemaToRules($ageSchema);

        // Test valid age.
        $result = $this->processor->process('25', $rules);
        $this->assertSame(25, $result);

        // Test too young.
        $this->expectException(ValidationException::class);
        $this->processor->process('15', $rules);
    }

    public function testArrayFieldWithConstraints(): void
    {
        $tagsSchema = [
            'type' => 'array',
            'minItems' => 1,
            'maxItems' => 5,
            'uniqueItems' => true,
        ];

        $rules = $this->mapper->mapSchemaToRules($tagsSchema);

        // Test valid tags.
        $result = $this->processor->process(['php', 'form', 'validation'], $rules);
        $this->assertSame(['php', 'form', 'validation'], $result);

        // Test too many items.
        $this->expectException(ValidationException::class);
        $this->processor->process(['a', 'b', 'c', 'd', 'e', 'f'], $rules);
    }

    public function testEnumField(): void
    {
        $statusSchema = [
            'type' => 'string',
            'enum' => ['pending' => 'pending', 'approved' => 'approved', 'rejected' => 'rejected'],
        ];

        $rules = $this->mapper->mapSchemaToRules($statusSchema);

        // Test valid status.
        $result = $this->processor->process('  APPROVED  ', $rules);
        $this->assertSame('approved', $result);

        // Test invalid status.
        $this->expectException(ValidationException::class);
        $this->processor->process('invalid', $rules);
    }

    public function testCompleteFormSchemaMapping(): void
    {
        $formSchema = [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'minLength' => 3,
                    'maxLength' => 100,
                ],
                'email' => [
                    'type' => 'string',
                    'format' => 'email',
                    'minLength' => 3,
                    'maxLength' => 80,
                ],
                'age' => [
                    'type' => 'integer',
                    'minimum' => 18,
                    'maximum' => 99,
                ],
            ],
        ];

        $fieldRules = [];
        foreach ($formSchema['properties'] as $fieldName => $propertySchema) {
            $fieldRules[$fieldName] = $this->mapper->mapSchemaToRules($propertySchema);
        }

        // Test valid form data.
        $formData = [
            'name' => '  John Doe  ',
            'email' => '  TEST@EXAMPLE.COM  ',
            'age' => '25',
        ];

        $processedData = [];
        foreach ($formData as $fieldName => $value) {
            $processedData[$fieldName] = $this->processor->process(
                $value,
                $fieldRules[$fieldName]
            );
        }

        $this->assertSame('John Doe', $processedData['name']);
        $this->assertSame('test@example.com', $processedData['email']);
        $this->assertSame(25, $processedData['age']);
    }
}
