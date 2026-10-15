<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Contract\Factory;

use Derafu\Form\Contract\Renderer\FormRendererInterface;

/**
 * Interface for creating form renderer instances.
 */
interface FormRendererFactoryInterface
{
    /**
     * Creates a new form renderer instance.
     *
     * @param array<string, mixed> $options The options for the form renderer.
     * @return FormRendererInterface The created form renderer.
     */
    public static function create(array $options = []): FormRendererInterface;
}
