<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Contract\Captcha;

use Derafu\Form\Exception\CaptchaUnavailableException;

/**
 * Gives the widget of a captcha and checks what the visitor solved.
 *
 * The forms do not know which captcha service it is, nor its keys, nor how it
 * talks to it: that is what each implementation knows (hCaptcha, Turnstile,
 * reCAPTCHA...). A form asks for the captcha with its option `captcha`.
 */
interface CaptchaProviderInterface
{
    /**
     * Whether there is a captcha to use: the application has configured one.
     *
     * A form that asks for the captcha does not have it when this is `false`:
     * nothing is rendered and nothing is checked.
     */
    public function isAvailable(): bool;

    /**
     * Gets the name of the field that carries what the visitor solved (the
     * widget writes it in the form), which is of each service.
     */
    public function getResponseField(): string;

    /**
     * Gets the HTML of the widget, with its scripts.
     *
     * @param string $formId The id of the form (see FormInterface::getId()).
     * @return string The HTML to write in the form.
     */
    public function getWidget(string $formId): string;

    /**
     * Checks what the visitor solved.
     *
     * @param string $token What came in the field of the response.
     * @param string $formId The id of the form (see FormInterface::getId()).
     * @return bool Whether it is the proof of a person for that form.
     * @throws CaptchaUnavailableException If the service could not be asked, so
     * it is not known: the form is not valid then, as it is when it is `false`.
     */
    public function verify(string $token, string $formId): bool;
}
