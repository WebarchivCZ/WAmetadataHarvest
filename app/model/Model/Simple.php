<?php

namespace Model;

use Nette;

abstract class Simple extends Nette\Object {

    protected $manager;

    protected $connection;

    protected $user;

    public function __construct(Manager $manager, Nette\Database\Connection $connection, Nette\Security\User $user)
    {
        $this->manager = $manager;
        $this->connection = $connection;
        $this->user = $user;

        $this->setup();

        if ($this->setupFinished === FALSE) {
            throw new Exception(get_class($this) . ' setup must call parent setup');
        }
    }

    private $setupFinished = FALSE;
    /**
     * Use to setup your model after construction
     */
    protected function setup()
    {
        $this->setupFinished = TRUE;
    }

    /**
     * Get model
     * @param string $name
     * @return Model\Base
     */
    public function model($name)
    {
        return $this->manager->model($name, $this);
    }

    /**
     * @return Nette\Database\Connection
     */
    public function getDatabase()
    {
        return $this->connection;
    }

    private $access;

    private $accessClass;

    public function getAccess($resource = NULL)
    {
        if ($this->accessClass === NULL) {
            if (class_exists(get_class($this) . '\\Access')) {
                $this->accessClass = get_class($this) . '\\Access';
            } else {
                throw new Exception('Model ' . get_class($this) . ' has no access class');
            }
        }
        if ($resource === NULL) {
            if ($this->access === NULL) {
                $this->access = new $this->accessClass($this, $this->user);
            }
            return $this->access;
        }
        return $this->access = new $this->accessClass($this, $this->user, $resource);
    }

    public function __call($name, $parameters)
    {
        if ('can' == substr($name, 0, 3)) {
            $resource = array_shift($parameters);
            return call_user_func_array(array($this->getAccess($resource), lcfirst(substr($name, 3))), $parameters);
        }
        return parent::__call($name, $parameters);
    }

}
