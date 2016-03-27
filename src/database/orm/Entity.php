<?php
/**
 * Rave <https://github.com/Classicodr/rave-core>
 * Copyright (C) 2016 Rave Team
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace rave\core\database\orm;

use rave\core\exception\UnknownPropertyException;

/**
 * Class Entity
 * @package rave\core
 */
abstract class Entity
{
    protected $options = [];

    /**
     * Entity constructor.
     *
     * ```php
     * $properties = [
     *      "property" => "default value",
     *      "id", ...]
     * ```
     *
     * @param array $properties
     * @param array $options [optional]
     *
     * additionnals options
     */
    public function __construct(array $properties, array $options = [])
    {
        foreach ($properties as $property => $value) {
            $this->$property = $value;
        }

        $this->options = $options;
    }

    /**
     * Set a property only if it was declared in the constructor
     *
     * @param array $properties , ["property" => "default value", ...]
     *
     * @throws UnknownPropertyException
     */
    public function set(array $properties)
    {
        foreach ($properties as $key => $value) {
            if (isset($this->$key)) {
                $this->$key = $value;
            } else {
                throw new UnknownPropertyException('No attribute ' . $key . ' in this Entity');
            }
        }
    }

    /**
     * Return the reference of the property
     *
     * @param string $properties
     *
     * @return mixed
     * @throws UnknownPropertyException
     */
    public function &get($properties)
    {
        if (isset($this->$properties)) {
            return $this->$properties;
        } else {
            throw new UnknownPropertyException("No matching property");
        }
    }

    /**
     * Returns the set of primary keys
     * @return string|array|null
     */
    public function getPrimaryKeys()
    {
        return isset($this->options['primary']) ? $this->options['primary'] : null;
    }

    /**
     * Return the value of the wanted option
     * return null if the option doesn't exist
     *
     * @param string $option
     *
     * @return null|mixed
     */
    public function options($option)
    {
        return isset($this->options[$option]) ? $this->options[$option] : null;
    }
}