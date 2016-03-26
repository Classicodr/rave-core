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

namespace rave\core;

use rave\core\exception\UnknownPropertyException;

class Config
{
    private static $_container = [];

    /**
     * Adds an array to the config
     *
     * @param array $options
     */
    public static function addArray(array $options)
    {
        self::$_container = array_merge(self::$_container, $options);
    }

    /**
     * Adds a $key => $value to the config
     *
     * @param $key string
     * @param $value mixed
     */
    public static function add($key, $value)
    {
        self::$_container[$key] = $value;
    }

    /**
     * Returns the datasource
     *
     * @param $source_name string name of the datasource
     * @return array|string Properties of the datasource
     */
    public static function getDatasources($source_name)
    {
        return self::get('datasources')[$source_name];
    }

    /**
     * Returns the configurated value, null otherwise
     *
     * @param $option string configuration to get
     * @return mixed|null
     * @throws UnknownPropertyException if $option is not a string
     */
    public static function get($option)
    {
        if (!is_string($option)) {
            throw new UnknownPropertyException('the key is not as string');
        }

        return isset(self::$_container[$option]) ? self::$_container[$option] : null;
    }

    /**
     * Returns the default error file
     *
     * @param $error_type string name of error
     * @return string path to the error file
     */
    public static function getError($error_type)
    {
        return self::get('error')[$error_type];
    }

}