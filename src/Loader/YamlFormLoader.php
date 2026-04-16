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
use Derafu\Form\Contract\Factory\FormFactoryInterface;
use Derafu\Form\Contract\FormInterface;
use RuntimeException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads form definitions from `.form.yaml` files.
 *
 * Requires `symfony/yaml`. The dependency is declared as a suggestion in
 * `composer.json` — if the package is missing the constructor fails fast
 * with a clear message.
 *
 * Only supports static definitions. `$context` is accepted for interface
 * compatibility but ignored.
 */
class YamlFormLoader extends AbstractFileFormLoader
{
    /**
     * The file extension for YAML form files.
     */
    protected const EXTENSION = '.form.yaml';

    /**
     * Constructor.
     *
     * @param FormFactoryInterface $formFactory The form factory to use.
     * @param string[]             $paths       Initial directories.
     */
    public function __construct(FormFactoryInterface $formFactory, array $paths = [])
    {
        if (!class_exists(Yaml::class)) {
            throw new RuntimeException(
                'YamlFormLoader requires "symfony/yaml". '
                . 'Run: composer require symfony/yaml'
            );
        }

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

        try {
            $definition = Yaml::parseFile($file);
        } catch (ParseException $e) {
            throw new RuntimeException(sprintf(
                'Failed to parse YAML form file "%s": %s',
                $file,
                $e->getMessage(),
            ), 0, $e);
        }

        if (!is_array($definition)) {
            throw new RuntimeException(sprintf(
                'Form file "%s" must parse to an array, got %s.',
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
