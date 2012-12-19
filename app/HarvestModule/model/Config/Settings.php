<?php

namespace HarvestModule\Config;

use Nette,
	Nette\Config\Helpers,
	Exception;

class Settings extends Nette\Object {


	private $settingFactory;


	public function __construct(SettingFactory $settingFactory)
	{
		$this->settingFactory = $settingFactory;
	}


	private $settings = array();


	public function build($settings)
	{
		$templates = array();
		foreach ($settings as $name => $definition) {
			if (isset($definition['extends'])) {
				$extends = $definition['extends'];
				if (isset($templates[$extends])) {
					$defaults = $templates[$extends];
				} elseif (isset($this->settings[$extends])) {
					$defaults = $settings[$extends];
				} else {
					throw new \Exception("Configuration '$name' is extending unknown '$extends'. (Check definition and order.)");
				}
				unset($definition['extends']);

				if (isset($defaults['extends'])) {
					throw new \Exception("Configuration nesting extensions is not supported.");
				}

				$replace = array();
				if (isset($definition['replace'])) {
					$replace = $definition['replace'];
					unset($definition['replace']);
				}

				$definition = Helpers::merge($definition, $defaults);
				foreach ($replace as $id => $values) {
					$definition = $this->replace($definition, $id, $values);
				}
			}
			if (isset($definition['template']) && $definition['template']) {
				unset($definition['template']);
				$templates[$name] = $definition;
			} else {
				$this->settings[$name] = $this->settingFactory->createSetting($name, $definition);
			}
		}
	}


	private function replace($definition, $id, $values)
	{
		if (isset($definition['__id']) && $definition['__id'] === $id) {
			unset($definition['__id']);
			return Helpers::merge($values, $definition);
		} else {
			$result = array();
			foreach ($definition as $key => $value) {
				$result[$key] = is_array($value) ? $this->replace($value, $id, $values) : $value;
			}
			return $result;
		}
	}


	public function getSettings()
	{
		return $this->settings;
	}


	public function getSetting($name)
	{
		return $this->settings[$name];
	}

}