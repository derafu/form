<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Lint;

/**
 * A text of a form definition that a person reads, found by reading the
 * definition (not by running it).
 *
 * Part of the lint tools: it is for tools and tests, never for the code that
 * runs the package.
 */
final readonly class FormText
{
    /**
     * @param string|null $id The text (its translation id), or `null` when it
     * is not a literal and so it is only known when the code runs.
     * @param string|null $domain The translation domain of the definition (its
     * `options.translation_domain`), or the one of the call that translates the text. `null`
     * when the definition has none (its texts are not translated) or when it is
     * not a literal.
     * @param string $file The file of the definition.
     * @param string $where Where the text is in the file: the path of its key
     * (`schema.properties.name.title`) or the line of the call.
     * @param string|null $expression The code of the text, when it is not a
     * literal and so it can not be read.
     */
    public function __construct(
        public ?string $id,
        public ?string $domain,
        public string $file,
        public string $where,
        public ?string $expression = null
    ) {
    }

    /**
     * Whether the text is only known when the code runs, so it can not be
     * checked by reading the definition.
     */
    public function isDynamic(): bool
    {
        return $this->id === null;
    }
}
