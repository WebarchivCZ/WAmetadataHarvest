<?php

namespace HarvestModule\Source\DataProvider;

interface ITable {


	/**
	 * @return array|Iterable of IRow
	 */
	public function getRows();

}
