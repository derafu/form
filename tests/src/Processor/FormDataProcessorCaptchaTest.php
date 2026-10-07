<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Processor;

use Derafu\DataProcessor\ProcessorFactory;
use Derafu\Form\Form;
use Derafu\Form\Processor\FormDataProcessor;
use Derafu\Form\Processor\FormRulesResolver;
use Derafu\Form\Processor\ProcessResult;
use Derafu\TestsForm\Captcha\InMemoryCaptchaProvider;
use Derafu\TestsForm\Csrf\InMemoryCsrfTokenManager;
use Derafu\Translation\Exception\Core\TranslatableLogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

/**
 * The captcha of a form that asks for it is checked when its data is
 * processed, once the rest is valid, and what the visitor solved is not part of
 * the data.
 */
#[CoversClass(FormDataProcessor::class)]
#[CoversClass(ProcessResult::class)]
#[CoversClass(Form::class)]
#[UsesClass(\Derafu\Form\Abstract\AbstractPropertySchema::class)]
#[UsesClass(\Derafu\Form\Abstract\AbstractUiSchemaElement::class)]
#[UsesClass(\Derafu\Form\Factory\FormUiSchemaFactory::class)]
#[UsesClass(\Derafu\Form\Factory\PropertySchemaFactory::class)]
#[UsesClass(\Derafu\Form\Factory\UiSchemaElementFactory::class)]
#[UsesClass(\Derafu\Form\FormField::class)]
#[UsesClass(\Derafu\Form\Options\FormOptions::class)]
#[UsesClass(\Derafu\Form\Processor\FormRulesResolver::class)]
#[UsesClass(\Derafu\Form\Rules\FormRules::class)]
#[UsesClass(\Derafu\Form\Schema\FormSchema::class)]
#[UsesTrait(\Derafu\Form\Schema\ObjectSchemaTrait::class)]
#[UsesClass(\Derafu\Form\Schema\StringSchema::class)]
#[UsesClass(\Derafu\Form\UiSchema\Control::class)]
#[UsesClass(\Derafu\Form\UiSchema\VerticalLayout::class)]
#[UsesClass(\Derafu\Form\Widget\Widget::class)]
#[UsesClass(\Derafu\Form\Widget\WidgetFactory::class)]
final class FormDataProcessorCaptchaTest extends TestCase
{
    private const INVALID = 'The captcha is not valid. Try again.';

    private const UNAVAILABLE = 'The captcha could not be verified. Try again in a moment.';

    private InMemoryCaptchaProvider $captcha;

    private InMemoryCsrfTokenManager $csrf;

    protected function setUp(): void
    {
        $this->captcha = new InMemoryCaptchaProvider();
        $this->csrf = new InMemoryCsrfTokenManager();
    }

    private function processor(?InMemoryCaptchaProvider $captcha = null, ?Translator $translator = null): FormDataProcessor
    {
        return new FormDataProcessor(
            new FormRulesResolver(),
            (new ProcessorFactory())->create(),
            translator: $translator,
            locale: $translator?->getLocale(),
            csrfTokenManager: $this->csrf,
            captchaProvider: $captcha,
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    private function form(array $options = ['captcha_protection' => true]): Form
    {
        return Form::fromArray([
            'schema' => [
                'name' => 'contact',
                'type' => 'object',
                'properties' => ['email' => ['type' => 'string', 'format' => 'email', 'title' => 'Email']],
                'required' => ['email'],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [['type' => 'Control', 'scope' => '#/properties/email']],
            ],
            'options' => $options + ['csrf_protection' => false],
        ]);
    }

    #[Test]
    public function aCaptchaThatWasSolvedIsAcceptedAndItIsNotPartOfTheData(): void
    {
        $result = $this->processor($this->captcha)->process($this->form(), [
            'email' => 'ana@example.com',
            'test-captcha-response' => 'solved-contact',
        ]);

        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->getFormErrors());
        $this->assertSame(['email' => 'ana@example.com'], $result->getProcessedData());
        $this->assertSame([['solved-contact', 'contact']], $this->captcha->verified);
    }

    #[Test]
    public function aMissingResponseIsNotValidAndTheServiceIsNotAsked(): void
    {
        $result = $this->processor($this->captcha)->process($this->form(), ['email' => 'ana@example.com']);

        $this->assertFalse($result->isValid());
        $this->assertSame([self::INVALID], $result->getFormErrors());
        $this->assertSame([], $this->captcha->verified);
    }

    #[Test]
    public function aResponseThatIsEmptyOrNotAStringIsNotValid(): void
    {
        foreach (['', ['x'], 5] as $response) {
            $result = $this->processor($this->captcha)->process($this->form(), [
                'email' => 'ana@example.com',
                'test-captcha-response' => $response,
            ]);

            $this->assertFalse($result->isValid());
            $this->assertSame([self::INVALID], $result->getFormErrors());
        }
        $this->assertSame([], $this->captcha->verified);
    }

    #[Test]
    public function aResponseThatTheServiceRejectsIsNotValid(): void
    {
        $result = $this->processor($this->captcha)->process($this->form(), [
            'email' => 'ana@example.com',
            'test-captcha-response' => 'solved-another-form',
        ]);

        $this->assertFalse($result->isValid());
        $this->assertSame([self::INVALID], $result->getFormErrors());
    }

    #[Test]
    public function aServiceThatDoesNotAnswerMakesTheFormNotValidWithAnotherMessage(): void
    {
        $result = $this->processor($this->captcha)->process($this->form(), [
            'email' => 'ana@example.com',
            'test-captcha-response' => 'unavailable',
        ]);

        $this->assertFalse($result->isValid());
        $this->assertSame([self::UNAVAILABLE], $result->getFormErrors());
    }

    #[Test]
    public function theServiceIsNotAskedWhenTheFieldsAreNotValid(): void
    {
        $result = $this->processor($this->captcha)->process($this->form(), [
            'email' => 'not-an-email',
            'test-captcha-response' => 'solved-contact',
        ]);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasFieldErrors('email'));
        $this->assertSame([], $result->getFormErrors());
        $this->assertSame([], $this->captcha->verified);
    }

