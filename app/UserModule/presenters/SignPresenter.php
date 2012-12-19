<?php

namespace UserModule;

use Nette,
	Nette\Forms\Form as NForm,
	Nette\Utils\Html as NHtml;

final class SignPresenter extends AbstractSignPresenter {


	public function createComponentSignInForm()
	{
		$form = $this->context->formFactory->createForm();
		$post = $this->getRequest()->getPost();
		$username = isset($post, $post['username']) ? $post['username'] : '';
		$form->addText('username', 'Username')
			->addRule(NForm::FILLED, 'You must enter username.')
			->setValue($username);
		$form->addPassword('password', 'Password')
			->addRule(NForm::FILLED, 'You must enter password.');
		$remember = $form->addCheckbox('remember', "Remember");
		$form->addSubmit('sign_in', 'Sign In')
			->getControlPrototype()->class('btn-primary');
		$form->onSuccess[] = callback($this, 'signInFormSubmitted');
		return $form;
	}


	public function signInFormSubmitted($form)
	{
		try {
			$values = $form->getValues();
			$user = $this->user;
			if ($values->remember) {
				$user->setExpiration($this->getSessionExpiration(TRUE), FALSE);
			} else {
				$user->setExpiration($this->getSessionExpiration());
			}
			$this->user->login($values->username, $values->password);
			$this->flashMessage('You have been successfully signed in.');
			$this->getApplication()->restoreRequest($this->backlink);
			$this->redirect($this->getAfterSignIn());
		} catch (Nette\Security\AuthenticationException $e) {
			$message = 'Bad Username or Password.';
			if ($e->getCode() === Nette\Security\IAuthenticator::NOT_APPROVED) {
				$message = $e->getMessage();
				$model = $this->context->userModel;
				$user = $model->getByUsername($values->username);
				if (!$user) {
					$user = $model->getByEmail($values->username);
				}
				$this->flashMessage($model->shouldTryLoginAfter($user)->format('Y-m-d H:i:s'), 'info');
			}
			$this->flashMessage($message, 'error');
			$this->redirect('this');
		}
	}


	public function renderIn()
	{
		$this->addBreadcrumb(array(
			'__label' => 'Sign In',
			'link' => 'this'
		), TRUE);
	}


	public function createComponentSignUpForm()
	{
		$usernameMin = 1;
		$usernameMax = 64;
		$passwordMin = 6;
		$post = $this->request->post;
		$username = isset($post, $post['username']) ? $post['username'] : '';
		$email = isset($post, $post['email']) ? $post['email'] : '';
		$form = $this->context->formFactory->createForm();
		$form->addText('username', 'Username')
			->addRule(NForm::FILLED, 'You must enter username.')
			->addRule(NForm::MAX_LENGTH, 'Maximal length of username is %d.', $usernameMax)
			->addRule(NForm::REGEXP, 'Only a-z, 0-9 and _ are allowed.',  '/^[a-z0-9_]+$/')
			->addRule(callback($this, 'isUsernameAvailable'), 'Username is taken please pick another.')
			->setValue($username)
			->setOption('description', NHtml::el()->setText($this->translate('%d up to %d characters of a-z, 0-9 and _.', $usernameMin, $usernameMax)));

		$form->addText('email', 'Email')
			->addRule(NForm::EMAIL, 'You must enter email.')
			->addRule(callback($this, 'isEmailAvailable'), 'Email is taken please pick another.')
			->setValue($email)
		;
		$form->addPassword('password', 'Password')
			->addRule(NForm::MIN_LENGTH, 'Password must be at least %s characters long.', $passwordMin)
			->addRule(NForm::FILLED, 'You must enter password.')
			->setOption('description', NHtml::el()->setText($this->translate('At least %d characters long.', $passwordMin)));

		$form->addSubmit('sign_up', 'Sign Up')
			->getControlPrototype()->class('btn-primary');
		$form->onSuccess[] = callback($this, 'signUpFormSuccess');
		return $form;
	}


	public function actionUp()
	{
		if (!$this->getUser()->canRegister()) {
			throw new Nette\Application\BadRequestException('Registration is not allowed.', Nette\Http\IResponse::S403_FORBIDDEN);
		}
	}


	public function signUpFormSuccess($form)
	{
		try {
			$userModel = $this->context->userModel;
			$values = $form->getValues();
			$data = $userModel->getDataFromRegistration($values);
			$userModel->create($data);
			$this->user->login($values['username'], $values['password']);
			$this->flashMessage('Welcome to our server.');

			$this->getApplication()->restoreRequest($this->backlink);
			$this->redirect($this->getAfterSignUp());
		} catch (Nette\Application\AbortException $e) {
			throw $e;
		}
	}


	public function isUsernameAvailable($item)
	{
		return !(bool) $this->model('user')->getByUsername($item->value);
	}


	public function isEmailAvailable($item)
	{
		return !(bool) $this->model('user')->getByEmail($item->value);
	}


	public function renderUp()
	{
		$this->addBreadcrumb(array(
			'__label' => 'Sign Up',
			'link' => 'this'
		), TRUE);
	}


	public function actionOut()
	{
		$this->user->logout(TRUE);
		$this->flashMessage('You have been signed out.');
		$this->redirect($this->getAfterSignOut());
	}


	private function getSessionExpiration($rememberMe = FALSE)
	{
		return $this->context->parameters['user']['session'][$rememberMe ? 'remember-me' : 'default'];
	}

}
