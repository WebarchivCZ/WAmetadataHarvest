<?php

namespace Model;

use Nette;

class Action extends Nette\Object
{

    const
        DISPLAY = 'display',
        CREATE = 'create',
        REPLACE = 'replace',
        UPDATE = 'update',
        DELETE = 'delete';

    const
        SUPPORT_NONE = 0,
        SUPPORT_DISPLAY = 1,
        SUPPORT_CREATE = 2,
        SUPPORT_REPLACE = 4,
        SUPPORT_UPDATE = 8,
        SUPPORT_DELETE = 16,
        SUPPORT_ALL = 31;

    private $model;

    private $user;

    public function __construct($model, $user)
    {
        $this->model = $model;
        $this->user = $user;
    }

    public function __call($name, $parameters)
    {
        $action = strtolower($name);
        if (in_array($action, array(self::DISPLAY, self::CREATE, self::REPLACE, self::UPDATE, self::DELETE))) {
            $count = count($parameters);
            if ($count > 2) {
                throw new Action/RuntimeException("Maximum of 2 parameters is allowed");
            }
            $resource = $action == self::CREATE ? NULL : $parameters[0];
            $this->checkSupport($action);
            $this->checkPermission($action, $resource);
            $model = $this->model;
            if ($action != self::DELETE && $action != self::DISPLAY) {
                $key = (int) ($action != self::CREATE);
                $data = $parameters[$key];
                $data = $model->filter($data) + $model->defaults($action);
                $model->validate($action, $data);
                $data = $model->process($data);
                $parameters[$key] = $data;
            }
            return call_user_func_array(array($this->model, $action), $parameters);
        }
        return parent::__call($name, $parameters);
    }

    private $supportMap = array(
        self::DISPLAY => self::SUPPORT_DISPLAY,
        self::CREATE => self::SUPPORT_CREATE,
        self::REPLACE => self::SUPPORT_REPLACE,
        self::UPDATE => self::SUPPORT_UPDATE,
        self::DELETE => self::SUPPORT_DELETE,
    );

    public function isSupported($action, $resource = NULL)
    {
        return (bool) ($this->model->getSupportedActions($resource) & $this->supportMap[$action]);
    }

    public function checkSupport($action, $resource = NULL)
    {
        if (!$this->isSupported($action, $resource)) {
            throw new Action\Unsupported("Unsupported action '$action'");
        }
    }

    public function isPermitted($action, $resource = NULL)
    {
        $permissions = $this->model->getActionPermissions($action, $resource);
        return TRUE;
    }

    public function checkPermission($action, $resource = NULL)
    {
        if (!$this->isPermitted($action, $resource)) {
            throw new Action\NotPermitted("Action '$action' is not permitted");
        }
    }

}
