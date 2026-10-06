<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Contract\Csrf;

/**
 * Gives and checks the CSRF tokens of the forms.
 *
 * The forms do not know where the token is kept, nor which request or session
 * it belongs to: that is what each implementation knows (for example the one of
 * Symfony, or the one that works over the session of Mezzio).
 *
 * A token is given for an id (the form it is for) and the form asks for it every
 * time it is shown. How long a token lasts, and whether checking it uses it up,
 * is up to each implementation.
 */
interface CsrfTokenManagerInterface
{
    /**
     * Gives the token for the id.
     *
     * @param string $id The id of the form that the token is for.
     * @return string The token, to be sent with the form.
     */
    public function getToken(string $id): string;

    /**
     * Checks a token that came with a form.
     *
     * @param string $id The id of the form that the token is for.
     * @param string $token The token that came with the form.
     * @return bool Whether the token is the one of the id for this user.
     */
    public function isValid(string $id, string $token): bool;
}
