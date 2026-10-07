<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Processor;

use Derafu\DataProcessor\Contract\ProcessorInterface;
use Derafu\Form\Contract\Captcha\CaptchaProviderInterface;
use Derafu\Form\Contract\Csrf\CsrfTokenManagerInterface;
use Derafu\Form\Contract\FormFieldInterface;
use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\Processor\FormDataProcessorInterface;
use Derafu\Form\Contract\Processor\FormRulesResolverInterface;
use Derafu\Form\Contract\Processor\UiSchemaRuleEvaluatorInterface;
use Derafu\Form\Exception\CaptchaUnavailableException;
use Derafu\Translation\Contract\TranslatableInterface;
use Derafu\Translation\Exception\Core\TranslatableLogicException as LogicException;
use Derafu\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * Service to process form data using form definitions and data processor rules.
 */
final class FormDataProcessor implements FormDataProcessorInterface
{
    /**
     * Constructor.
     *
     * @param FormRulesResolverInterface $resolver The resolver that derives and
     * merges all processing rules for the form fields.
     * @param ProcessorInterface $processor The processor to process the data
     * using Derafu\DataProcessor.
     * @param UiSchemaRuleEvaluatorInterface $evaluator The evaluator that
     * determines whether a field is active given the submitted data and the
     * field's UI schema rule. Defaults to a plain UiSchemaRuleEvaluator so
     * that existing call sites that instantiate FormDataProcessor directly
     * (e.g., tests) do not need to change.
     * @param TranslatorInterface|null $translator Optional translator used to
     * translate processing errors that implement TranslatableInterface (e.g.
     * Derafu\DataProcessor's ValidationException). When null, the untranslated
     * (English) exception message is used, same as before this parameter
     * existed.
     * @param string|null $locale The locale to translate error messages to.
     * Only used when $translator is provided.
     * @param CsrfTokenManagerInterface|null $csrfTokenManager Checks the CSRF
     * token of the forms that are protected (see FormInterface::isCsrfProtected()).
     * A form that is protected can not be processed without it.
     * @param CaptchaProviderInterface|null $captchaProvider Checks the captcha
     * of the forms that ask for it (see FormInterface::usesCaptcha()). Without
     * it, or when it is not available, the forms are processed without captcha.
     */
    public function __construct(
        private readonly FormRulesResolverInterface $resolver,
        private readonly ProcessorInterface $processor,
        private readonly UiSchemaRuleEvaluatorInterface $evaluator = new UiSchemaRuleEvaluator(),
        private readonly ?TranslatorInterface $translator = null,
        private readonly ?string $locale = null,
        private readonly ?CsrfTokenManagerInterface $csrfTokenManager = null,
        private readonly ?CaptchaProviderInterface $captchaProvider = null,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function process(FormInterface $form, array $data = []): ProcessResult
    {
        // If no data is provided, get it from the request.
        if (empty($data)) {
            // The data should never be empty. It's the responsibility of the
            // caller to provide the data. This solution is a workaround to
            // avoid the need to pass the request to the processor in some
            // cases, but it's not a good solution neither recommended.
            $data = array_merge($_POST, $_FILES);
        }

        $processedData = [];
        $errors = [];
        $formErrors = [];
        $isValid = true;

        // The CSRF token is not data of the form: it is checked and it is not
        // part of what is processed.
        if ($form->isCsrfProtected()) {
            $csrfTokenManager = $this->csrfTokenManager ?? throw new LogicException([
                'The form "{form}" is protected with a CSRF token, but there is no CSRF token manager. Register one, or turn the protection off with the option "csrf_protection" of the form.',
                'form' => $form->getCsrfTokenId(),
            ]);

            $token = $data[FormInterface::CSRF_FIELD] ?? null;
            unset($data[FormInterface::CSRF_FIELD]);

            if (!is_string($token) || !$csrfTokenManager->isValid($form->getCsrfTokenId(), $token)) {
                $formErrors[] = $this->translated(new TranslatableMessage(
                    'The form is not valid or has expired. Reload the page and try again.',
                    [],
                    'errors'
                ));
                $isValid = false;
            }
        }

        // What the visitor solved of the captcha is not data of the form either:
        // it is taken out here (the raw data is what was sent) and it is checked
        // at the end, when the rest is valid, so the service is not asked about
        // a form that is not going to be accepted.
        $captchaProvider = $form->usesCaptcha() && $this->captchaProvider?->isAvailable()
            ? $this->captchaProvider
            : null
        ;
        $captchaResponse = null;
        if ($captchaProvider !== null) {
            $captchaField = $captchaProvider->getResponseField();
            $captchaResponse = $data[$captchaField] ?? null;
            unset($data[$captchaField]);
        }

        // Tracks field names that were intentionally skipped due to an
        // inactive UiSchema rule. These must not be re-added by the
        // extra-fields loop below, even if they were submitted in $data.
        $skippedFields = [];

        // Resolve the complete, merged rules into the form's own FormRules instance.
        $this->resolver->resolve($form);

        // Process each field using the rules now stored in the form.
        foreach ($form->getFields() as $fieldName => $field) {
            // Skip fields whose UiSchema rule makes them inactive given the
            // current submitted data. Inactive fields are not validated and
            // not included in the processed result.
            if (!$this->isFieldActive($field, $data)) {
                $skippedFields[$fieldName] = true;
                continue;
            }

            $fieldValue = $data[$fieldName] ?? null;
            $fieldRules = $form->getRules()[$fieldName];

            try {
                // Process the field value through all rules.
                $processedValue = $this->processor->process(
                    $fieldValue,
                    $fieldRules
                );
                $processedData[$fieldName] = $processedValue;
            } catch (Throwable $e) {
                // Collect validation and other processing errors.
                $errors[$fieldName] = [$this->resolveErrorMessage($e)];
                $isValid = false;
                // Keep original value for invalid fields.
                $processedData[$fieldName] = $fieldValue;
            }
        }

        // Add any fields from data that weren't in the schema, excluding
        // fields that were intentionally skipped due to an inactive rule.
        foreach ($data as $fieldName => $fieldValue) {
            if (!isset($processedData[$fieldName]) && !isset($skippedFields[$fieldName])) {
                $processedData[$fieldName] = $fieldValue;
            }
        }

        if ($captchaProvider !== null && $isValid) {
            $captchaError = $this->checkCaptcha($captchaProvider, $captchaResponse, $form->getId());
            if ($captchaError !== null) {
                $formErrors[] = $captchaError;
                $isValid = false;
            }
        }

        return new ProcessResult($form, $processedData, $errors, $isValid, $formErrors);
    }

    /**
     * Checks what the visitor solved of the captcha.
     *
     * @param mixed $response What came in the field of the response.
     * @param string $formId The id of the form.
     * @return string|null The message of the error, or `null` if it is valid.
     */
    private function checkCaptcha(CaptchaProviderInterface $provider, mixed $response, string $formId): ?string
    {
        try {
            $valid = is_string($response)
                && $response !== ''
                && $provider->verify($response, $formId)
            ;
        } catch (CaptchaUnavailableException) {
            return $this->translated(new TranslatableMessage(
                'The captcha could not be verified. Try again in a moment.',
                [],
                'errors'
            ));
        }

        return $valid ? null : $this->translated(new TranslatableMessage(
            'The captcha is not valid. Try again.',
            [],
            'errors'
        ));
    }

    /**
     * A message of the processing, translated when there is a translator.
     */
    private function translated(TranslatableMessage $message): string
    {
        return $this->translator !== null
            ? $message->trans($this->translator, $this->locale)
            : (string) $message
        ;
    }

    /**
     * Resolves the display message for a processing error.
     *
     * Translates the exception's message when a translator was provided and
     * the exception supports translation. Otherwise falls back to the
     * exception's own (English by default) message.
     *
     * @param Throwable $e The exception raised while processing a field.
     * @return string
     */
    private function resolveErrorMessage(Throwable $e): string
    {
        if ($this->translator !== null && $e instanceof TranslatableInterface) {
            return $e->trans($this->translator, $this->locale);
        }

        return $e->getMessage();
    }

    // =========================================================================
    // Rule evaluation
    // =========================================================================

    /**
     * Determines whether a field is active given the current submitted data.
     *
     * Delegates to UiSchemaRuleEvaluator. A field with no rule is always active.
     *
     * @param FormFieldInterface $field The field to evaluate.
     * @param array $data The full submitted data array.
     * @return bool True if the field should be processed, false if it must be skipped.
     */
    private function isFieldActive(FormFieldInterface $field, array $data): bool
    {
        $rule = $field->getControl()->getRule();

        if ($rule === null) {
            return true;
        }

        return $this->evaluator->isActive($rule, $data);
    }
}
