<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Schema;

/**
 * A pattern of JSON Schema, written as PCRE.
 *
 * JSON Schema patterns have no delimiters, and PHP needs them. The pattern gets
 * the slash as its delimiter, and the slashes that it has are escaped, except
 * the ones that are escaped already (`\/`): escaping them again would make an
 * escaped backslash and leave the slash as the end of the pattern.
 */
final class Pattern
{
    /**
     * @param string $pattern The pattern of JSON Schema, without delimiters.
     * @return string The pattern with its delimiters.
     */
    public static function toRegex(string $pattern): string
    {
        $escaped = '';
        $length = strlen($pattern);

        for ($i = 0; $i < $length; $i++) {
            $character = $pattern[$i];

            if ($character === '\\' && $i + 1 < $length) {
                // A backslash takes the character that follows it as it is.
                $escaped .= $character . $pattern[++$i];
            } elseif ($character === '/') {
                $escaped .= '\\/';
            } else {
                $escaped .= $character;
            }
        }

        return '/' . $escaped . '/';
    }
}
