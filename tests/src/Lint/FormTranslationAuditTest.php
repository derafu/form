<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Lint;

use Derafu\Form\Lint\FormDefinitionScanner;
use Derafu\Form\Lint\FormText;
use Derafu\Form\Lint\FormTranslationAudit;
use Derafu\Form\Lint\FormTranslationAuditReport;
use Derafu\Form\Translation\FormTexts;
use Derafu\Translation\SimpleTranslationResourceProvider;
use InvalidArgumentException;
use JsonException;
use PhpParser\Error as PhpParserError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * The texts of the definitions are found by reading them (YAML, JSON and PHP)
 * and checked against a real catalogue. The fixtures of `fixtures/audit` have
 * one definition of each kind, and a text of each case.
 */
#[CoversClass(FormDefinitionScanner::class)]
#[CoversClass(FormTranslationAudit::class)]
#[CoversClass(FormTranslationAuditReport::class)]
#[CoversClass(FormText::class)]
#[UsesClass(FormTexts::class)]
final class FormTranslationAuditTest extends TestCase
{
    private const AUDIT = __DIR__ . '/../../fixtures/audit';

    private function report(): FormTranslationAuditReport
    {
        return (new FormTranslationAudit())->audit(
            self::AUDIT,
            new SimpleTranslationResourceProvider([__DIR__ . '/../../fixtures/translated/translations'])
        );
    }

    #[Test]
    public function findsTheTextsOfTheDefinitionsOfEveryKind(): void
    {
        $report = $this->report();

        $this->assertFalse($report->nothingFound);
        $this->assertSame(
            [
                'a-contact.form.yaml schema.title "Contact us" [contact]',
                'a-contact.form.yaml schema.properties.name.title "Your name" [contact]',
                'b-missing.form.json schema.properties.name.title "Not in the catalogue" [contact]',
                'd-array.form.php schema.properties.tags.title "Tags" [contact]',
                'd-array.form.php uischema.elements.0.options.help "Help text" [contact]',
                // The calls of `$context['_t']` are found wherever they are, with
                // the domain of the call or `messages`.
                'e-closure.form.php line 15 "Hello {name}" [contact]',
                'e-closure.form.php line 24 "days" [contact]',
                'e-closure.form.php line 34 "Good day" [messages]',
            ],
            $report->describe($report->texts)
        );
    }

    #[Test]
    public function aTextThatIsNotALiteralIsReportedAsDynamic(): void
    {
        $report = $this->report();

        $this->assertSame(
            [
                "d-array.form.php schema.properties.size.title 'Size ' . 'in cm'",
                "e-closure.form.php schema.properties.a.title \$t('Your name')",
                "e-closure.form.php line 6 \$context['_t'](\$id, [], 'contact')",
                "e-closure.form.php line 29 \$context['_t'](\$id)",
            ],
            $report->describe($report->dynamicTexts)
        );
    }

    #[Test]
    public function theTextsOfADefinitionWithoutADomainAreReportedApart(): void
    {
        $report = $this->report();

        $this->assertSame(
            ['c-without-domain.form.yaml schema.properties.kind.title "Kind" [(no domain)]'],
            $report->describe($report->withoutDomain)
        );
    }

    #[Test]
    public function findsTheTextsWithoutAnEntryInTheCatalogue(): void
    {
        $report = $this->report();

        $this->assertSame(
            ['b-missing.form.json schema.properties.name.title "Not in the catalogue" [contact]'],
            $report->describe($report->missingTranslations)
        );
    }

    #[Test]
    public function findsTheEntriesOfTheCataloguesThatNoTextUses(): void
    {
        $report = $this->report();

        // Only in the domains that the definitions use, and 'Kind' is not used
        // because its definition has no domain.
        $this->assertSame(
            [
                '"We will answer you soon" [contact]',
                '"As in your ID" [contact]',
                '"Kind" [contact]',
                '"Option A" [contact]',
                '"Option B" [contact]',
                '"Type here" [contact]',
                '"Details" [contact]',
                '"Sales" [contact]',
                '"Support" [contact]',
                // The catalogue of `messages` has an entry that is only there to
                // show that the texts of a definition without a domain do not go
                // to it (see the test of the factory).
                '"Your name" [messages]',
            ],
            $report->describe($report->notUsedBySources)
        );
    }

    #[Test]
    public function aDirectoryWithoutDefinitionsFindsNothing(): void
    {
        $directory = sys_get_temp_dir() . '/form-audit-' . uniqid('', true);
        mkdir($directory);

        try {
            $report = (new FormTranslationAudit())->audit(
                $directory,
                new SimpleTranslationResourceProvider([__DIR__ . '/../../fixtures/translated/translations'])
            );
        } finally {
            rmdir($directory);
        }

        $this->assertTrue($report->nothingFound);
        $this->assertSame([], $report->texts);
    }

    #[Test]
    public function aDirectoryThatDoesNotExistFailsLoudly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        (new FormDefinitionScanner())->scanDirectory(self::AUDIT . '/nothing-here');
    }

    #[Test]
    public function aFileThatIsNotADefinitionFailsLoudly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not a form definition');

        (new FormDefinitionScanner())->scanFile(dirname(__DIR__, 3) . '/README.md');
    }

    #[Test]
    public function aFileThatDoesNotExistFailsLoudly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        (new FormDefinitionScanner())->scanFile(self::AUDIT . '/nothing.form.yaml');
    }

    #[Test]
    public function aFileThatCanNotBeParsedFailsLoudly(): void
    {
        $scanner = new FormDefinitionScanner();
        $forms = __DIR__ . '/../../fixtures/forms';

        // A PHP file with a syntax error is made here: a fixture of that kind
        // would make the tools that read the tests fail.
        $syntax = sys_get_temp_dir() . '/form-syntax-' . uniqid('', true) . '.form.php';
        file_put_contents($syntax, "<?php\n\nreturn [\n");

        $failures = [];
        try {
            foreach ([
                ParseException::class => $forms . '/broken.form.yaml',
                JsonException::class => $forms . '/broken.form.json',
                PhpParserError::class => $syntax,
            ] as $exception => $file) {
                try {
                    $scanner->scanFile($file);
                } catch (\Throwable $e) {
                    $failures[$exception] = $e::class;
                }
            }
        } finally {
            unlink($syntax);
        }

        $this->assertSame(
            [
                ParseException::class => ParseException::class,
                JsonException::class => JsonException::class,
                PhpParserError::class => PhpParserError::class,
            ],
            $failures
        );
    }
}
