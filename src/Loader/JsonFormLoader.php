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

use Derafu\Form\Abstract\AbstractFileFormLoader;
use Derafu\Form\Contract\FormInterface;
use JsonException;
use RuntimeException;

/**
 * Loads form definitions from `.form.json` files.
 *
 * Only supports static definitions. `$context` is accepted for interface
 * compatibility (e.g. chained loaders) but ignored — JSON cannot execute
 * dynamic logic.
 */
class JsonFormLoader extends AbstractFileFormLoader
{
    protected const EXTENSION = '.form.json';

    public function load(
        string $name,
        array $context = [],
        array $data = []
    ): FormInterface {
        $file = $this->resolve($name);
        $raw = file_get_contents($file);

        try {
            $definition = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException(sprintf(
                'Failed to parse JSON form file "%s": %s',
                $file,
                $e->getMessage(),
            ), 0, $e);
        }

        if (!is_array($definition)) {
            throw new RuntimeException(sprintf(
                'Form file "%s" must decode to an array, got %s.',
                $file,
                get_debug_type($definition),
            ));
        }

        if ($data !== []) {
            $definition['data'] = array_merge($definition['data'] ?? [], $data);
        }

        return $this->formFactory->create($definition);
    }
}
