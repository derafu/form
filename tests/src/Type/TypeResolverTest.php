<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Type;

use DateTimeImmutable;
use Derafu\Form\Abstract\AbstractType;
use Derafu\Form\Contract\Type\TypeInterface;
use Derafu\Form\Type\ArrayType;
use Derafu\Form\Type\BooleanType;
use Derafu\Form\Type\DatetimeType;
use Derafu\Form\Type\EmailType;
use Derafu\Form\Type\FloatType;
use Derafu\Form\Type\IntegerType;
use Derafu\Form\Type\ObjectType;
use Derafu\Form\Type\TextType;
use Derafu\Form\Type\TypeRegistry;
use Derafu\Form\Type\TypeResolver;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * The resolver guesses the type of a value.
 */
#[CoversClass(TypeResolver::class)]
#[UsesClass(TypeRegistry::class)]
#[UsesClass(AbstractType::class)]
#[UsesClass(ArrayType::class)]
#[UsesClass(BooleanType::class)]
#[UsesClass(DatetimeType::class)]
#[UsesClass(EmailType::class)]
#[UsesClass(FloatType::class)]
#[UsesClass(IntegerType::class)]
#[UsesClass(ObjectType::class)]
#[UsesClass(TextType::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Schema\Pattern::class)]
final class TypeResolverTest extends TestCase
{
    private function resolver(): TypeResolver
    {
        return new TypeResolver(new TypeRegistry([
            new EmailType(),
            new BooleanType(),
            new IntegerType(),
            new FloatType(),
            new DatetimeType(),
            new ArrayType(),
            new ObjectType(),
            new TextType(),
        ]));
    }

    /**
     * @return array<string, array{mixed, class-string<TypeInterface>}>
     */
    public static function valuesProvider(): array
    {
        return [
            'integer' => [5, IntegerType::class],
            'float' => [1.5, FloatType::class],
            'boolean' => [true, BooleanType::class],
            'array' => [[1], ArrayType::class],
            'object' => [new stdClass(), ObjectType::class],
            'a date' => [new DateTimeImmutable(), DatetimeType::class],
            'an integer as string' => ['42', IntegerType::class],
            'a negative integer as string' => ['-42', IntegerType::class],
            'an integer above the range' => ['99999999999999999999', TextType::class],
            'an integer below the range' => ['-99999999999999999999', TextType::class],
            'an email' => ['a@b.cl', EmailType::class],
            'a text' => ['hello', TextType::class],
        ];
    }

    /**
     * @param class-string<TypeInterface> $expected
     */
    #[Test]
    #[DataProvider('valuesProvider')]
    public function shouldGuessTheTypeOfAValue(mixed $value, string $expected): void
    {
        $this->assertInstanceOf($expected, $this->resolver()->guess($value));
    }

    #[Test]
    public function shouldFailWithAValueThatHasNoType(): void
    {
        $this->expectException(TranslatableInvalidArgumentException::class);

        $this->resolver()->guess(null);
    }
}
