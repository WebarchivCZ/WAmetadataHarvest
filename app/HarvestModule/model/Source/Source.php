<?php

namespace HarvestModule\Source;

abstract class Source implements ISource {


	protected $options = array();

	public function setOptions($options = array())
	{
		$this->options = $options;
	}


	protected $dataProvider = NULL;

	/**
	 * Stupid mistake prevention.
	 * Must be set to TRUE prior calling getDataProvider.
	 * @var bool
	 */
	protected $setup = FALSE;

	public function getDataProvider()
	{
		if (!$this->setup) {
			throw new \Exception("Source '" . get_class($this) . "' was not properly setup.");
		}
		return $this->dataProvider;
	}


	protected $hasData = FALSE;

	public function hasData()
	{
		return $this->hasData;
	}

}
