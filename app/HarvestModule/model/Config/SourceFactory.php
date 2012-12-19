<?php

namespace HarvestModule\Config;

use Nette;

class SourceFactory extends Nette\Object {


	public function createSource($name, $definition)
	{
		$source = new Source($name);
		if (!isset($definition['filename'])) {
			throw new \Exception("Source '$name' is missing filename.");
		}
		$source->setFileMask($definition['filename']);

		if (!isset($definition['type'])) {
			throw new \Exception("Source '$name' is missing type.");
		}
		if (isset($definition['options']) && !is_array($definition['options'])) {
			throw new \Exception("Source '$name' options must be array.");
		}
		$source->setDataSource($definition['type'], isset($definition['options']) ? $definition['options'] : array());

		if (isset($definition['depth'])) {
			if (!is_numeric($definition['depth'])) {
				throw new \Exception("Source '$name' depth must be a number.");
			}
			$source->setDepth((int) $definition['depth']);
		}

		if (isset($definition['score'])) {
			if (!is_numeric($definition['score'])) {
				throw new \Exception("Source '$name' score must be a number.");
			}
			$source->setScoreMultiplier((float) $definition['score']);
		}
		return $source;
	}

}
