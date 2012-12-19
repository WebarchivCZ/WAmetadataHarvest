<?php

namespace HarvestModule\Source\DataProvider;

use Nette;

class DbActiveRow extends Object implements IRow {


	private $row;

	public function __construct($row)
	{
		$this->row = $row;
	}


	public function getData($key)
	{
		// $dump = (bool) 
		$slugs = explode('.', $key);
		$value = $this->row;
		do {
			$key = array_shift($slugs);
			if ($value->$key === NULL) {
				// dump([$key, $value, $this->row, $slugs]);
				// die;
				return NULL;
			}
			$value = $value->$key;
		} while ($slugs);
		return $value;
	}

}
