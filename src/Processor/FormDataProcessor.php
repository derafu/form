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
use Derafu\Form\Contract\Schema\ObjectSchemaInterface;
use Derafu\Form\Data\FieldPath;
use Derafu\Form\Exception\CaptchaUnavailableException;
use Derafu\Support\Value;
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
     * of the forms that are protected with it (see
     * FormInterface::isCaptchaProtected()). A form that is protected can not be
     * processed without it, unless the application decided not to have one.
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
        $captchaProvider = null;
        $captchaResponse = null;
        if ($form->isCaptchaProtected() && !$this->captchaProvider?->isDisabled()) {
            $captchaProvider = $this->captchaProvider;
            if ($captchaProvider === null || !$captchaProvider->isAvailable()) {
                throw new LogicException([
                    'The form "{form}" is protected with a captcha, but the application has none. Configure one (derafu/captcha has them: for example CAPTCHA_PROVIDER=altcha with CAPTCHA_SECRET_KEY, which needs no account), or turn the protection off with the option "captcha_protection" of the form.',
                    'form' => $form->getId(),
                ]);
            }

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

        // A read-only field is not what was sent: it is the value that the form
        // has (the same that was drawn), which is also the one that the rules
        // of the UI schema see.
        $readOnlyValues = [];
        foreach ($form->getFields() as $fieldName => $field) {
            if ($field->getProperty()->isReadOnly() === true) {
                $readOnlyValues[$fieldName] = $field->getData();
                unset($data[$fieldName]);
                $data = FieldPath::set($data, $fieldName, $readOnlyValues[$fieldName]);
            }
        }

        // Process each field using the rules now stored in the form.
        foreach ($form->getFields() as $fieldName => $field) {
            // Skip fields whose UiSchema rule makes them inactive given the
            // current submitted data. Inactive fields are not validated and
            // not included in the processed result.
            if (!$this->isFieldActive($field, $data)) {
                $skippedFields[$fieldName] = true;
                continue;
            }

            // A read-only field has no rules to apply: its value is not an
            // input of the visitor.
            if (array_key_exists($fieldName, $readOnlyValues)) {
                $processedData = FieldPath::set($processedData, $fieldName, $readOnlyValues[$fieldName]);
                continue;
            }

            $fieldValue = FieldPath::get($data, $fieldName);
            $fieldRules = $form->getRules()[$fieldName];

            // A checkbox that is not checked is not sent by the browser: the
            // field that is a boolean and has no value is `false`, not a value
            // that is missing (which the processor leaves as `null`).
            if (
                in_array($fieldRules['cast'] ?? null, ['boolean', 'bool'], true)
                && ($fieldValue === null || $fieldValue === '')
            ) {
                $fieldValue = false;
            }

            try {
                // Process the field value through all rules.
                $processedValue = $this->processor->process(
                    $fieldValue,
                    $fieldRules
                );
                $processedData = FieldPath::set($processedData, $fieldName, $processedValue);
            } catch (Throwable $e) {
                // Collect validation and other processing errors.
                $errors[$fieldName] = [$this->resolveErrorMessage($e)];
                $isValid = false;
                // Keep original value for invalid fields.
                $processedData = FieldPath::set($processedData, $fieldName, $fieldValue);
            }
        }

        // The fields that an object requires when another one has a value
        // (`dependentRequired`). The error is the one of `required`, in each
        // field that is missing.
        $missingDependents = $this->missingDependents(
            $form->getSchema(),
            '',
            $processedData,
            $form->getFields(),
            $skippedFields
        );
        foreach ($missingDependents as $name) {
            try {
                $this->processor->process(null, ['validate' => ['required']]);
            } catch (Throwable $e) {
                $errors[$name] = [$this->resolveErrorMessage($e)];
                $isValid = false;
            }
        }

        // The form is closed: what was sent and is not a field of the form is
        // not in the result, unless the object that has it says
        // `additionalProperties` (`true`, or the schema of the properties).
        $processedData = $this->withAdditionalProperties(
            $form->getSchema(),
            '',
            $data,
            $processedData,
            array_keys($form->getFields()),
            $skippedFields
        );

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
     * Adds to the result what was sent in the objects that are open.
     *
     * An object is open when it has `additionalProperties` with `true` or with
     * a schema. The key of an object that has fields of the form is not an
     * extra one: its fields are the ones that were processed. A field that was
     * skipped due to an inactive rule is not added back, and a field that is
     * `null` was processed (an empty value is `null`), so it is not replaced
     * with what was sent.
     *
     * @param ObjectSchemaInterface $object The object of the schema.
     * @param string $path The path of the object (`''` is the root).
     * @param array<array-key, mixed> $data What was sent.
     * @param array<array-key, mixed> $processedData The processed data.
     * @param list<string> $fieldNames The names of the fields of the form.
     * @param array<string, true> $skippedFields The fields that were skipped.
     * @return array<array-key, mixed> The processed data with what was added.
     */
    private function withAdditionalProperties(
        ObjectSchemaInterface $object,
        string $path,
        array $data,
        array $processedData,
        array $fieldNames,
        array $skippedFields
    ): array {
        $prefix = $path === '' ? '' : $path . '.';

        $sent = $path === '' ? $data : FieldPath::get($data, $path);
        if ($object->getAdditionalProperties() !== false && is_array($sent)) {
            $level = $path === '' ? $processedData : FieldPath::get($processedData, $path);
            $level = is_array($level) ? $level : [];

            foreach ($sent as $key => $value) {
                $name = $prefix . $key;
                if (
                    !array_key_exists($key, $level)
                    && !isset($skippedFields[$name])
                    && !$this->hasFieldsIn($name, $fieldNames)
                ) {
                    $level[$key] = $value;
                }
            }

            $processedData = $path === '' ? $level : FieldPath::set($processedData, $path, $level);
        }

        foreach ($object->getProperties() as $name => $property) {
            if ($property instanceof ObjectSchemaInterface) {
                $processedData = $this->withAdditionalProperties(
                    $property,
                    $prefix . $name,
                    $data,
                    $processedData,
                    $fieldNames,
                    $skippedFields
                );
            }
        }

        return $processedData;
    }

    /**
     * Whether an object of the schema has fields of the form.
     *
     * @param string $path The path of the object.
     * @param list<string> $fieldNames The names of the fields of the form.
     */
    private function hasFieldsIn(string $path, array $fieldNames): bool
    {
        foreach ($fieldNames as $fieldName) {
            if (str_starts_with($fieldName, $path . '.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * The fields that are required because of `dependentRequired`, and that
     * have no value.
     *
     * The names in the object are the ones of its path (in `contact`, the
     * field `card` is `contact.card`). A field that is not a field of the form,
     * or that was skipped due to an inactive rule, has no value and is not
     * required. A trigger that is a checkbox that is not checked (`false`) has
     * no value either.
     *
     * @param ObjectSchemaInterface $object The object of the schema.
     * @param string $path The path of the object (`''` is the root).
     * @param array<array-key, mixed> $processedData The processed data.
     * @param array<string, FormFieldInterface> $fields The fields of the form.
     * @param array<string, true> $skippedFields The fields that were skipped.
     * @return list<string> The names of the fields.
     */
    private function missingDependents(
        ObjectSchemaInterface $object,
        string $path,
        array $processedData,
        array $fields,
        array $skippedFields
    ): array {
        $prefix = $path === '' ? '' : $path . '.';
        $missing = [];

        foreach ($object->getDependentRequired() ?? [] as $trigger => $dependents) {
            $triggerName = $prefix . $trigger;
            if (
                !isset($fields[$triggerName])
                || isset($skippedFields[$triggerName])
                || Value::isBlank(FieldPath::get($processedData, $triggerName))
                || FieldPath::get($processedData, $triggerName) === false
            ) {
                continue;
            }

            foreach ($dependents as $dependent) {
                $name = $prefix . $dependent;
                if (
                    isset($fields[$name])
                    && !isset($skippedFields[$name])
                    && Value::isBlank(FieldPath::get($processedData, $name))
                ) {
                    $missing[] = $name;
                }
            }
        }

        foreach ($object->getProperties() as $name => $property) {
            if ($property instanceof ObjectSchemaInterface) {
                array_push($missing, ...$this->missingDependents(
                    $property,
                    $prefix . $name,
                    $processedData,
                    $fields,
                    $skippedFields
                ));
            }
        }

        return $missing;
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
     * @param array<array-key, mixed> $data The full submitted data array.
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
