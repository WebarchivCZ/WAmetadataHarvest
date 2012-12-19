<?php

namespace Model;

abstract class Helper {
    // TODO passing database to helpers is quite silly
    /**
     * @var Database
     */
    protected $database;
    /**
     * @param Database $database 
     */
    public function __construct($database)
    {
        $this->database = $database;
    }

}
