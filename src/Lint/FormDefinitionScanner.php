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

use Derafu\Form\Translation\FormTexts;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;
use FilesystemIterator;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Yaml\Yaml;

/**
 * Finds the texts of the form definitions of a directory, by reading them.
 *
 * It reads YAML, JSON and PHP files and takes from each definition the texts of
 * `FormTexts`, in the domain of its `options.translation_domain`. It never runs the code:
 *
 *   - A PHP file is read when its definition is an array written in the file
 *     (returned by the file or by the closure that it returns). A text that is
 *     not a literal (a call of a local function, a concatenation) is reported as
 *     dynamic.
 *   - The calls `$context['_t']('Text', [], 'domain')` are found wherever they
 *     are, because they are written for what is not in the list of `FormTexts`.
 *     Their domain is the one of the call (`messages` when it has none).
 *
 * Part of the lint tools: it is for tools and tests, never for the code that
 * runs the package. It needs `symfony/yaml` (to read YAML) and
 * `nikic/php-parser` (to read PHP).
 */
final class FormDefinitionScanner
{
    /**
     * Marks, inside a definition that was read from PHP, a text that is not a
     * literal. The text that follows is the code of the expression.
     */
    private const UNREADABLE = "\0unreadable:";

    /**
     * Marks the call of `$context['_t']`, which is found by its own.
     */
    private const TRANSLATED = "\0translated";

    private const EXTENSIONS = ['yaml', 'yml', 'json', 'php'];

    public function __construct(private readonly FormTexts $texts = new FormTexts())
    {
    }

