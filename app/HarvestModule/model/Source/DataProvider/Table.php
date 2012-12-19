<?php

namespace HarvestModule\Source\DataProvider;

use Exception,
	Nette;

class Table extends Nette\Object implements ITable, IRow {


	private $rows = array();

	public function __construct($rows)
	{
		foreach ($rows as $row) {
			if (!$row instanceof IRow) {
				$row = new HashMap($row, TRUE);
			}
			$this->rows[] = $row;
		}
	}


	public function getRows()
	{
		return $this->rows;
	}


	public function getData($key)
	{
		if (preg_match('/^(?P<function>[a-z][a-z_0-9]+)\((?P<value>[^)]+)\)$/i', $key, $matches)) {
			$value = $matches['value'];
			switch ($matches['function']) {
				case 'sum':
					$sum = 0;
					array_walk($this->rows, function ($row) use ($value, &$sum) {
						$sum += $row->getData($value);
					});
					return $sum;
				default:
					throw new Exception("Function $matchs[function] is not supported.");
			}
		} else {
			return NULL;
		}
	}

}
