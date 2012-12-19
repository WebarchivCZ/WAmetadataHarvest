<?php

namespace User\AccountRecovery;

use Model,
	Nette;

class Process extends Model\Base
{

	private $presenter;

	public function injectPresenter(Nette\Application\IPresenter $presenter)
	{
		$this->presenter = $presenter;
	}

	private $from;

	public function setFrom($from)
	{
		$this->from = $from;
	}

	private $project;

	public function setProject($project)
	{
		$this->project = $project;
	}

	private $person;

	public function setPerson($person)
	{
		$this->person = $person;
	}

	private $position;

	public function setPosition($position)
	{
		$this->position = $position;
	}

	private $template;

	public function setTemplate($filename)
	{
		$this->template = $filename;
	}

	public function start($user)
	{
		try {
			$ticket = $this->model('user.accountrecovery.ticket')->create($user);
			$this->sendMail($user, $ticket);
			return TRUE;
		} catch (Exception $e) {
			Nette\Diagnostics\Debugger::log($e);
		} catch (Nette\InvalidState $e) {
			Nette\Diagnostics\Debugger::log($e);
		}
		return FALSE;
	}

	public function sendMail($user, $ticket)
	{
		$mail = new Nette\Mail\Message;
		$mail->addTo($user->email);
		$mail->setFrom($this->from, $this->person);
		$mail->setPriority(1);

		$template = $this->createTemplate($ticket);
		list($subject, $html, $plain) = explode("\n\n\n\n", $template->__toString(TRUE), 3);

		$mail->setSubject($subject);
		$mail->setHtmlBody($html);
		$mail->setBody($plain);

		$mail->send();
	}

	public function createTemplate($ticket)
	{
		$template = new Nette\Templating\FileTemplate;
		$template->registerFilter($this->presenter->getContext()->nette->createLatte());
		$template->registerHelperLoader('Nette\Templating\Helpers::loader');

		$template->setFile($this->template);
		$template->setCacheStorage($this->presenter->getContext()->nette->templateCacheStorage);

		$template->setTranslator($this->presenter->getContext()->getByType('Nette\Localization\ITranslator'));

		$template->project = $this->project;
		$template->person = $this->person;
		$template->position = $this->position;
		$template->setPasswordUrl = $this->presenter->link('//:User:SetPassword:', array('ticket' => $ticket->hash));
		$template->accountRecoveryUrl = $this->presenter->link('//:User:AccountRecovery:');
		return $template;
	}

}