<?php

namespace UserModule\SettingsModule;

use Nette\Forms\Form;

final class EmailPresenter extends BasePresenter {


	public function beforeRender()
	{
		parent::beforeRender();
		$this->addBreadcrumb(array(
			'__label' => 'Change Email',
			'link' => 'this',
		));
	}


	public function createComponentEmailForm()
	{
		$form = $this->formFactory->createForm();
		$user = $this->getCurrentUser();
		$form->addGroup('Change Email');
		$form->addText('email', 'Email')
			->addRule(Form::EMAIL, 'You must enter valid email')
			->addRule(callback($this, 'isEmailAvailable'), 'Email is taken please pick another.')
			->setValue($user->email);
		$form->addSubmit('save', 'Change')
			->getControlPrototype()->class('button-submit');
		$form->onSuccess[] = callback($this, 'emailFormSucceded');
		return $form;
	}


	public function emailFormSucceded($form)
	{
		$values = $form->getValues();
		$user = $this->getCurrentUser();
		if ($values['email'] !== $user->email) {
			$user->email = $values['email'];
			$user->update();
			$this->flashMessage('Your email was set to %s.', array($user->email));
		}
		$this->redirect('this');
	}


	public function isEmailAvailable($item)
	{
		$user = $this->userModel->getByEmail($item->value);
		return !$user || $user->id == $this->getUser()->getId();
	}

}
