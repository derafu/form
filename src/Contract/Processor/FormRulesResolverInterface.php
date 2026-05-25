<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Contract\Processor;

use Derafu\Form\Contract\FormInterface;

/**
 * Resolves the complete, merged processing rules for every field in a form.
 *
 * A resolver combines three sources:
 *   1. Rules derived from the JSON Schema property definitions (type, format,
 *      constraints such as minLength, minimum, enum …).
 *   2. Rules derived from the UI schema control options (e.g. 'image' control
 *      adds image validation; 'editor' control adds strip_tags transform).
 *   3. Explicit rules from the form's 'rules' section (via FormInterface::getRules()),
 *      merged on top of the derived ones:
 *      - cast:      explicit replaces derived (mutually exclusive).
 *      - sanitize / transform / validate: explicit appended after derived.
 *      - required:  always placed first in validate, deduplicated.
 *
 * resolve() fills the form's own FormRulesInterface instance in place.
 * After calling it, $form->getRules() contains the complete resolved set.
 */
interface FormRulesResolverInterface
{
    /**
     * Resolves the complete processing rules for all form fields and stores
     * them in the form's own FormRulesInterface instance via fill().
     *
     * After this call, $form->getRules() reflects the fully merged rules.
     * No new FormRulesInterface object is created.
     *
     * @param FormInterface $form The form whose rules should be resolved.
     */
    public function resolve(FormInterface $form): void;
}
