<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 01.03.17
 * Time: 02:48
 */

namespace Cilex\Components;


class Logger {

    private $file;

    function __construct($file) {

        if(!file_exists($file)) {
            touch($file);
        }

        if(!is_writable($file))  {
            throw new \Exception("Access to log deny.");
        }

        $this->file = $file;

    }


    public function log($data) {

        file_put_contents($this->file, date("d-m-Y H:i:s")." INFO ". $data.PHP_EOL, FILE_APPEND);
    }
}