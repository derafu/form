<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Abstract;

use Derafu\Form\Contract\Factory\FormFactoryInterface;
use Derafu\Form\Contract\Loader\FormLoaderInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Base class for loaders that read form definitions from files.
 *
 * Subclasses declare their file extension via the `EXTENSION` constant
 * (e.g. `.form.php`, `.form.json`, `.form.yaml`) and implement `load()`
 * using `resolve()` to turn a form name into an absolute path.
 */
abstract class AbstractFileFormLoader implements FormLoaderInterface
{
    /**
     * File extension used by this loader, including leading dot and any
     * namespace part (e.g. `.form.php`).
     */
    protected const EXTENSION = '';

    /** @var string[] Registered directories (searched last-to-first). */
    private array $paths = [];

    /**
     * @param FormFactoryInterface $formFactory The form factory to use.
     * @param string[]             $paths       Initial directories, applied
     *                                          in order through `addPath()`.
     *                                          The last one registered wins.
     */
    public function __construct(
        protected readonly FormFactoryInterface $formFactory,
        array $paths = [],
    ) {
        foreach ($paths as $path) {
            $this->addPath($path);
        }
    }

    /**
     * Registers a directory that contains form definition files.
     *
     * Directories are searched in reverse registration order — the last
     * registered path has the highest priority. This allows app-level
     * overrides of bundle-provided forms: bundles register first, the
     * app registers last.
     *
     * Non-existent directories are silently skipped so that callers can
     * register a conventional path (e.g. `resources/forms/`) without
     * having to check whether it exists first.
     */
    public function addPath(string $path): void
    {
        $realPath = realpath($path);

        if ($realPath === false || !is_dir($realPath)) {
            return;
        }

        $this->paths[] = $realPath;
    }

    /**
     * Resolves a form name to an absolute file path.
     *
     * Searches registered directories in reverse order using the subclass'
     * `EXTENSION`. Throws if the name is invalid or no file matches.
     */
    protected function resolve(string $name): string
    {
        if ($name === '' || str_contains($name, '..')) {
            throw new InvalidArgumentException(sprintf(
                'Invalid form name "%s".',
                $name,
            ));
        }

        $relativePath = $name . static::EXTENSION;

        for ($i = count($this->paths) - 1; $i >= 0; $i--) {
            $file = $this->paths[$i] . '/' . $relativePath;
            if (is_file($file)) {
                return $file;
            }
        }

        throw new RuntimeException(sprintf(
            'Form definition "%s" not found (extension "%s"). Searched in: %s',
            $name,
            static::EXTENSION,
            implode(', ', $this->paths) ?: '(no paths registered)',
        ));
    }
}
