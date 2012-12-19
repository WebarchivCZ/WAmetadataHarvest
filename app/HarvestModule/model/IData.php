<?php

namespace HarvestModule;

interface IData {

	/** @return bool */
	public function isMultiple();

	/** @return array */
	public function getRecord();

}
