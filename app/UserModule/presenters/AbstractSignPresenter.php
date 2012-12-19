<?php

namespace UserModule;

abstract class AbstractSignPresenter extends \BasePresenter {


	/** @persistent */
	public $backlink = '';


	public function getAfterSignIn()
	{
		return $this->context->params['user']['after']['sign-in'];
	}


	public function getAfterSignUp()
	{
		return $this->context->params['user']['after']['sign-up'];
	}


	public function getAfterSignOut()
	{
		return $this->context->params['user']['after']['sign-out'];
	}

}