<?php

namespace HarvestModule\Source;

class Xml extends Source implements IContentsSource {


	protected $setup = TRUE;

	public function setContents($contents)
	{
		$dom = simplexml_load_string($contents);
		if ($this->hasData = (bool) $dom) {
			$this->dataProvider = new DataProvider\Xpath($dom);
		}
	}

}
