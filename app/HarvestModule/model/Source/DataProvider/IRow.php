<?php

namespace HarvestModule\Source\DataProvider;

interface IRow {


	/**
	 * @param string $key
	 * @return mixed NULL means for empty
	 */
	public function getData($key);

}