    #[Test]
    public function theServiceIsNotAskedWhenTheCsrfTokenIsNotValid(): void
    {
        $result = $this->processor($this->captcha)->process($this->form(['captcha_protection' => true, 'csrf_protection' => true]), [
            'email' => 'ana@example.com',
            'test-captcha-response' => 'solved-contact',
        ]);

        $this->assertFalse($result->isValid());
        $this->assertCount(1, $result->getFormErrors());
        $this->assertSame([], $this->captcha->verified);
    }

    #[Test]
    public function bothTheCsrfTokenAndTheCaptchaAreChecked(): void
    {
        $token = $this->csrf->getToken('contact');

        $result = $this->processor($this->captcha)->process($this->form(['captcha_protection' => true, 'csrf_protection' => true]), [
            'email' => 'ana@example.com',
            '_token' => $token,
            'test-captcha-response' => 'solved-contact',
        ]);

        $this->assertTrue($result->isValid());
        $this->assertSame(['email' => 'ana@example.com'], $result->getProcessedData());
    }

    #[Test]
    public function aFormThatIsNotProtectedWithTheCaptchaIsProcessedWithoutIt(): void
    {
        $result = $this->processor($this->captcha)->process($this->form(['captcha_protection' => false]), ['email' => 'ana@example.com']);

        $this->assertTrue($result->isValid());
        $this->assertSame([], $this->captcha->verified);
    }

    #[Test]
    public function aFormThatIsProtectedCanNotBeProcessedWithoutACaptchaInTheApplication(): void
    {
        $this->expectException(TranslatableLogicException::class);
        $this->expectExceptionMessage('The form "contact" is protected with a captcha, but the application has none.');

        $this->processor(null)->process($this->form(), ['email' => 'ana@example.com']);
    }

    #[Test]
    public function aCaptchaThatIsNotAvailableIsTheSameAsNone(): void
    {
        $captcha = new InMemoryCaptchaProvider(available: false);

        try {
            $this->processor($captcha)->process($this->form(), ['email' => 'ana@example.com']);
            $this->fail('A protected form was processed without a captcha.');
        } catch (TranslatableLogicException) {
            $this->assertSame([], $captcha->verified);
        }
    }

    #[Test]
    public function anApplicationThatDecidedNotToHaveACaptchaProcessesTheFormWithoutIt(): void
    {
        $captcha = new InMemoryCaptchaProvider(available: false, disabled: true);

        $result = $this->processor($captcha)->process($this->form(), ['email' => 'ana@example.com']);

        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->getFormErrors());
        $this->assertSame([], $captcha->verified);
    }

    #[Test]
    public function aFormThatIsNotProtectedNeedsNoCaptchaInTheApplication(): void
    {
        $result = $this->processor(null)->process($this->form(['captcha_protection' => false]), ['email' => 'ana@example.com']);

        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function theMessagesAreTranslatedWhenThereIsATranslator(): void
    {
        $translator = new Translator('es');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', [
            self::INVALID => 'Captcha no válido.',
            self::UNAVAILABLE => 'No se pudo verificar.',
        ], 'es', 'errors');
        $processor = $this->processor($this->captcha, $translator);

        $invalid = $processor->process($this->form(), ['email' => 'ana@example.com']);
        $unavailable = $processor->process($this->form(), ['email' => 'ana@example.com', 'test-captcha-response' => 'unavailable']);

        $this->assertSame(['Captcha no válido.'], $invalid->getFormErrors());
        $this->assertSame(['No se pudo verificar.'], $unavailable->getFormErrors());
    }
}
