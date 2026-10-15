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

use Derafu\Form\Abstract\AbstractType;
use Derafu\Form\Contract\Type\TypeInterface;
use Derafu\Form\Type\ArrayType;
use Derafu\Form\Type\BooleanType;
use Derafu\Form\Type\CheckboxType;
use Derafu\Form\Type\EditorType;
use Derafu\Form\Type\EmailType;
use Derafu\Form\Type\FileType;
use Derafu\Form\Type\HiddenType;
use Derafu\Form\Type\HostnameType;
use Derafu\Form\Type\Ipv4Type;
use Derafu\Form\Type\Ipv6Type;
use Derafu\Form\Type\MaskedType;
use Derafu\Form\Type\MoneyType;
use Derafu\Form\Type\NumberType;
use Derafu\Form\Type\ObjectType;
use Derafu\Form\Type\PasswordType;
use Derafu\Form\Type\PercentType;
use Derafu\Form\Type\RadioType;
use Derafu\Form\Type\RangeType;
use Derafu\Form\Type\RegexType;
use Derafu\Form\Type\SearchType;
use Derafu\Form\Type\SliderType;
use Derafu\Form\Type\TagType;
use Derafu\Form\Type\TextareaType;
use Derafu\Form\Type\TextType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * What every type says about itself: its name, the JSON schema that it writes,
 * the values that it accepts and if it can be guessed.
 */
#[CoversClass(AbstractType::class)]
#[CoversClass(ArrayType::class)]
#[CoversClass(BooleanType::class)]
#[CoversClass(CheckboxType::class)]
#[CoversClass(EditorType::class)]
#[CoversClass(EmailType::class)]
#[CoversClass(FileType::class)]
#[CoversClass(HiddenType::class)]
#[CoversClass(HostnameType::class)]
#[CoversClass(Ipv4Type::class)]
#[CoversClass(Ipv6Type::class)]
#[CoversClass(MaskedType::class)]
#[CoversClass(MoneyType::class)]
#[CoversClass(NumberType::class)]
#[CoversClass(ObjectType::class)]
#[CoversClass(PasswordType::class)]
#[CoversClass(PercentType::class)]
#[CoversClass(RadioType::class)]
#[CoversClass(RangeType::class)]
#[CoversClass(RegexType::class)]
#[CoversClass(SearchType::class)]
#[CoversClass(SliderType::class)]
#[CoversClass(TagType::class)]
#[CoversClass(TextType::class)]
#[CoversClass(TextareaType::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Schema\Pattern::class)]
final class TypeBehaviourTest extends TestCase
{
    /**
     * @return array<string, array{class-string<TypeInterface>, string, array<string, mixed>, list<mixed>, list<mixed>, bool}>
     */
    public static function typesProvider(): array
    {
        $object = new stdClass();

        return [
            'array' => [ArrayType::class, 'array', ['type' => 'array'], [[], 'x', 1], [], false],
            'checkbox' => [CheckboxType::class, 'checkbox', ['type' => 'boolean'], [true, 'x'], [], false],
            'editor' => [EditorType::class, 'editor', ['type' => 'string'], ['x'], [1, null], false],
            'file' => [FileType::class, 'file', ['type' => 'string'], ['x'], [], false],
            'hidden' => [HiddenType::class, 'hidden', ['type' => 'string'], ['x'], [], false],
            'masked' => [MaskedType::class, 'masked', ['type' => 'string'], ['x'], [1, null], false],
            'object' => [ObjectType::class, 'object', ['type' => 'object'], [$object, 'x'], [], false],
            'radio' => [RadioType::class, 'radio', ['type' => 'string', 'enum' => []], ['x'], [], false],
            'range' => [RangeType::class, 'range', ['type' => 'number'], [1, '1.5'], ['x', null], false],
            'search' => [SearchType::class, 'search', ['type' => 'string'], ['x'], [1, null], false],
            'slider' => [SliderType::class, 'slider', ['type' => 'number'], [1, '1.5'], ['x', null], false],
            'tag' => [TagType::class, 'tag', ['type' => 'string'], ['x'], [1, null], false],
            'text' => [TextType::class, 'text', ['type' => 'string'], ['x', ''], [1, null, []], false],
            'textarea' => [TextareaType::class, 'textarea', ['type' => 'string'], ['x'], [1, null], false],
            'password' => [PasswordType::class, 'password', ['type' => 'string', 'format' => 'password'], ['x'], [1, null], false],
            'regex' => [RegexType::class, 'regex', ['type' => 'string', 'format' => 'regex'], ['^a$', '', 'a/b', '^a\\/b$', '(?<year>\\d{4})'], ['(', '[a-', 'a\\', 1, null], false],
            'hostname' => [
                HostnameType::class, 'hostname',
                ['type' => 'string', 'format' => 'hostname', 'maxLength' => 255, 'minLength' => 1],
                ['example.com', 'localhost'], ['-bad-.com', 'a b', '', 1, null], false,
            ],
            'money' => [MoneyType::class, 'money', ['type' => 'number'], [1, '1.5', 1.5], ['x', null, []], false],
            'number' => [NumberType::class, 'number', ['type' => 'number'], [1, '1.5', 1.5], ['x', null, []], false],
            'percent' => [PercentType::class, 'percent', ['type' => 'number', 'format' => 'percent'], [1, '50.5'], ['x', null], false],
            'boolean' => [BooleanType::class, 'boolean', ['type' => 'boolean'], [true, false], [1, 'true', null], true],
            'email' => [
                EmailType::class, 'email',
                ['type' => 'string', 'format' => 'email', 'maxLength' => 320],
                ['a@b.cl'], ['a', 'a@', 1, null], true,
            ],
            'ipv4' => [
                Ipv4Type::class, 'ipv4',
                ['type' => 'string', 'format' => 'ipv4', 'maxLength' => 15, 'minLength' => 7],
                ['127.0.0.1'], ['::1', '1.2.3', 1, null], true,
            ],
            'ipv6' => [
                Ipv6Type::class, 'ipv6',
                ['type' => 'string', 'format' => 'ipv6', 'maxLength' => 39, 'minLength' => 2],
                ['::1', '2001:db8::1'], ['127.0.0.1', 'x', 1, null], true,
            ],
        ];
    }

