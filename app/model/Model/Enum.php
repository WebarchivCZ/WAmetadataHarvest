<?php

namespace Model;

abstract class Enum extends Base {

    /**
     * Model value name
     * @var string 
     */
    protected $value = 'value';

    public function getId($value)
    {
        $row = $this->table->select($this->id)->where(array($this->value => $value))->limit(1)->fetch();
        return $row ? $row->id : FALSE;
    }

    public function getValueId($value)
    {
        $id = $this->getId($value);
        if (!$id) {
            $item = $this->table->insert(array(
                $this->value => $value,
            ));
            $id = $item->id;
        }
        return $id;
    }

    public function getById($id)
    {
        $row = parent::getById($id);
        return $row ? $row->value : FALSE;
    }

    public function getByIds($ids)
    {
        $rows = parent::getByIds($ids);
        $result = array();
        foreach ($rows as $id => $row) {
            $result[$id] = $row->value;
        }
        return $result;
    }

    public function getAll()
    {
        return $this->table->fetchPairs($this->id, $this->value);
    }

    public function getByValues($values)
    {
        $result = array();
        foreach ($values as $value) {
            $result[$this->getValueId($value)] = $value;
        }
        return $result;
    }

}
