<?php

use Nette\Http;

/**
 * Base presenter for all application presenters.
 */
abstract class CommonPresenter extends Nette\Application\UI\Presenter
{

	public function createTemplate($class = NULL)
	{
		return parent::createTemplate($class)
			;//->setCacheStorage(new Nette\Caching\Storages\DevNullStorage);
	}

	public function templatePrepareFilters($template)
	{
		$latte = new \Nette\Latte\Engine;
		Mishak\WebResourceManagement\Latte\Macros::install($latte->compiler);
		$template->registerFilter($latte);
		return $template;
	}

	public function model($name)
	{
		return $this->context->modelManager->model($name);
	}

	protected function load($model, $id, $roles = array())
	{
		$key = 'id';
		if (is_array($model)) {
			list($model, $key) = $model;
		}
		if (is_string($model)) {
			$model = $this->model($model);
		}
		$resource = $model->{'getBy' . ucfirst($key)}($id);
		if (!$resource) {
			$this->error('Not Found');
		}
		if ($roles) {
			$this->check($model, $resource, $roles);
		}
		return $resource;
	}

	protected function check($model, $resource, $roles)
	{
		if (is_string($model)) {
			$model = $this->model($model);
		}
		if (is_string($roles)) {
			$roles = array($roles);
		}
		if (!$model->is($resource, $roles, $this->getUser())) {
			$this->error('Forbidden', Http\IResponse::S403_FORBIDDEN);
		}
	}

	protected function authorize($roles = array())
	{
		$user = $this->getUser();
		if (!$user->isLoggedIn()) {
			$this->error('You must be logged in to proceed.', Http\IResponse::S401_UNAUTHORIZED);
		}
		if (is_string($roles)) {
			$roles = array($roles);
		}
		if ($roles && !array_intersect($user->getRoles(), $roles)) {
			$this->error('Forbidden', Http\IResponse::S403_FORBIDDEN);
		}
	}

}
