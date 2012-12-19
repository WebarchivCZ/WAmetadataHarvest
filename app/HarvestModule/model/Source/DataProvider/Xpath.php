<?php

namespace HarvestModule\Source\DataProvider;

use Exception,
	Nette;

class Xpath extends Nette\Object implements IRow {


	private $dom;

	public function __construct($dom)
	{
		$this->dom = $dom;
	}


	public function getData($key)
	{
		$nodes = $this->dom->xpath($key);
		if ($nodes) {
			array_walk($nodes, function ($node) {
				return (string) $node;
			});
			return $nodes;
		} else {
			return NULL;
		}
	}

}
