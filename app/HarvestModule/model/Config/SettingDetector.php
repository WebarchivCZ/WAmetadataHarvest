<?php

namespace HarvestModule\Config;

use HarvestModule\Source,
	Nette;

class SettingDetector extends Nette\Object {


	private $settings;

	private $locator;

	public function __construct(Settings $settings, Source\FileLocator $locator)
	{
		$this->settings = $settings;
		$this->locator = $locator;
	}


	public function getBestSetting($directory)
	{
		$bestScore = 0;
		$best = NULL;
		foreach ($this->settings->getSettings() as $setting) {
			$score = 0;
			foreach ($setting->getSources() as $source) {
				$files = 0;
				if ($multiplier = $source->getScoreMultiplier()) {
					$files = count($this->locator->locateFiles($source, $directory));
					$score += ($files && $source->isSingleFile() ? 1 : $files) * $multiplier;
				}
			}
			if ($score > $bestScore) {
				$best = $setting;
				$bestScore = $score;
			}
		}
		return $best;
	}

}
