<?php

namespace UserModule;

use Nette,
	Nette\Forms\Form as NForm;

final class AccountRecoveryPresenter extends \BasePresenter {


	protected function startup()
	{
		if (!$this->getUser()->canRecoverAccount()) {
			throw new Nette\Application\BadRequestException('Account recovery is not allowed.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
	}


	public function beforeRender()
	{
		parent::beforeRender();
		$this->addBreadcrumb(array(
			'__label' => 'Account Recovery',
			'link' => 'this',
		));
	}


	public function createComponentRecoverForm()
	{
		$post = $this->getRequest()->getPost();
		$email = isset($post, $post['email']) ? $post['email'] : '';
		$form = $this->context->formFactory->createForm();
		$form->addText('email', 'Email')
			->addRule(NForm::EMAIL, 'You must enter valid email address.')
			->setValue($email);
		$form->addSubmit('recover', 'Recover Account')
			->getControlPrototype()->class('button button-submit');
		$form->onSuccess[] = callback($this, 'recoverFormSuccess');
		return $form;
	}


	public function recoverFormSuccess($form)
	{
		$values = $form->getValues();
		$user = $this->model('user')->getByEmail($values->email);
		if ($user) {
			$process = $this->model('user.accountrecovery.process');
			$process->injectPresenter($this);
			if ($process->start($user)) {
				$this->flashMessage('Instructions how to recover your password were sent on your email.');
				$this->redirect($this->getAfterAccountRecovery());
			} else {
				$this->flashMessage('I was unable to perform account recovery, please try again or contact support.');
			}
		} else {
			$this->flashMessage('Account not found.');
		}
		$this->redirect('this');
	}


	private function getAfterAccountRecovery()
	{
		return $this->context->parameters['user']['after']['account-recovery'];
	}

}
