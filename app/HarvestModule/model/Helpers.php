<?php

namespace HarvestModule;

use Nette,
	DateTime;

class Helpers {


	public static function first($value)
	{
		return reset($value);
	}


	public static function datetime($value)
	{
		if ($value instanceof DateTime) {
			return $value;
		}
		return Nette\DateTime::from($value);
	}

}
