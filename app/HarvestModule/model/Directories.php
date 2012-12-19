<?php

namespace HarvestModule;

use Exception,
	Nette;

class Directories extends Nette\Object {


	const SEPARATOR = '/';


	private $directories = array();


	public function __construct($directories)
	{
		foreach ($directories as $name => $directory) {
			$this->directories[$name] = realpath($directory);
		}
	}


	public function getDirectories()
	{
		return $this->directories;
	}


	public function slugify($path)
	{
		return explode(self::SEPARATOR, $path);
	}


	public function getPath($realpath)
	{
		foreach ($this->directories as $root => $path) {
			if (0 === strpos($realpath, $path)) {
				$trim = trim(substr($realpath, strlen($path)), DIRECTORY_SEPARATOR);
				return $root . self::SEPARATOR . strtr($trim, DIRECTORY_SEPARATOR, self::SEPARATOR);
			}
		}
		throw new \Exception("Cannot find matching directory for path '$realpath'.");
	}


	public function getRealPath($path)
	{
		if (strpos($path, self::SEPARATOR) !== FALSE) {
			list($root, $path) = explode(self::SEPARATOR, $path, 2);
		} else {
			$root = $path;
			$path = NULL;
		}
		if (!isset($this->directories[$root])) {
			throw new Exception("Unknown root '$path'.");
		}
		if ($path === NULL) {
			return $this->directories[$root];
		}
		return $this->directories[$root] . DIRECTORY_SEPARATOR . strtr($path, self::SEPARATOR, DIRECTORY_SEPARATOR);
	}

}