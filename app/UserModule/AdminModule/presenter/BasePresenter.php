<?php

namespace UserModule\AdminModule;

abstract class BasePresenter extends \AdminModule\BasePresenter {


	protected $userModel;

	public function injectUserModel(\User $userModel)
	{
		$this->userModel = $userModel;
	}

}
