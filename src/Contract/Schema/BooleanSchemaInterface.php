<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Contract\Schema;

/**
 * Represents a boolean property within a Schema.
 *
 * Boolean type doesn't have any specific validation methods beyond what is
 * already provided by PropertySchemaInterface, but having a dedicated interface
 * allows for type safety and future expansion.
 */
interface BooleanSchemaInterface extends PropertySchemaInterface
{
}
