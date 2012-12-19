<?php

namespace UserModule;

abstract class BasePresenter extends \BasePresenter {

	protected function authorize($roles = array())
	{
		try {
			parent::authorize($roles);
		} catch (\Nette\Application\BadRequestException $e) {
			if ($e->getCode() == \Nette\Http\IResponse::S401_UNAUTHORIZED) {
				$this->flashMessage($e->getMessage());
				$backlink = $this->storeRequest();
				$this->redirect(':User:Sign:in', array('backlink' => $backlink));
			} else {
				throw $e;
			}
		}
	}

}
