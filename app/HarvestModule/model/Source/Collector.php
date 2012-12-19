<?php

namespace HarvestModule\Source;

use Nette;

class Collector extends Nette\Object {


	private $factory;

	private $cache;

	public function __construct(Factory $factory, Nette\Caching\Cache $cache)
	{
		$this->factory = $factory;
		$this->cache = $cache;
	}


	public function collect($sources, $directory)
	{
		$collection = array();
		foreach ($sources as $config) {
			$source = $this->factory->createSource($config, $directory);
			$collection[$config->getName()] = $source->hasData() ? $source->getDataProvider() : NULL;
		}
		return $collection;
	}

}
