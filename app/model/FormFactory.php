<?php

class FormFactory {

	private $translator;

	public function __construct(Nette\Localization\ITranslator $translator = NULL)
	{
		$this->translator = $translator;
	}

	public function createForm()
	{
		$form = new Nette\Application\UI\Form;
        $form->setRenderer(new Kdyby\Extension\Forms\BootstrapRenderer\BootstrapRenderer);
        if ($this->translator !== NULL) {
        	$form->setTranslator($this->translator);
        }
        return $form;
	}

}