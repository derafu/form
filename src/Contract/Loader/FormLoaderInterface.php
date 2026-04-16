<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Contract\Loader;

use Derafu\Form\Contract\FormInterface;

/**
 * Loads a form definition by name and returns a ready-to-use Form.
 *
 * Implementations decide where the definition lives (files, database, remote
 * service, chain of loaders, etc.) and how `$context` and `$data` are applied.
 */
interface FormLoaderInterface
{
    /**
     * Loads a form definition and returns a ready-to-use Form.
     *
     * @param string $name    Dot-free path relative to a forms directory
     *                        (e.g. 'auth/login' → auth/login.form.php).
     * @param array  $context Data passed to dynamic definitions (repos,
     *                        current user, etc.). Loaders that only support
     *                        static definitions may ignore this argument.
     * @param array  $data    Submitted values to pre-fill the form with.
     */
    public function load(
        string $name,
        array $context = [],
        array $data = []
    ): FormInterface;
}
