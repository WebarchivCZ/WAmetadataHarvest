<?php

namespace UserModule;

use Nette,
	Nette\Forms\Form as NForm;

final class SetPasswordPresenter extends \BasePresenter {

	/** @persistent */
	public $ticket;

	public function actionDefault()
	{
		if (!$this->getUser()->canRecoverAccount()) {
			throw new Nette\Application\BadRequestException('Account recovery is not allowed.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
		$this->getTicket();
	}


	public function beforeRender()
	{
		parent::beforeRender();
		$this->addBreadcrumb(array(
			'__label' => 'Set Password',
			'link' => 'this'
		));
	}


	public function createComponentPasswordForm()
	{
		$post = $this->getRequest()->getPost();
		$form = $this->context->formFactory->createForm();
		$form->addPassword('password', 'Password')
			->addRule(NForm::FILLED, 'You must enter password.');
		$form->addPassword('password_check', 'Re-Enter Password')
			->addRule(NForm::EQUAL, 'Passwords don\'t match.', $form['password']);
		$form->addSubmit('change', 'Change password')
			->getControlPrototype()->class('btn-primary');
		$form->onSuccess[] = callback($this, 'passwordFormSubmitted');
		return $form;
	}


	public function passwordFormSubmitted($form)
	{
		$values = $form->getValues();
		$ticket = $this->getTicket();
		$user = $this->model('user')->getById($ticket->user_id);
		$this->model('user')->setPassword($user, $values['password']);
		$this->model('user.accountrecovery.ticket')->markUsed($ticket);
		$this->flashMessage('Your password was updated. You can <a href="%s" class="sign-in">sign in</a> with new password.', array($this->link('Sign:in')));
		$this->redirect($this->getAfterSetPassword());
	}


	protected function getTicket()
	{
		if ($this->ticket) {
			$model = $this->model('user.accountrecovery.ticket');
			$ticket = $model->getByHash($this->ticket);
			if ($model->wasUsed($ticket)) {
				$this->flashMessage('This account recovery ticket was already used. Your can <a href="%s">request another one</a>.', array($this->link('AccountRecovery:')));
				$this->redirect($this->getAfterTicketUsed());
			}
			if ($model->hasExpired($ticket)) {
				$this->flashMessage('Your account recovery request expired. Your can <a href="%s">request another one</a>.', array($this->link('AccountRecovery:')));
				$this->redirect($this->getAfterTicketExpired());
			}
			return $ticket;
		} else {
			throw new Nette\Application\BadRequestException('Missing secret key.');
		}
	}


	private function getAfterSetPassword()
	{
		return $this->context->parameters['user']['after']['set-password'];
	}


	private function getAfterTicketExpired()
	{
		return $this->context->parameters['user']['after']['ticket-expired'];
	}


	private function getAfterTicketUsed()
	{
		return $this->context->parameters['user']['after']['ticket-used'];
	}

}
