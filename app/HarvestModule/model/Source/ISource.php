<?php

namespace HarvestModule\Source;

interface ISource {


	/** @param array $options */
	public function setOptions($options);


	/**
	 * @return HarvestModule\Source\DataProvider\IDataProvider
	 */
	public function getDataProvider();

}
