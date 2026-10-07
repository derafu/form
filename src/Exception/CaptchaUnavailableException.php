<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Exception;

use Derafu\Translation\Exception\Core\TranslatableRuntimeException;

/**
 * The captcha service could not be asked (no answer, a timeout, an answer that
 * is not understood), so it is not known if what the visitor solved is valid.
 *
 * It is what an implementation of `CaptchaProviderInterface` throws when it can
 * not verify: the form is not valid, as with a captcha that is not valid, but
 * the visitor is told that it can try again.
 */
final class CaptchaUnavailableException extends TranslatableRuntimeException
{
}
