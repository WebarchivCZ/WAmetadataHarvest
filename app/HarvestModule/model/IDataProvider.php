<?php

namespace HarvestModule;

interface IDataProvider {

	/** @return IData */
	public function getData($query);

}
