<?php

namespace UserModule\AdminModule;

use Nette,
	Nette\Forms\Form as NForm,
	Nette\Utils\Html as NHtml;

final class DeletePresenter extends BasePresenter {


	/** @persistent */
	public $id;

	private $userToDelete;


	public function actionDefault()
	{
		$this->userToDelete = $this->load('user', $this->id);
		if ($this->userToDelete->getPrimary() === $this->getUser()->getId()) {
			$this->flashMessage('You cannot delete yourself!', 'error');
			$this->redirect('Dashboard:');
		}
	}


	public function renderDefault()
	{
		$this->template->userToDelete = $this->userToDelete;
	}


	public function createComponentForm()
	{
		$form = $this->formFactory->createForm();
		$form->addProtection();
		$form->addSubmit('delete', 'Delete')
			->getControlPrototype()->class('btn-danger');
		$form->onSuccess[] = $this->onSuccess;
		return $form;
	}


	public function onSuccess($form)
	{
		$this->userToDelete->delete();
		$this->flashMessage('User was successfully deleted.', 'success');
		$this->redirect('Dashboard:');
	}


	private $formFactory;

	public function injectFormFactory(\FormFactory $formFactory)
	{
		$this->formFactory = $formFactory;
	}

}
