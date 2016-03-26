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

/**
 * Created by PhpStorm.
 * User: stardisblue
 * Date: 26/03/16
 * Time: 16:40
 */

namespace rave\core;


use rave\core\database\driver\GenericDriver;

class Database
{
    private static $database_object;

    public static function set(GenericDriver $generic_driver)
    {
        self::$database_object = $generic_driver;
    }

    /**
     * @return GenericDriver
     */
    public static function get()
    {
        return self::$database_object;
    }
}