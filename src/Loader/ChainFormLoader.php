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

use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\Loader\FormLoaderInterface;
use RuntimeException;
use Throwable;

/**
 * Tries a list of loaders in order and returns the first successful result.
 *
 * Useful for mixing formats (e.g. PHP first, then YAML, then JSON) without
 * coupling the caller to which one actually provides a given form.
 *
 * Has no paths of its own — each child loader manages its own configuration.
 */
final class ChainFormLoader implements FormLoaderInterface
{
    /**
     * Constructor.
     *
     * @param FormLoaderInterface[] $loaders The loaders to try, in order.
     */
    public function __construct(
        private readonly array $loaders = [],
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function load(
        string $name,
        array $context = [],
        array $data = []
    ): FormInterface {
        $errors = [];

        foreach ($this->loaders as $loader) {
            try {
                return $loader->load($name, $context, $data);
            } catch (Throwable $e) {
                $errors[] = sprintf(
                    '%s: %s',
                    $loader::class,
                    $e->getMessage(),
                );
            }
        }

        if ($errors === []) {
            throw new RuntimeException(
                'No loaders registered in ChainFormLoader.'
            );
        }

        throw new RuntimeException(sprintf(
            'No loader could resolve form "%s". Attempts: %s',
            $name,
            implode(' | ', $errors),
        ));
    }
}
