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

use rave\core\exception\RouterException;

class Router
{
    private $url;

    private $routes = [];
    private $namedRoutes = [];

    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * PUT route
     *
     * @param string $path
     * @param $callable
     * @param string|null $name
     * @return Route
     */
    public function put($path, $callable, $name = null)
    {
        return $this->add('PUT', $path, $callable, $name);
    }

    /**
     * ADD route
     *
     * @param string $method
     * @param string $path
     * @param $callable
     * @param $name
     * @return Route
     */
    private function add($method, $path, $callable, $name)
    {
        $route = new Route($path, $callable);
        $this->routes[$method][] = $route;

        if (is_string($callable) && $name === null) {
            $name = $callable;
        }

        if ($name) {
            $this->namedRoutes[$name] = $route;
        }

        return $route;
    }

    public function get($path, $callable, $name = null)
    {
        return $this->add('GET', $path, $callable, $name);
    }

    /**
     * POST route
     *
     * @param string $path
     * @param $callable
     * @param string|null $name
     * @return Route
     */
    public function post($path, $callable, $name = null)
    {
        return $this->add('POST', $path, $callable, $name);
    }

    /**
     * DELETE route
     *
     * @param string $path
     * @param $callable
     * @param string|null $name
     * @return Route
     */
    public function delete($path, $callable, $name = null)
    {
        return $this->add('DELETE', $path, $callable, $name);
    }

    /**
     * Runs the router
     *
     * @return mixed
     * @throws RouterException
     */
    public function run()
    {
        if (!isset($this->routes[$_SERVER['REQUEST_METHOD']])) {
            throw new RouterException('REQUEST_METHOD does not exists');
        }

        foreach ($this->routes[$_SERVER['REQUEST_METHOD']] as $route) {
            /** @var Route $route */
            if ($route->match($this->url)) {
                return $route->call();
            }
        }

        throw new RouterException('No matching route');
    }

    /**
     * Get the url of the given named route
     *
     * @param string $name
     * @param array $parameters
     * @return string
     * @throws RouterException
     */
    public function url($name, $parameters = [])
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new RouterException('No route matching this name');
        }

        return $this->namedRoutes[$name]->getUrl($parameters);
    }

}