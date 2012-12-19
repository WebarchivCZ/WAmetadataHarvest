<?php

namespace User;

use Nette,
	Nette\Security as NS,
	User;

class Authenticator extends Nette\Object implements NS\IAuthenticator
{

	private $model;

	public function __construct(User $model)
	{
		$this->model = $model;
	}

	/**
	 * Performs an authentication
	 * @param  array
	 * @return IIdentity
	 * @throws AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($username, $password) = $credentials;

		if (FALSE !== strpos($username, '@')) {
			$user = $this->model->getByEmail($username);
		} else {
			$user = $this->model->getByUsername($username);
		}

		if (!$user) {
			throw new NS\AuthenticationException("User '$username' not found.", self::IDENTITY_NOT_FOUND);
		}

		$now = new Nette\DateTime;
		if ($this->model->shouldTryLoginAfter($user) > $now) {
			throw new NS\AuthenticationException("You have reached unsuccessful login limit. Please try again later.", self::NOT_APPROVED);
		}

		if (!$this->model->isPasswordMatching($user, $password)) {
			$this->model->registerInvalidTry($user);
			throw new NS\AuthenticationException('Invalid password.', self::INVALID_CREDENTIAL);
		}

		$this->model->registerLogin($user);

		unset($user->password);
		return new NS\Identity($user->getPrimary(), $user->group, $user->toArray());
	}

}
