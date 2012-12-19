<?php

namespace UserModule\AdminModule;

final class DashboardPresenter extends \AdminModule\BasePresenter {


	public function beforeRender()
	{
		parent::beforeRender();

		$this->addBreadcrumb(array(
			'__label' => 'Users',
			'link' => 'this',
		));
	}


	public function renderDefault()
	{
		$this->template->users = $this->model('user')->getAll()->order('username');
	}

}
