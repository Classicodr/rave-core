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

use rave\core\exception\IOException;

abstract class Controller
{
    const LOG_NOTICE = 0;
    const LOG_WARNING = 1;
    const LOG_FATAL_ERROR = 2;

    private static $currentLogFile;
    protected $data = [];
    protected $query;
    protected $layout = false;

    public function __construct()
    {
    }

    /**
     * Trigerred before controller Call (MVC)
     *
     * @param string $method
     */
    public function beforeCall($method)
    {
    }

    /**
     * Trigerred after Controller Call (MVC)
     *
     * @param string $method
     */
    public function afterCall($method)
    {
        $method();
    }

    /**
     * Load the view
     *
     * @param string $view
     * @param array $data
     */
    protected function loadView($view, array $data = [])
    {
        if (!empty($data) || !empty($this->data)) {
            extract(array_merge($this->data, $data));
        }

        $controller = explode('\\', static::class);

        $file = APP . 'view/' . strtolower(end($controller)) . '/' . $view . '.php';

        ob_start();

        if (file_exists($file)) {
            include_once $file;
        } else {
            Error::create('Error while loading view', 404);
        }

        $content = ob_get_clean();

        if (!$this->layout) {
            echo $content;
        } else {
            include_once APP . 'view/layout/' . $this->layout . '.php';
        }
    }

    /**
     * Redirect to page
     *
     * @param string $page
     */
    protected function redirect($page = '')
    {
        header('Location: ' . WEB_ROOT . '/' . $page);
        exit;
    }

    /**
     * Writes the message in the logs
     *
     * @param string $message
     * @param int $priority
     */
    protected function log($message, $priority = self::LOG_NOTICE)
    {
        $log = date('H:i:s');

        switch ($priority) {
            case self::LOG_NOTICE:
                $log .= ' : ' . $message;
                break;
            case self::LOG_WARNING:
                $log .= ' WARNING : ' . $message;
                break;
            case self::LOG_FATAL_ERROR:
                $log .= ' FATAL ERROR : ' . $message;
        }

        try {
            $this->writeLog($log);
        } catch (IOException $ioException) {
            Error::create($ioException->getMessage(), 500);
        }
    }

    /**
     * Write a message in log file
     *
     * @param string $message
     * @throws IOException
     */
    private function writeLog($message)
    {
        if (isset(self::$currentLogFile)) {
            file_put_contents(self::$currentLogFile, $message . PHP_EOL, FILE_APPEND);
        } else {
            if (file_exists(ROOT . '/log') === false) {
                mkdir(ROOT . '/log');
            }

            self::$currentLogFile = ROOT . '/log/' . date('d-m-Y') . '.log';

            if (!file_exists(self::$currentLogFile) && !fopen(self::$currentLogFile, 'a')) {
                throw new IOException('Unable to create log file');
            }

            $this->writeLog($message);
        }
    }

    /**
     * Sets the default layout of the Controller
     *
     * @param string $layout
     * @param array $data
     */
    protected function setLayout($layout, array $data = [])
    {
        $this->data = $data;
        $this->layout = file_exists(APP . 'view/layout/' . $layout . '.php') ? $layout : false;
    }

}