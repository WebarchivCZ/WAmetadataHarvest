<?php

namespace HarvestModule\Source\DataProvider;

use Nette;

class HashMap extends Nette\Object implements IRow {


	protected $data;

	private $ignoreCase;

	public function __construct($data, $ignoreCase = FALSE)
	{
		$this->ignoreCase = $ignoreCase;
		if ($this->ignoreCase) {
			$this->data = array();
			foreach ($data as $key => $value) {
				$this->data[strtolower($key)] = $value;
			}
		} else {
			$this->data = $data;
		}
	}


	public function getData($key)
	{
		if ($this->ignoreCase) {
			$key = strtolower($key);
		}
		return isset($this->data[$key]) ? $this->data[$key] : NULL;
	}

}
