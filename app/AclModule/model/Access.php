<?php

abstract class Access extends Nette\Object implements IAccess
{

    protected $model;

    protected $user;

    protected $resource;

    function __construct($model, $user, $resource = NULL)
    {
        $this->model = $model;
        $this->user = $user;
        $this->resource = $resource;
    }

    function can($action)
    {
        if (method_exists($this, $action)) {
            return call_user_func(array($this, $action));
        } else {
            throw new Access\UnsupportedAction("Unsupported action '$action'");
        }
    }

    protected function isLoggedIn()
    {
        return $this->user->isLoggedIn();
    }

    static private $userRow = array();

    protected function getUserRow($user = NULL)
    {
        if ($user !== NULL) {
            return $user;
        }

        if (!$this->user->isLoggedIn()) {
            return NULL;
        }

        $id = $this->user->getId();
        if (!isset(self::$userRow[$id])) {
            self::$userRow[$id] = $this->model->model('user')->getById($id);
        }
        return self::$userRow[$id];
    }

    function is(/* ($groups|$group[, $group ..], $user = NULL */)
    {
        $parameters = func_get_args();
        array_unshift($parameters, $this->resource);
        return call_user_func_array(array($this->model, 'is'), $parameters);
    }

    protected function model($name)
    {
        return $this->model->model($name, $this->model);
    }

}
