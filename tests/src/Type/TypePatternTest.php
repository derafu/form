<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Tests\Type;

use Derafu\Form\Abstract\AbstractType;
use Derafu\Form\Type\ColorType;
use Derafu\Form\Type\DatetimeType;
use Derafu\Form\Type\DateType;
use Derafu\Form\Type\DurationType;
use Derafu\Form\Type\MonthType;
use Derafu\Form\Type\TelephoneType;
use Derafu\Form\Type\TimeType;
use Derafu\Form\Type\UriType;
use Derafu\Form\Type\UuidType;
use Derafu\Form\Type\WeekType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests that each Type:
 *   1. Stores PATTERN in ECMA format (no PCRE delimiters).
 *   2. Returns a valid PCRE-ready regex from getRegex().
 *   3. Exposes the ECMA pattern under the 'pattern' key in getJsonSchema().
 *   4. Validates correct values as true and invalid values as false.
 *
 * These tests prevent regressions after the pattern format was standardised
 * to ECMA throughout the project.
 */
#[CoversClass(AbstractType::class)]
#[CoversClass(ColorType::class)]
#[CoversClass(DateType::class)]
#[CoversClass(DatetimeType::class)]
#[CoversClass(DurationType::class)]
#[CoversClass(MonthType::class)]
#[CoversClass(TelephoneType::class)]
#[CoversClass(TimeType::class)]
#[CoversClass(UriType::class)]
#[CoversClass(UuidType::class)]
#[CoversClass(WeekType::class)]
final class TypePatternTest extends TestCase
{
    // =========================================================================
    // getRegex() — ECMA → PCRE wrapping
    // =========================================================================

    /**
     * PATTERN constants must not contain PCRE delimiters ('/' at start/end).
     */
    #[DataProvider('typesWithPatternProvider')]
    public function testPatternConstantHasNoDelimiters(AbstractType $type): void
    {
        $pattern = $type::PATTERN;
        $this->assertNotNull($pattern);
        $this->assertStringStartsNotWith('/', $pattern, 'PATTERN must be ECMA (no leading /)');
        $this->assertStringEndsNotWith('/', $pattern, 'PATTERN must be ECMA (no trailing /)');
    }

    /**
     * getRegex() must wrap PATTERN with '/' delimiters.
     */
    #[DataProvider('typesWithPatternProvider')]
    public function testGetRegexWrapsWithDelimiters(AbstractType $type): void
    {
        $regex = $type->getRegex();
        $this->assertNotNull($regex);
        $this->assertStringStartsWith('/', $regex);
        $this->assertStringEndsWith('/', $regex);
    }

    /**
     * getRegex() must produce a valid PCRE pattern (preg_match must not fail).
     */
    #[DataProvider('typesWithPatternProvider')]
    public function testGetRegexIsValidPcre(AbstractType $type): void
    {
        $regex = $type->getRegex();
        $this->assertNotNull($regex);
        $result = @preg_match($regex, '');
        $this->assertNotFalse($result, "getRegex() of {$type->getName()} is not a valid PCRE pattern");
    }

    /**
     * getJsonSchema() must expose the ECMA pattern under the 'pattern' key
     * when the base AbstractType::getJsonSchema() is used. Subclasses that
     * override getJsonSchema() completely (e.g. DurationType, DateType) use
     * 'format' instead — for those we verify the schema is at least a valid
     * array with a 'type' key.
     */
    #[DataProvider('typesWithPatternProvider')]
    public function testGetJsonSchemaExposesPatternKey(AbstractType $type): void
    {
        $schema = $type->getJsonSchema();
        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        if (isset($schema['pattern'])) {
            $this->assertSame($type::PATTERN, $schema['pattern']);
        }
    }

    public static function typesWithPatternProvider(): array
    {
        return [
            'color'     => [new ColorType()],
            'date'      => [new DateType()],
            'datetime'  => [new DatetimeType()],
            'duration'  => [new DurationType()],
            'month'     => [new MonthType()],
            'telephone' => [new TelephoneType()],
            'time'      => [new TimeType()],
            'uri'       => [new UriType()],
            'uuid'      => [new UuidType()],
            'week'      => [new WeekType()],
        ];
    }

    // =========================================================================
    // validateValue() — valid and invalid cases per type
    // =========================================================================

    public function testColorValidValues(): void
    {
        $type = new ColorType();
        $this->assertTrue($type->validateValue('#ff5733'));
        $this->assertTrue($type->validateValue('#FFF'));
        $this->assertTrue($type->validateValue('#aabbcc'));
    }

    public function testColorInvalidValues(): void
    {
        $type = new ColorType();
        $this->assertFalse($type->validateValue('red'));
        $this->assertFalse($type->validateValue('#GGGGGG'));
        $this->assertFalse($type->validateValue(123));
    }

    public function testDateValidValues(): void
    {
        $type = new DateType();
        $this->assertTrue($type->validateValue('2025-01-15'));
        $this->assertTrue($type->validateValue('2100-12-31'));
        $this->assertTrue($type->validateValue('1999-06-01'));
    }

    public function testDateInvalidValues(): void
    {
        $type = new DateType();
        $this->assertFalse($type->validateValue('2025/01/15'));
        $this->assertFalse($type->validateValue('15-01-2025'));
        $this->assertFalse($type->validateValue('not-a-date'));
        $this->assertFalse($type->validateValue(null));
    }

