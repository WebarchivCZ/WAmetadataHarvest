<?php

namespace HarvestModule\Source\DataProvider;

use Nette;

class Iterator extends HashMap implements IRow {


	public function __construct($startFromOne = FALSE)
	{
		parent::__construct(array(), TRUE);
		$this->data['index'] = $startFromOne ? 1 : 0;
	}


	public function inc()
	{
		++$this->data['index'];
	}

}
