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
 * Represents an integer property within a Schema.
 */
interface IntegerSchemaInterface extends PropertySchemaInterface
{
    /**
     * Gets the exclusive maximum that the property value can reach.
     *
     * @return int|null
     * @link https://www.learnjsonschema.com/2020-12/validation/exclusivemaximum/
     */
    public function getExclusiveMaximum(): int|null;

    /**
     * Sets the exclusive maximum that the property value can reach.
     *
     * @param int $exclusiveMaximum
     * @return static The current instance.
     */
    public function setExclusiveMaximum(int $exclusiveMaximum): static;

    /**
     * Gets the exclusive minimum that the property value can reach.
     *
     * @return int|null
     * @link https://www.learnjsonschema.com/2020-12/validation/exclusiveminimum/
     */
    public function getExclusiveMinimum(): int|null;

    /**
     * Sets the exclusive minimum that the property value can reach.
     *
     * @param int $exclusiveMinimum
     * @return static The current instance.
     */
    public function setExclusiveMinimum(int $exclusiveMinimum): static;

    /**
     * Gets the maximum that the property value can reach.
     *
     * @return int|null
     * @link https://www.learnjsonschema.com/2020-12/validation/maximum/
     */
    public function getMaximum(): int|null;

    /**
     * Sets the maximum that the property value can reach.
     *
     * @param int $maximum
     * @return static The current instance.
     */
    public function setMaximum(int $maximum): static;

    /**
     * Gets the minimum that the property value can reach.
     *
     * @return int|null
     * @link https://www.learnjsonschema.com/2020-12/validation/minimum/
     */
    public function getMinimum(): int|null;

    /**
     * Sets the minimum that the property value can reach.
     *
     * @param int $minimum
     * @return static The current instance.
     */
    public function setMinimum(int $minimum): static;

    /**
     * Gets the divisor of the value property that results in an integer value.
     *
     * @return int|null
     * @link https://www.learnjsonschema.com/2020-12/validation/multipleof/
     */
    public function getMultipleOf(): int|null;

    /**
     * Sets the divisor of the value property that results in an integer value.
     *
     * @param int $multipleOf
     * @return static The current instance.
     */
    public function setMultipleOf(int $multipleOf): static;
}
