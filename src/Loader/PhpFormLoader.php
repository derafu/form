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
use Derafu\Form\Contract\FormInterface;
use RuntimeException;

/**
 * Loads form definitions from `.form.php` files.
 *
 * Each file returns either a `Closure(array $context): array` for dynamic
 * definitions or a plain array for static ones.
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
            $definition = $result($context);
        } elseif (is_array($result)) {
            $definition = $result;
        } else {
            throw new RuntimeException(sprintf(
                'Form file "%s" must return a Closure or an array, got %s.',
                $file,
                get_debug_type($result),
            ));
        }

        if ($data !== []) {
            $definition['data'] = array_merge($definition['data'] ?? [], $data);
        }

        return $this->formFactory->create($definition);
    }
}
