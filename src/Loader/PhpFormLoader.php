<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Loader;

use Closure;
use Derafu\Form\Abstract\AbstractFileFormLoader;
use Derafu\Form\Contract\Factory\FormFactoryInterface;
use Derafu\Form\Contract\FormInterface;
use Derafu\Translation\Exception\Core\TranslatableRuntimeException as RuntimeException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Loads form definitions from `.form.php` files.
 *
 * Each file returns either a `Closure(array $context): array` for dynamic
 * definitions or a plain array for static ones.
 *
 * When the loader has a translator, the closure receives it in the context, as
 * `translator`, and as `_t`, a function `(string $id, array $parameters = [],
 * string $domain = 'messages'): string` to translate what is written in PHP: a
 * text with parameters, or one that is not in the list of `FormTexts`. What the
 * caller already put in the context under those names is not replaced. Write
 * the call as `$context['_t']('Text', [], 'domain')` to have it audited.
 *
 * ```php
 * // resources/forms/auth/login.form.php
 * return function (array $context = []): array {
 *     return [
 *         'schema' => [...],
 *         'uischema' => [...],
 *     ];
 * };
 * ```
 */
class PhpFormLoader extends AbstractFileFormLoader
{
    /**
     * The file extension for PHP form files.
     */
    protected const EXTENSION = '.form.php';

    /**
     * Constructor.
     *
     * @param FormFactoryInterface $formFactory The form factory to use.
     * @param string[] $paths Initial directories.
     * @param TranslatorInterface|null $translator The translator that the
     * closures of the definitions receive in their context.
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        array $paths = [],
        private readonly ?TranslatorInterface $translator = null
    ) {
        parent::__construct($formFactory, $paths);
    }

    /**
     * {@inheritDoc}
     */
    public function load(
        string $name,
        array $context = [],
        array $data = []
    ): FormInterface {
        $file = $this->resolve($name);
        $result = require $file;

        if ($result instanceof Closure) {
            if ($this->translator !== null) {
                $translator = $this->translator;
                $context['translator'] ??= $translator;
                $context['_t'] ??= fn (
                    string $id,
                    array $parameters = [],
                    string $domain = 'messages'
                ): string => $translator->trans($id, $parameters, $domain);
            }

            $definition = $result($context);
        } elseif (is_array($result)) {
            $definition = $result;
        } else {
            throw new RuntimeException([
                'Form file "{file}" must return a Closure or an array, got {type}.',
                'file' => $file,
                'type' => get_debug_type($result),
            ]);
        }

        if ($data !== []) {
            $definition['data'] = array_merge($definition['data'] ?? [], $data);
        }

        return $this->formFactory->create($definition);
    }
}
