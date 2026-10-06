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

use Derafu\Translation\Contract\TranslationResourceProviderInterface;
use Derafu\Translation\TranslatorFactory;

/**
 * Audits the form definitions of a package: finds their texts (see
 * `FormDefinitionScanner`) and checks them against the catalogues of the
 * package.
 *
 * It only finds facts, in a report, about the definitions that it reads: it does
 * not say what is a problem. The test of a package says it, by asserting that
 * the lists it cares about are empty:
 *
 *     $report = (new FormTranslationAudit())->audit(
 *         dirname(__DIR__, 2) . '/resources/forms',
 *         new MyPackageTranslationResourceProvider(),
 *     );
 *
 *     $this->assertFalse($report->nothingFound);
 *     $this->assertSame([], $report->describe($report->dynamicTexts));
 *     $this->assertSame([], $report->describe($report->withoutDomain));
 *     $this->assertSame([], $report->describe($report->missingTranslations));
 *     $this->assertSame([], $report->describe($report->notUsedBySources));
 *
 * Part of the lint tools: it is for tools and tests, never for the code that
 * runs the package.
 */
final class FormTranslationAudit
{
    /**
     * Audits the definitions of a directory.
     *
     * @param string $directory Directory with the form definitions.
     * @param TranslationResourceProviderInterface|iterable<TranslationResourceProviderInterface> $providers
     * The catalogues of the package.
     * @param string $locale The locale of the catalogues that is checked.
     * @throws \InvalidArgumentException If the directory does not exist.
     */
    public function audit(
        string $directory,
        TranslationResourceProviderInterface|iterable $providers,
        string $locale = 'es'
    ): FormTranslationAuditReport {
        $providers = $providers instanceof TranslationResourceProviderInterface
            ? [$providers]
            : $providers;

        $catalogue = TranslatorFactory::create($locale, [], $providers)->getCatalogue($locale);

        $texts = (new FormDefinitionScanner())->scanDirectory($directory);

        $readable = [];
        $dynamic = [];
        $withoutDomain = [];
        $missing = [];
        $used = [];
        foreach ($texts as $text) {
            if ($text->isDynamic()) {
                $dynamic[] = $text;

                continue;
            }

            if ($text->domain === null) {
                $withoutDomain[] = $text;

                continue;
            }

            $readable[] = $text;
            $used[$text->domain][] = (string) $text->id;
            if (!$catalogue->has((string) $text->id, $text->domain)) {
                $missing[] = $text;
            }
        }

        $notUsed = [];
        $domains = array_keys($used);
        sort($domains);
        foreach ($domains as $domain) {
            foreach (array_diff(array_keys($catalogue->all($domain)), $used[$domain]) as $id) {
                $notUsed[] = ['domain' => $domain, 'id' => (string) $id];
            }
        }

        return new FormTranslationAuditReport(
            $directory,
            $readable,
            $dynamic,
            $withoutDomain,
            $missing,
            $notUsed,
            $texts === []
        );
    }
}
