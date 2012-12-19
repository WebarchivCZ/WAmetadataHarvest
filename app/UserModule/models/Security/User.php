<?php

namespace UserModule\Security;

use Nette;

class User extends Nette\Security\User {

	private $registration;

	private $accountRecovery;

	public function __construct(Nette\Security\IUserStorage $storage, Nette\DI\Container $context)
	{
		parent::__construct($storage, $context);
		$this->registration = $context->parameters['user']['registration']['enabled'];
		$this->accountRecovery = $context->parameters['user']['account-recovery']['enabled'];
	}

	public function canRegister()
	{
		return $this->registration;
	}

	public function canRecoverAccount()
	{
		return $this->accountRecovery;
	}

}
