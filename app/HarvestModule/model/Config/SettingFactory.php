<?php

namespace HarvestModule\Config;

use Nette,
	Exception;

class SettingFactory extends Nette\Object {


	private $sourceFactory;


	public function __construct(SourceFactory $sourceFactory)
	{
		$this->sourceFactory = $sourceFactory;
	}


	public function createSetting($name, $definition)
	{
		if (!isset($definition['sources'])) {
			throw new \Exception("Setting '$name' is missing sources definition.");
		}
		if (!isset($definition['xml'])) {
			throw new \Exception("Setting '$name' is missing xml definition.");
		}
		if (!isset($definition['form'])) {
			throw new \Exception("Setting '$name' is missing form definition.");
		}

		$setting = new Setting($name, $definition['xml'], $definition['form']);
		foreach ($definition['sources'] as $_name => $definition) {
			$setting->addSource($this->sourceFactory->createSource($_name, $definition));
		}
		return $setting;
	}

}
