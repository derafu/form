<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Schema;

use Derafu\Form\Schema\Pattern;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A pattern of JSON Schema gets its delimiters, and its slashes are escaped
 * once.
 */
#[CoversClass(Pattern::class)]
final class PatternTest extends TestCase
{
    /**
     * @return array<string, array{string, string, string, bool}>
     */
    public static function patternsProvider(): array
    {
        return [
            'without slashes' => ['^[a-z]+$', '/^[a-z]+$/', 'abc', true],
            'with a slash' => ['^a/b$', '/^a\\/b$/', 'a/b', true],
            'with a slash already escaped' => ['^a\\/b$', '/^a\\/b$/', 'a/b', true],
            'with an escaped backslash and a slash' => ['^a\\\\/b$', '/^a\\\\\\/b$/', 'a\\/b', true],
            'with two slashes' => ['a//b', '/a\\/\\/b/', 'a//b', true],
            'empty' => ['', '//', '', true],
            'that does not match' => ['^a/b$', '/^a\\/b$/', 'ab', false],
        ];
    }

    #[Test]
    #[DataProvider('patternsProvider')]
    public function shouldWriteThePatternAsPcre(string $pattern, string $expected, string $text, bool $matches): void
    {
        $regex = Pattern::toRegex($pattern);

        $this->assertSame($expected, $regex);
        $this->assertSame($matches, preg_match($regex, $text) === 1);
    }

    #[Test]
    public function shouldKeepALoneBackslashAtTheEndSoThePatternIsNotValid(): void
    {
        $this->assertSame('/a\\/', Pattern::toRegex('a\\'));
    }
}
