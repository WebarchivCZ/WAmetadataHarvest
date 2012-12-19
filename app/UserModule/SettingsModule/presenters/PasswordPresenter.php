<?php

namespace UserModule\SettingsModule;

use Nette\Forms\Form,
	Nette\Utils\Html;

final class PasswordPresenter extends BasePresenter {


	/**
	 * Determines between setting password or changing it
	 * @var bool
	 */
	protected $changing;

	protected function startup()
	{
		parent::startup();
		$this->changing = (bool) $this->userModel->hasPassword($this->getCurrentUser());
	}


	public function beforeRender()
	{
		parent::beforeRender();
		$this->addBreadcrumb(array(
			'__label' => $this->changing ? 'Change Password' : 'Set Password',
			'link' => 'this',
		));
	}


	public function createComponentPasswordForm()
	{
		$passwordMin = 6;
		$form = $this->formFactory->createForm();
		$form->addGroup($this->changing ? 'Change Password' : 'Set Password');
		if ($this->changing) {
			$form->addPassword('old_password', 'Old Password')
				->addRule(Form::FILLED, 'You must enter your current password')
				->addRule(callback($this, 'checkPassword'), 'You must enter your current password');
		}
		$form->addPassword('password', 'Password')
			->addRule(Form::MIN_LENGTH, 'Password must be at least %s characters long', $passwordMin)
			->addRule(Form::FILLED, 'You must enter new password')
			->setOption('description', Html::el()->setText($this->translate('At least %d characters long.', $passwordMin)));
		$form->addPassword('password_confirmation', 'Re-Enter Password')
			->addRule(Form::EQUAL, 'Passwords don\'t match.', $form['password']);
		$form->addSubmit('save', $this->changing ? 'Change' : 'Set')
			->getControlPrototype()->class('button-submit');
		$form->onSuccess[] = callback($this, 'newPasswordFormSuccessed');
		return $form;
	}


	public function newPasswordFormSuccessed($form)
	{
		$values = $form->getValues();
		$this->userModel->setPassword($this->getCurrentUser(), $values['password']);
		$this->flashMessage($this->changing ? 'Your password was changed.' : 'Your password was set.');
		$this->redirect('this');
	}


	public function checkPassword($item)
	{
		return $this->userModel->isPasswordMatching($this->getCurrentUser(), $item->value);
	}

}
