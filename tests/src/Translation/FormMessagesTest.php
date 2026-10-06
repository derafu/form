<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Translation;

use Derafu\Form\Renderer\Support\InputActionResolver;
use Derafu\Form\Translation\FormTranslationResourceProvider;
use Derafu\Translation\Lint\MessageMethod;
use Derafu\Translation\Lint\MessageReference;
use Derafu\Twig\Lint\TwigTranslationAudit;
use Derafu\Twig\Service\TwigService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * The package is translated: every message of its code and of its templates has
 * its Spanish translation (in the domain of the exceptions, `errors`, and in the
 * one of the interface texts, `form`), the catalogue has nothing that they do
 * not use, every text of the templates goes through the translation, and every
 * exception that the package throws is translatable.
 *
 * It is found by reading the code and the templates, so a new message without an
 * entry in the catalogue fails here, instead of showing in the original language
 * when it is shown.
 *
 * `InputActionResolver::trans()` is declared here as a method of messages of the
 * domain `form`, so what its callers write is audited. Four calls can not have a
 * literal, by nature, and they are fixed here by their whole call, so any other
 * message that is not a literal makes this test fail:
 *
 *   - `TranslatingFormFactory` and the function `_t` of `PhpFormLoader` translate
 *     the text that the author of a form wrote: it is audited where it is
 *     written, by `Derafu\Form\Lint\FormTranslationAudit`.
 *   - The message of the exception of a field that is not valid is translated
 *     as it is: it is audited where the exception is thrown.
 *   - `InputActionResolver::trans()` translates the message that it is given: it
 *     is audited where it is called.
 */
#[CoversClass(FormTranslationResourceProvider::class)]
final class FormMessagesTest extends TestCase
{
    public function testThePackageIsTranslated(): void
    {
        $root = dirname(__DIR__, 3);

        // The templates use the functions of the form renderer: they are only
        // declared here.
        $renderer = new class () extends AbstractExtension {
            public function getFunctions(): array
            {
                return array_map(
                    fn (string $name) => new TwigFunction($name, fn () => ''),
                    [
                        'form', 'form_body', 'form_start', 'form_end', 'form_label',
                        'form_errors', 'form_widget', 'form_help', 'form_row',
                        'form_rest', 'form_enctype', 'form_element', 'form_elements',
                        'form_csrf', 'form_global_errors',
                    ]
                );
            }
        };

        $report = (new TwigTranslationAudit())->audit(
            $root . '/src',
            $root . '/resources/templates',
            new FormTranslationResourceProvider(),
            (new TwigService([
                'extra' => false,
                'paths' => [$root . '/resources/templates', $root . '/vendor/derafu/twig/resources/templates'],
                'extensions' => [$renderer],
            ]))->getTwig(),
            messageMethods: [
                new MessageMethod(InputActionResolver::class, 'trans', domain: 'form', id: 0),
            ]
        );

        // Finding nothing would look like a clean result.
        $this->assertFalse($report->nothingFound);
        $this->assertSame([], $report->describe($report->missingTranslations));
        $this->assertSame([], $report->describe($report->notUsedBySources));
        $this->assertSame([], $report->describe($report->notTranslatable));
        $this->assertSame([], $report->describe($report->untranslatedTexts));

        $this->assertSame(
            [
                'Derafu\\Form\\Factory\\TranslatingFormFactory::create::{closure}: '
                    . '$this->translator->trans($text, [], $domain)',
                'Derafu\\Form\\Loader\\PhpFormLoader::load::{closure}: '
                    . '$translator->trans($id, $parameters, $domain)',
                'Derafu\\Form\\Processor\\FormDataProcessor::csrfErrorMessage: '
                    . '$message->trans($this->translator, $this->locale)',
                'Derafu\\Form\\Processor\\FormDataProcessor::resolveErrorMessage: '
                    . '$e->trans($this->translator, $this->locale)',
                'Derafu\\Form\\Renderer\\Support\\InputActionResolver::trans: '
                    . '$translatable->trans($this->translator, $this->locale)',
            ],
            array_map(fn (MessageReference $reference) => $reference->identity(), $report->dynamicMessages)
        );
    }
}
