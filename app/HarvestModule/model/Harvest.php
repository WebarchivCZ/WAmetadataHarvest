<?php

namespace HarvestModule;

use Model,
	Nette\Utils\Strings,
	Nette\DateTime;

class Harvest extends Model\Base {


	const DISCOVERED = 'discovered',
		PENDING = 'pending',
		PROCESSED = 'processed';


	public function getByDirectory($directory)
	{
		return $this->getTable()->where('directory', $directory)->limit(1)->fetch();
	}


	public function discover($directory, Config\Setting $setting)
	{
		$this->connection->query('LOCK TABLES ' . $this->tableName . ' WRITE');
		$now = new DateTime;
		$record = $this->getTable()->insert(array(
			'uuid' => $this->generateUuid(),
			'directory' => $directory,
			'status' => self::DISCOVERED,
			'setting' => $setting->getName(),
			'created' => $now,
			'updated' => $now,
			'form' => serialize(array())
		));
		$this->connection->query('UNLOCK TABLES');
		return $record;
	}


	public function generateUuid()
	{
		do {
			$uuid = $this->formatUuid(Strings::random(32));
		} while ($this->getTable()->where('uuid', $uuid)->limit(1)->fetch());
		return $uuid;
	}


	private function formatUuid($uuid)
	{
		$s = str_split($uuid, 4);
		return "$s[0]$s[1]-$s[2]-$s[3]-$s[4]-$s[5]$s[6]$s[7]";
	}



	public function getXmlFilename($harvest)
	{
		$name = 'harvest';
		$output = $this->xmlOutputDirectory;
		if (!$output) {
			$output = $harvest->directory;
		} else {
			$path = $this->directories->getPath($harvest->directory);
			$converted = strtr($path, Directories::SEPARATOR, DIRECTORY_SEPARATOR);
			$output .= DIRECTORY_SEPARATOR . ltrim($converted, DIRECTORY_SEPARATOR);
		}
		return rtrim($output, DIRECTORY_SEPARATOR) . $name . '.xml';
	}


	private $xmlOutputDirectory;

	public function setXmlOutputDirectory($directory)
	{
		$this->xmlOutputDirectory = $directory;
	}


	private $directories;

	public function injectDirectories(Directories $directories)
	{
		$this->directories = $directories;
	}

}
