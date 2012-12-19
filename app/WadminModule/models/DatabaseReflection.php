<?php

namespace WadminModule;

use Nette;

class DatabaseReflection extends Nette\Database\Reflection\ConventionalReflection implements Nette\Database\IReflection {


	public function getBelongsToReference($table, $key)
	{
		static $map = array(
			'conspectus_subcategory' => array('conspectus_subcategories', 'id'),
			'curator' => array('curators', 'id'),
			'creator' => array('curators', 'id'),
			'resource' => array('resources', 'id'),
		);
		if (isset($map[$key])) {
			$res = $map[$key];
		} else {
			$res = parent::getBelongstoReference($table, $key);
		}
		return $res;
	}

}