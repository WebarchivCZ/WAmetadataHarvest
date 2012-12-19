<?php

namespace Resource;

use Model;

class Type extends Model\Base
{

    private $cacheByName = array();

    function getByName($name)
    {
        if (!isset($this->cacheByName[$name])) {
            $type = $this->getTable()
                ->where('name', $name)
                ->limit(1)
                ->fetch();
            if (!$type) {
                $type = $this->getTable()->insert(array('name' => $name));
            }
            $this->cacheByName[$name] = $type;
        }
        return $this->cacheByName[$name];
    }

}
