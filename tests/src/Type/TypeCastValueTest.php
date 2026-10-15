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
use Derafu\Form\Type\ColorType;
use Derafu\Form\Type\DatetimeType;
use Derafu\Form\Type\DateType;
use Derafu\Form\Type\FloatType;
use Derafu\Form\Type\IntegerType;
use Derafu\Form\Type\MoneyType;
use Derafu\Form\Type\NumberType;
use Derafu\Form\Type\PercentType;
use Derafu\Form\Type\RangeType;
use Derafu\Form\Type\SliderType;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;
use Stringable;

/**
 * What the types do when they cast a value: they turn a valid one into the
 * value of the type, and they say what is wrong with one that can not be cast.
 */
#[CoversClass(AbstractType::class)]
#[CoversClass(ColorType::class)]
#[CoversClass(DateType::class)]
#[CoversClass(DatetimeType::class)]
#[CoversClass(FloatType::class)]
#[CoversClass(IntegerType::class)]
#[CoversClass(MoneyType::class)]
#[CoversClass(NumberType::class)]
#[CoversClass(PercentType::class)]
#[CoversClass(RangeType::class)]
#[CoversClass(SliderType::class)]
final class TypeCastValueTest extends TestCase
{
    /**
     * @return array<string, array{class-string<TypeInterface>}>
     */
    public static function numericTypesProvider(): array
    {
        return [
            'money' => [MoneyType::class],
            'number' => [NumberType::class],
            'percent' => [PercentType::class],
            'range' => [RangeType::class],
            'slider' => [SliderType::class],
        ];
    }

    /**
     * @param class-string<TypeInterface> $class
     */
    #[Test]
    #[DataProvider('numericTypesProvider')]
    public function shouldCastToAnIntegerOrAFloat(string $class): void
    {
        $type = new $class();

        $this->assertSame(25, $type->castValue('25'));
        $this->assertSame(25, $type->castValue(25.0));
        $this->assertSame(1000, $type->castValue('1e3'));
        $this->assertSame(1.5, $type->castValue('1.5'));
        $this->assertSame(1.5, $type->castValue(1.5));
    }

    /**
     * @param class-string<TypeInterface> $class
     */
    #[Test]
    #[DataProvider('numericTypesProvider')]
    public function shouldNotCastWhatIsNotANumber(string $class): void
    {
        $this->expectException(TranslatableInvalidArgumentException::class);
        $this->expectExceptionMessage('The value must be a number, string given.');

        (new $class())->castValue('abc');
    }

    #[Test]
    public function shouldCastToAnIntegerWithTheIntegerType(): void
    {
        $type = new IntegerType();

        $this->assertSame(25, $type->castValue('25'));
        $this->assertSame(25, $type->castValue(25.0));
        $this->assertSame(1, $type->castValue('1.5'), 'The decimals are cut.');
    }

    #[Test]
    public function shouldCastToAFloatWithTheFloatType(): void
    {
        $type = new FloatType();

        $this->assertSame(25.0, $type->castValue('25'));
        $this->assertSame(1.5, $type->castValue('1.5'));
    }

    /**
     * @return array<string, array{class-string<TypeInterface>, mixed, string}>
     */
    public static function notNumbersProvider(): array
    {
        return [
            'integer type, null' => [IntegerType::class, null, 'null'],
            'integer type, text' => [IntegerType::class, 'abc', 'string'],
            'float type, array' => [FloatType::class, [1], 'array'],
            'float type, boolean' => [FloatType::class, true, 'bool'],
            'number type, object' => [NumberType::class, new stdClass(), 'stdClass'],
        ];
    }

    /**
     * @param class-string<TypeInterface> $class
     */
    #[Test]
    #[DataProvider('notNumbersProvider')]
    public function shouldSayWhatWasGivenWhenItIsNotANumber(string $class, mixed $value, string $given): void
    {
        $this->expectException(TranslatableInvalidArgumentException::class);
        $this->expectExceptionMessage('The value must be a number, ' . $given . ' given.');

        (new $class())->castValue($value);
    }

    #[Test]
    public function shouldCastTextToLowercaseWithTheColorType(): void
    {
        $text = new class () implements Stringable {
            public function __toString(): string
            {
                return '#ABCDEF';
            }
        };

        $this->assertSame('#abcdef', (new ColorType())->castValue('#ABCDEF'));
        $this->assertSame('#abcdef', (new ColorType())->castValue($text));
    }

    #[Test]
    public function shouldCastTextToADateWithTheDateTypes(): void
    {
        $date = (new DateType())->castValue('2024-01-15');
        $datetime = (new DatetimeType())->castValue('2024-01-15T10:30');

        $this->assertInstanceOf(DateTimeImmutable::class, $date);
        $this->assertSame('2024-01-15', $date->format('Y-m-d'));
        $this->assertInstanceOf(DateTimeImmutable::class, $datetime);
        $this->assertSame('2024-01-15 10:30', $datetime->format('Y-m-d H:i'));
    }

    /**
     * @return array<string, array{class-string<TypeInterface>, mixed, string}>
     */
    public static function notTextProvider(): array
    {
        return [
            'color, integer' => [ColorType::class, 5, 'int'],
            'date, null' => [DateType::class, null, 'null'],
            'datetime, array' => [DatetimeType::class, ['2024-01-15'], 'array'],
        ];
    }

    /**
     * @param class-string<TypeInterface> $class
     */
    #[Test]
    #[DataProvider('notTextProvider')]
    public function shouldSayWhatWasGivenWhenItIsNotText(string $class, mixed $value, string $given): void
    {
        $this->expectException(TranslatableInvalidArgumentException::class);
        $this->expectExceptionMessage('The value must be text, ' . $given . ' given.');

        (new $class())->castValue($value);
    }
}
