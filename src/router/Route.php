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

namespace rave\core\router;

use rave\core\Controller;
use rave\core\Error;

class Route
{
    const MATCH_INDEX = 1;
    const METHOD_INDEX = 1;
    const CONTROLLER_INDEX = 0;
    private $path;
    private $callable;
    private $matches = [];
    private $parameters = [];

    public function __construct($path, $callable)
    {
        $this->path = trim($path, '/');
        $this->callable = $callable;
    }

    /**
     * Checks if there is variables
     *
     * @param string $url
     * @return bool
     */
    public function match($url)
    {
        $url = trim($url, '/');
        $path = preg_replace_callback('#:([\w]+)#', [$this, 'parameterMatch'], $this->path);

        if (!preg_match('#^' . $path . '$#i', $url, $matches)) {
            return false;
        }

        array_shift($matches);

        $this->matches = $matches;

        return true;
    }

    /**
     * Calls the Controller and the function
     *
     * @return mixed the result of the controller function
     */
    public function call()
    {
        if (is_array($this->callable)) {
            $namespace = 'techweb\\app\\controller\\';

            $method = reset($this->callable);
            $class = $namespace . key($this->callable);

            if (!class_exists($class)) {
                Error::create('Router: class "' . $class . '" does not exists', 500);
            }

            /** @var Controller $controller */
            $controller = new $class();

            if (!is_callable([$controller, $method])) {
                Error::create('Router: method "' . $method . '" does not exists', 500);
            }

            $controller->beforeCall($method);
            $result = call_user_func_array([$controller, $method], $this->matches);
            $controller->afterCall($method);

            return $result;

        } else {
            if (!is_callable($this->callable)) {
                Error::create('Router: method does not exists', 500);
            }

            return call_user_func_array($this->callable, $this->matches);
        }
    }

    /**
     * Add variables and their conditions
     *
     * @param string $parameter
     * @param string $regex
     * @return Route
     */
    public function with($parameter, $regex)
    {
        $this->parameters[$parameter] = str_replace('(', '(?:', $regex);

        return $this;
    }

    /**
     * Returns the url of the Route with the given parameters
     *
     * @param array $parameters
     * @return string
     */
    public function getUrl(array $parameters)
    {
        $path = $this->path;

        foreach ($parameters as $key => $value) {
            $path = str_replace(':' . $key, $value, $path);
        }

        return $path;
    }

    /**
     * Checks if the parameter Match
     *
     * @param array $match
     * @return string
     * @see match()
     */
    private function parameterMatch(array $match)
    {
        if (isset($this->parameters[$match[self::MATCH_INDEX]])) {
            return '(' . $this->parameters[$match[self::MATCH_INDEX]] . ')';
        }

        return '([^/]+)';
    }

}