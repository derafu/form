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

/**
 * What an audit of the form definitions of a package found.
 *
 * It only has facts about what was read: the definitions of the directory that
 * was audited. It does not say whether any of them is a problem: that is for the
 * test of each package to say, by asserting that the lists it cares about are
 * empty.
 *
 *     $this->assertSame([], $report->describe($report->missingTranslations));
 *
 * Part of the lint tools: it is for tools and tests, never for the code that
 * runs the package.
 */
final readonly class FormTranslationAuditReport
{
    /**
     * @param string $directory The directory that was audited.
     * @param list<FormText> $texts The texts that can be read and are in a
     * domain: each one is a message that the catalogue has to have.
     * @param list<FormText> $dynamicTexts Texts that are not a literal (for
     * example `$t('Name')` with a local function), so they can not be checked
     * by reading the definition.
     * @param list<FormText> $withoutDomain Texts of definitions that have no
     * `translationDomain`: they are not translated when the form is created.
     * @param list<FormText> $missingTranslations Texts that have no entry in the
     * catalogues, in their domain.
     * @param list<array{domain: string, id: string}> $notUsedBySources Entries
     * of the catalogues, in the domains that the definitions use, that no text
     * that was read uses. It is relative to what was read: an entry can be used
     * by something that was not, for example a template, when the domain is
     * shared.
     * @param bool $nothingFound Whether no definition with a text was found at
     * all (the directory is wrong, or has no forms).
     */
    public function __construct(
        public string $directory,
        public array $texts,
        public array $dynamicTexts,
        public array $withoutDomain,
        public array $missingTranslations,
        public array $notUsedBySources,
        public bool $nothingFound
    ) {
    }

    /**
     * Turns findings into lines, one each, for the message of a failed test: the
     * file (relative to the audited directory) and where in it, and what it is.
     *
     * @param list<FormText|array{domain: string, id: string}> $findings
     * @return list<string>
     */
    public function describe(array $findings): array
    {
        $lines = [];

        foreach ($findings as $finding) {
            $lines[] = $finding instanceof FormText
                ? $this->file($finding->file) . ' ' . $finding->where . ' ' . (
                    $finding->isDynamic()
                        ? (string) $finding->expression
                        : sprintf('"%s" [%s]', $finding->id, $finding->domain ?? '(no domain)')
                )
                : sprintf('"%s" [%s]', $finding['id'], $finding['domain']);
        }

        return $lines;
    }

    private function file(string $file): string
    {
        $prefix = rtrim($this->directory, '/') . '/';

        return str_starts_with($file, $prefix) ? substr($file, strlen($prefix)) : $file;
    }
}
