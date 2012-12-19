<?php

namespace HarvestModule\Config;

use Nette,
	Exception;

class Setting extends Nette\Object {


	private $name;

	private $sources = array();

	private $xml;

	private $form;

	public function __construct($name, $xml, $form)
	{
		$this->name = $name;
		$this->xml = $xml;
		$this->form = $form;
	}


	public function getName()
	{
		return $this->name;
	}


	public function getSources()
	{
		return $this->sources;
	}


	public function getSource($name)
	{
		if (isset($this->sources[$name])) {
			return $this->sources[$name];
		} else {
			throw new Exception("Unknown source '$name'.");
		}
	}


	public function getXml()
	{
		return $this->xml;
	}


	public function getForm()
	{
		return $this->form;
	}


	public function addSource(Source $source)
	{
		$this->sources[$source->getName()] = $source;
		$source->setSetting($this);
	}

}
