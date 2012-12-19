<?php
namespace Model;

use Nette\Database\Table\ActiveRow;

class RestWrapper {

	private $model;

	public function __construct($model)
	{
		$this->model = $model;
	}

	public function action($action /*[, $resource][, $data ]*/)
	{
		$params = func_get_args();
		switch ($action) {
			case Action::DISPLAY:
				return $this->display($params[1]);
			case Action::CREATE:
				return $this->create($params[1]);
			case Action::REPLACE:
				return $this->replace($params[1], $params[2]);
			case Action::UPDATE:
				return $this->update($params[1], $params[2]);
			case Action::DELETE:
				return $this->delete($params[1]);
			default:
				throw Exception('Unsupported action.');
		}
	}

    public function display($resource)
    {
        $action = Action::DISPLAY;
        $this->model->isSupported($action, $resource);
        $this->hasPermission($action, $resource);
        return $resource;
    }

    public function create($data)
    {
        $action = Action::CREATE;
        $this->model->isSupported($action);
        $this->hasPermission($action);
        $data = $this->model->filter($data) + $this->model->defaults($action);
        $this->model->validate($action, $data);
        $data = $this->model->process($action, $data);
        $resource = $this->model->create($data);
        return $resource;
    }

    public function replace($resource, $data)
    {
        $action = Action::REPLACE;
        $this->model->isSupported($action, $resource);
        $this->hasPermission($action, $resource);
        $data = $this->model->filter($data) + $this->model->defaults($action);
        $this->model->validate($action, $data);
        $data = $this->model->process($action, $data);
        $resource = $this->model->replace($resource, $data);
        return $resource;
    }

    public function update($resource, $data)
    {
        $action = Action::UPDATE;
        $this->model->isSupported($action, $resource);
        $this->hasPermission($action, $resource);
        $data = $this->model->filter($data) + $this->model->defaults($action);
        $this->model->validate($action, $data);
        $data = $this->model->process($action, $data);
        $this->model->update($resource, $data);
        return $resource;
    }

    public function delete($resource)
    {
        $action = Action::DELETE;
        $this->model->isSupported($action, $resource);
        $this->hasPermission($action, $resource);
        $this->model->delete($resource);
        return $resource;
    }


    protected function hasPermission($action, $resource = NULL)
    {
        return FALSE;
    }

    /**
     * Add additional filters and stuff
     * @resource object|Nette\Database\Table\Selection
     */
    protected function process($resource)
    {
        return $resource;
    }

    protected function output($resource = NULL)
    {
        $resource = $resource ?: $this->resource();
        if ($resource instanceof ActiveRow) {
            $output = $this->model->output($resource);
        } else {
            $output = array();
            foreach ($resource as $resource) {
                $output[] = $this->model->output($resource);
            }
        }
        return $output;
    }

}