    /**
     * @param class-string<TypeInterface> $class
     * @param array<string, mixed> $schema
     * @param list<mixed> $valid
     * @param list<mixed> $invalid
     */
    #[Test]
    #[DataProvider('typesProvider')]
    public function shouldDescribeItselfAndJudgeValues(
        string $class,
        string $name,
        array $schema,
        array $valid,
        array $invalid,
        bool $guessable
    ): void {
        $type = new $class();

        $this->assertSame($name, $type->getName());
        $this->assertSame($schema, $type->getJsonSchema());
        $this->assertSame($guessable, $type->isGuessable());
        foreach ($valid as $value) {
            $this->assertTrue($type->validateValue($value), $name . ': ' . var_export($value, true));
        }
        foreach ($invalid as $value) {
            $this->assertFalse($type->validateValue($value), $name . ': ' . var_export($value, true));
        }
    }

    #[Test]
    public function shouldCastTheValuesOfTheNumericTypes(): void
    {
        foreach ([new MoneyType(), new NumberType(), new PercentType()] as $type) {
            $this->assertSame(5, $type->castValue('5'));
            $this->assertSame(1.5, $type->castValue('1.5'));
        }

        $this->assertTrue((new BooleanType())->castValue(1));
        $this->assertFalse((new BooleanType())->castValue(''));
        $this->assertSame('x', (new TextType())->castValue('x'));
    }

    #[Test]
    public function shouldMergeTheOptionsWithTheDefaultOnesAndAllowToChangeThem(): void
    {
        $type = new TextType(['a' => ['b' => 1, 'c' => 2]]);

        $this->assertSame(['a' => ['b' => 1, 'c' => 2]], $type->getOptions());
        $this->assertSame(['b' => 1, 'c' => 2], $type->getOption('a'));
        $this->assertSame('d', $type->getOption('missing', 'd'));
        $this->assertNull($type->getOption('missing'));

        $this->assertSame($type, $type->setOptions(['x' => 1]));
        $this->assertSame(['x' => 1], $type->getOptions());
    }

    #[Test]
    public function shouldNotHaveARegexWhenThereIsNoPattern(): void
    {
        $this->assertNull((new TextType())->getRegex());
    }
}