    /**
     * The texts of every definition of a directory and its subdirectories, in
     * order of file.
     *
     * @return list<FormText>
     * @throws InvalidArgumentException If the directory does not exist.
     */
    public function scanDirectory(string $directory): array
    {
        if (!is_dir($directory)) {
            throw new InvalidArgumentException([
                'The directory "{directory}" does not exist.',
                'directory' => $directory,
            ]);
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), self::EXTENSIONS, true)) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);

        $texts = [];
        foreach ($files as $file) {
            $texts = array_merge($texts, $this->scanFile($file));
        }

        return $texts;
    }

    /**
     * The texts of the definition of a file.
     *
     * @return list<FormText>
     * @throws InvalidArgumentException If the file does not exist or its
     * extension is not one of a definition.
     * @throws \Symfony\Component\Yaml\Exception\ParseException If a YAML file can
     * not be parsed.
     * @throws \JsonException If a JSON file can not be parsed.
     * @throws \PhpParser\Error If a PHP file can not be parsed.
     */
    public function scanFile(string $file): array
    {
        if (!is_file($file)) {
            throw new InvalidArgumentException([
                'The file "{file}" does not exist.',
                'file' => $file,
            ]);
        }

        $extension = pathinfo($file, PATHINFO_EXTENSION);

        return match ($extension) {
            'yaml', 'yml' => $this->scanDefinition(Yaml::parseFile($file), $file),
            'json' => $this->scanDefinition(
                json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR),
                $file
            ),
            'php' => $this->scanPhp($file),
            default => throw new InvalidArgumentException([
                'The file "{file}" is not a form definition (YAML, JSON or PHP).',
                'file' => $file,
            ]),
        };
    }

    /**
     * @return list<FormText>
     */
    private function scanDefinition(mixed $definition, string $file): array
    {
        if (!is_array($definition)) {
            return [];
        }

        $options = $definition['options'] ?? null;
        $domain = is_array($options) ? ($options['translation_domain'] ?? null) : null;
        $domain = is_string($domain) && $domain !== '' ? $domain : null;

        $texts = [];
        foreach ($this->texts->collect($definition) as ['text' => $text, 'path' => $path]) {
            if ($text === self::TRANSLATED) {
                continue;
            }

            $texts[] = str_starts_with($text, self::UNREADABLE)
                ? new FormText(null, $domain, $file, $path, substr($text, strlen(self::UNREADABLE)))
                : new FormText($text, $domain, $file, $path);
        }

        return $texts;
    }

    /**
     * @return list<FormText>
     */
    private function scanPhp(string $file): array
    {
        $ast = (new ParserFactory())->createForNewestSupportedVersion()->parse(
            (string) file_get_contents($file)
        ) ?? [];

        $printer = new Standard();

        $collector = new class () extends NodeVisitorAbstract {
            /** @var list<Expr\Array_> */
            public array $definitions = [];

            /** @var list<Expr\FuncCall> */
            public array $calls = [];

            public function enterNode(Node $node): ?int
            {
                if ($node instanceof Expr\Array_ && self::isDefinition($node)) {
                    $this->definitions[] = $node;
                }

                if ($node instanceof Expr\FuncCall && self::isTranslation($node)) {
                    $this->calls[] = $node;
                }

                return null;
            }

            private static function isDefinition(Expr\Array_ $array): bool
            {
                foreach ($array->items as $item) {
                    if ($item->key instanceof Node\Scalar\String_
                        && in_array($item->key->value, ['schema', 'uischema'], true)
                    ) {
                        return true;
                    }
                }

                return false;
            }

            private static function isTranslation(Expr\FuncCall $call): bool
            {
                return $call->name instanceof Expr\ArrayDimFetch
                    && $call->name->var instanceof Expr\Variable
                    && $call->name->dim instanceof Node\Scalar\String_
                    && $call->name->dim->value === '_t';
            }
        };

        $traverser = new NodeTraverser($collector);
        $traverser->traverse($ast);

        $texts = [];

        // The definitions written in the file, outermost first and once each.
        $inside = [];
        foreach ($collector->definitions as $array) {
            if (isset($inside[spl_object_id($array)])) {
                continue;
            }
            foreach ((new \PhpParser\NodeFinder())->findInstanceOf($array->items, Expr\Array_::class) as $nested) {
                $inside[spl_object_id($nested)] = true;
            }

            $texts = array_merge(
                $texts,
                $this->scanDefinition($this->array($array, $printer), $file)
            );
        }

        foreach ($collector->calls as $call) {
            $texts[] = $this->translation($call, $file, $printer);
        }

        return $texts;
    }

    /**
     * What the call of `$context['_t']` translates: its id and its domain.
     */
    private function translation(Expr\FuncCall $call, string $file, Standard $printer): FormText
    {
        $arguments = $call->args;
        $id = isset($arguments[0]) && $arguments[0] instanceof Node\Arg ? $arguments[0]->value : null;

        $domain = 'messages';
        foreach ($arguments as $position => $argument) {
            if (!$argument instanceof Node\Arg) {
                continue;
            }
            $named = $argument->name?->toString();
            if ($named === 'domain' || ($named === null && $position === 2)) {
                $domain = $argument->value instanceof Node\Scalar\String_ ? $argument->value->value : null;
            }
        }

        $where = 'line ' . $call->getStartLine();

        if (!$id instanceof Node\Scalar\String_ || $domain === null) {
            return new FormText(null, null, $file, $where, $printer->prettyPrintExpr($call));
        }

        return new FormText($id->value, $domain, $file, $where);
    }

    /**
     * The array that is written in the code, with what is not a literal
     * replaced by a mark.
     *
     * @return array<int|string, mixed>
     */
    private function array(Expr\Array_ $array, Standard $printer): array
    {
        $result = [];

        foreach ($array->items as $item) {
            $value = $this->value($item->value, $printer);

            if ($item->key === null) {
                $result[] = $value;

                continue;
            }

            $key = $this->value($item->key, $printer);
            $result[is_int($key) || is_string($key) ? $key : $printer->prettyPrintExpr($item->key)] = $value;
        }

        return $result;
    }

    private function value(Expr $expr, Standard $printer): mixed
    {
        return match (true) {
            $expr instanceof Expr\Array_ => $this->array($expr, $printer),
            $expr instanceof Node\Scalar\String_ => $expr->value,
            $expr instanceof Node\Scalar\Int_ => $expr->value,
            $expr instanceof Node\Scalar\Float_ => $expr->value,
            $expr instanceof Expr\ConstFetch => match (strtolower($expr->name->toString())) {
                'true' => true,
                'false' => false,
                'null' => null,
                default => self::UNREADABLE . $printer->prettyPrintExpr($expr),
            },
            $expr instanceof Expr\FuncCall
                && $expr->name instanceof Expr\ArrayDimFetch
                && $expr->name->dim instanceof Node\Scalar\String_
                && $expr->name->dim->value === '_t' => self::TRANSLATED,
            default => self::UNREADABLE . $printer->prettyPrintExpr($expr),
        };
    }
}
