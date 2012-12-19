<?php

namespace UserModule\AdminModule;

use Nette,
	Nette\Forms\Form as NForm,
	Nette\Utils\Html as NHtml;

final class CreateOrEditPresenter extends BasePresenter {


	/** @persistent */
	public $id;

	private $editedUser;


	public function actionDefault()
	{
		if (NULL !== $this->id) {
			$this->editedUser = $this->load('user', $this->id);
		}
		if ($this->editedUser) {
			$this['form']->setDefaults($this->editedUser->toArray());
		}
	}


	public function renderDefault()
	{
		$this->template->editedUser = $this->editedUser;
	}


	public function createComponentForm()
	{
		$form = $this->formFactory->createForm();

		$usernameMin = 1;
		$usernameMax = 64;
		$passwordMin = 6;
		$form = $this->context->formFactory->createForm();
		$username = $form->addText('username', 'Username');
		if ($this->editedUser) {
			$username->getControlPrototype()->readonly = 'readonly';
		} else {
			$username
				->addRule(NForm::FILLED, 'You must enter username.')
				->addRule(NForm::MAX_LENGTH, 'Maximal length of username is %d.', $usernameMax)
				->addRule(NForm::REGEXP, 'Only a-z, 0-9 and _ are allowed.',  '/^[a-z0-9_]+$/')
				->addRule(callback($this, 'isUsernameAvailable'), 'Username is taken please pick another.')
				->setOption('description', NHtml::el()->setText($this->translate('%d up to %d characters of a-z, 0-9 and _.', $usernameMin, $usernameMax)));
		}

		$form->addRadioList('group', 'Group', array(
			'user' => 'group-user',
			'admin' => 'group-admin'
		))->setDefaultValue('user');

		$form->addText('email', 'Email')
			->addRule(NForm::EMAIL, 'You must enter email.')
			->addRule(callback($this, 'isEmailAvailable'), 'Email is taken please pick another.');
		;
		$password = $form->addPassword('password', 'Password');
		$append = $this->editedUser ? $password->addCondition(NForm::FILLED) : $password;
		$append->addRule(NForm::MIN_LENGTH, 'Password must be at least %s characters long.', $passwordMin);
		if (!$this->editedUser) {
			$password->addRule(NForm::FILLED, 'You must enter password.');
		}
		$password->setOption('description', NHtml::el()->setText($this->translate('At least %d characters long.', $passwordMin)));

		if ($this->editedUser) {
			$form->addSubmit('save', 'Save')
				->getControlPrototype()->class('btn-primary');
		} else {
			$form->addSubmit('save', 'Create')
				->getControlPrototype()->class('btn-success');
		}

		$form->onSuccess[] = $this->onSuccess;
		return $form;
	}


	public function onSuccess($form)
	{
		if ($this->editedUser) {
			$values = $form->getValues();
			$this->editedUser->email = $values['email'];
			$this->editedUser->group = $values['group'];
			if ($values['password']) {
				$this->userModel->setPassword($this->editedUser, $values['password']);
			}
			$this->editedUser->update();
			$this->flashMessage('User was successfully updated.', 'success');
		} else {
			$values = $form->getValues();
			$data = $this->userModel->getDataFromRegistration($values);
			$data['group'] = $values['group'];
			$this->editedUser = $this->userModel->create($data);
			$this->flashMessage('User was successfully created.', 'success');
		}
		$this->redirect('Dashboard:');
	}


	public function isUsernameAvailable($item)
	{
		return !(bool) $this->userModel->getByUsername($item->value);
	}


	public function isEmailAvailable($item)
	{
		$user = $this->userModel->getByEmail($item->value);
		return !$user || $this->editedUser && $user->id == $this->editedUser->id;
	}


	private $formFactory;

	public function injectFormFactory(\FormFactory $formFactory)
	{
		$this->formFactory = $formFactory;
	}

}