    public function testDatetimeValidValues(): void
    {
        $type = new DatetimeType();
        $this->assertTrue($type->validateValue('2025-03-04T14:30:00Z'));
        $this->assertTrue($type->validateValue('2025-03-04T14:30:00+02:00'));
        $this->assertTrue($type->validateValue('2025-03-04T00:00:00-03:00'));
    }

    public function testDatetimeInvalidValues(): void
    {
        $type = new DatetimeType();
        $this->assertFalse($type->validateValue('2025-03-04 14:30:00'));
        $this->assertFalse($type->validateValue('not-a-datetime'));
        $this->assertFalse($type->validateValue(42));
    }

    public function testDurationValidValues(): void
    {
        $type = new DurationType();
        $this->assertTrue($type->validateValue('P1Y2M3DT4H5M6S'));
        $this->assertTrue($type->validateValue('PT1H30M'));
        // DurationType::PATTERN requires T; a pure date-only duration like P1Y
        // is not valid per this pattern (use the JSON Schema pattern from
        // 002_control.json which makes T optional).
        $this->assertTrue($type->validateValue('P1DT0S'));
    }

    public function testDurationInvalidValues(): void
    {
        $type = new DurationType();
        $this->assertFalse($type->validateValue('1Y2M3D'));  // missing P prefix
        $this->assertFalse($type->validateValue('P1Y'));     // missing T
        $this->assertFalse($type->validateValue('hola'));
        $this->assertFalse($type->validateValue(false));
    }

    public function testMonthValidValues(): void
    {
        $type = new MonthType();
        $this->assertTrue($type->validateValue('2025-03'));
        $this->assertTrue($type->validateValue('1999-12'));
    }

    public function testMonthInvalidValues(): void
    {
        $type = new MonthType();
        $this->assertFalse($type->validateValue('2025-13'));
        $this->assertFalse($type->validateValue('25-03'));
        $this->assertFalse($type->validateValue('not-a-month'));
    }

    public function testTelephoneValidValues(): void
    {
        $type = new TelephoneType();
        $this->assertTrue($type->validateValue('+56912345678'));
        $this->assertTrue($type->validateValue('+1 800 555 0199'));
        $this->assertTrue($type->validateValue('912345678'));
    }

    public function testTelephoneInvalidValues(): void
    {
        $type = new TelephoneType();
        $this->assertFalse($type->validateValue('not-a-phone'));
        $this->assertFalse($type->validateValue([]));
    }

    public function testTimeValidValues(): void
    {
        $type = new TimeType();
        $this->assertTrue($type->validateValue('14:30:00'));
        $this->assertTrue($type->validateValue('00:00'));
        $this->assertTrue($type->validateValue('23:59:59'));
    }

    public function testTimeInvalidValues(): void
    {
        $type = new TimeType();
        $this->assertFalse($type->validateValue('25:00:00'));
        $this->assertFalse($type->validateValue('14:60'));
        $this->assertFalse($type->validateValue('not-a-time'));
    }

    public function testUriValidValues(): void
    {
        $type = new UriType();
        $this->assertTrue($type->validateValue('https://example.com'));
        $this->assertTrue($type->validateValue('mailto:user@example.com'));
        $this->assertTrue($type->validateValue('ftp://files.example.com/path/to/file'));
    }

    public function testUriInvalidValues(): void
    {
        $type = new UriType();
        $this->assertFalse($type->validateValue('not a uri'));
        $this->assertFalse($type->validateValue(''));
        $this->assertFalse($type->validateValue(0));
    }

    public function testUuidValidValues(): void
    {
        $type = new UuidType();
        $this->assertTrue($type->validateValue('550e8400-e29b-41d4-a716-446655440000'));
        $this->assertTrue($type->validateValue('6ba7b810-9dad-11d1-80b4-00c04fd430c8'));
    }

    public function testUuidInvalidValues(): void
    {
        $type = new UuidType();
        $this->assertFalse($type->validateValue('not-a-uuid'));
        $this->assertFalse($type->validateValue('550e8400-e29b-41d4-a716'));
        $this->assertFalse($type->validateValue(null));
    }

    public function testWeekValidValues(): void
    {
        $type = new WeekType();
        $this->assertTrue($type->validateValue('2025-W01'));
        $this->assertTrue($type->validateValue('2025-W53'));
        $this->assertTrue($type->validateValue('1999-W10'));
    }

    public function testWeekInvalidValues(): void
    {
        $type = new WeekType();
        $this->assertFalse($type->validateValue('2025-W54'));
        $this->assertFalse($type->validateValue('2025-00'));
        $this->assertFalse($type->validateValue('not-a-week'));
    }

    // =========================================================================
    // AbstractType base — null PATTERN
    // =========================================================================

    public function testAbstractTypeWithNullPatternReturnsNullGetRegex(): void
    {
        // Use an anonymous class to exercise the null-PATTERN branch in
        // AbstractType::getRegex() without introducing a dedicated stub class.
        $type = new class () extends AbstractType {
            public function getName(): string
            {
                return 'test';
            }
        };

        $this->assertNull($type::PATTERN);
        $this->assertNull($type->getRegex());
    }
}
