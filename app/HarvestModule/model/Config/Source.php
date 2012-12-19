<?php

namespace HarvestModule\Config;

use Mishak\ArchiveTar\Reader as TarReader,
	Nette,
	Nette\Utils\Finder,
	Exception;

class Source extends Nette\Object {


	private $name;

	public function __construct($name)
	{
		$this->name = $name;
	}


	public function getName()
	{
		return $this->name;
	}


	private $scoreMultiplier = 0;

	public function getScoreMultiplier()
	{
		return $this->scoreMultiplier;
	}


	public function setScoreMultiplier($multiplier)
	{
		$this->scoreMultiplier = $multiplier;
	}


	private $mask;

	public function setFileMask($mask)
	{
		$this->mask = $mask;
	}

	public function getFileMask()
	{
		return $this->mask;
	}

	private $dataSource;

	private $dataSourceOptions;

	public function setDataSource($dataSource, $options = array())
	{
		$this->dataSource = $dataSource;
		$this->dataSourceOptions = $options;
	}


	public function getDataSource()
	{
		return $this->dataSource;
	}


	public function getDataSourceOptions()
	{
		return $this->dataSourceOptions;
	}


	public function isSingleFile()
	{
		return is_array($this->mask) && count($this->mask) <= 1 || !is_array($this->mask) && preg_match('/[?*]/', $this->mask);
	}


	private $depth = 0;

	public function setDepth($depth)
	{
		$this->depth = $depth;
	}

	public function getDepth()
	{
		return $this->depth;
	}


	private $setting;

	public function setSetting(Setting $setting)
	{
		$this->setting = $setting;
	}


	public function getSetting()
	{
		return $this->setting;
	}

}
