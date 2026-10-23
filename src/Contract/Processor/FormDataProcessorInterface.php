<?php

declare(strict_types=1);

/**
 * Derafu: Form - JSON Forms inspired form generation library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Contract\Processor;

use Derafu\Form\Contract\FormInterface;

/**
 * Interface for form data processing services.
 *
 * This interface defines the contract for services that process form data
 * using schema definitions and data processor rules.
 */
interface FormDataProcessorInterface
{
    /**
     * Process form data using form definition.
     *
     * This method:
     *
     *   1. Maps the form to data processor rules.
     *   2. Processes each field through the rules (cast, transform, sanitize,
     *      validate).
     *   3. Returns a result with processed data, errors, and validation status.
     *
     * @param FormInterface $form The form definition.
     * @param array<array-key, mixed> $data The form data to process. If not
     * provided, the data will be retrieved from the request.
     * @return ProcessResultInterface The processing result with data, errors
     * and validation status.
     */
    public function process(
        FormInterface $form,
        array $data = []
    ): ProcessResultInterface;
}
