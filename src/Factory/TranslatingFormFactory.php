<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Factory;

use Derafu\Form\Contract\Factory\FormFactoryInterface;
use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Translation\FormTexts;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Creates the form with its texts translated.
 *
 * It decorates the form factory, which is where every form ends: the loaders
 * (PHP, YAML and JSON) and whoever builds a definition by hand call
 * `create()`. A definition that has the key `translationDomain` gets its texts
 * (the ones of `FormTexts`) translated, in that domain and in the language of
 * the translator when the form is created: the text in English is the id, and a
 * text without an entry in the catalogue stays as it was written.
 *
 * A definition without `translationDomain` is not touched: each form says in
 * which domain its texts are.
 */
final class TranslatingFormFactory implements FormFactoryInterface
{
    /**
     * Constructor.
     *
     * @param FormFactoryInterface $inner The factory that creates the form.
     * @param TranslatorInterface $translator The translator of the texts.
     * @param FormTexts $texts Which keys of a definition are text.
     */
    public function __construct(
        private readonly FormFactoryInterface $inner,
        private readonly TranslatorInterface $translator,
        private readonly FormTexts $texts = new FormTexts()
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $definition): FormInterface
    {
        $domain = $definition['translationDomain'] ?? null;

        if (is_string($domain) && $domain !== '') {
            $definition = $this->texts->map(
                $definition,
                fn (string $text): string => $this->translator->trans($text, [], $domain)
            );
        }

        return $this->inner->create($definition);
    }
}
