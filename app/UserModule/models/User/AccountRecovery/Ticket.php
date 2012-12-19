<?php

namespace User\AccountRecovery;

use DateInterval,
	Model,
	Nette;

class Ticket extends Model\Base
{

	public function getByHash($hash)
	{
		return $this->getTable()->where('hash', $hash)->limit(1)->fetch();
	}

	public function hasExpired($ticket)
	{
		return $ticket->expires_at < new Nette\DateTime;
	}

	public function create($user)
	{
		$tries = 5;
		for ($i = 0; $i < $tries; $i++) {
			try {
				$hash = sha1(uniqid(mt_rand(), TRUE));
				$expiresAt = new Nette\DateTime();
				$expiresAt->add(new DateInterval('PT3H'));
				return $this->getTable()->insert(array(
					'user_id' => $user->getPrimary(),
					'expires_at' => $expiresAt,
					'used' => null,
					'hash' => $hash,
				));
			} catch (\PDOException $e) {
				if ($e->getCode() != 23000) { // duplicate (hash)
					throw $e;
				}
			}
		}
		throw new Exception\CannotCreate("Reached limit of tries ($tries)");
	}

	public function markUsed($ticket)
	{
		$ticket->used = new Nette\Datetime;
		$ticket->update();
	}

	public function wasUsed($ticket)
	{
		return (bool) $ticket->used;
	}

}
