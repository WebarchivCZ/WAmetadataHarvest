<?php

namespace HarvestModule\Source\DataProvider;

use Nette;

class DbSelection extends Nette\Object implements ITable {


	private $selection = array();

	public function __construct(Nette\Database\Table\Selection $selection)
	{
		$this->selection = $selection;
		foreach ($selection as $row) {
			$this->rows[] = new DbActiveRow($row, TRUE);
		}
	}


	public function getRows()
	{
		return $this->rows;
	}

}
