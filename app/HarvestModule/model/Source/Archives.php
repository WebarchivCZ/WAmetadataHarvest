<?php

namespace HarvestModule\Source;

use Exception,
	Mishak\WebArchive\Reader as WebArchiveReader,
	Nette;

class Archives extends Source implements IFilesSource {


	private $infoProvider = array();

	public function setOptions($options)
	{
		parent::setOptions($options + array(
			'infoProvider' => NULL,
		));
		$this->infoProvider = $this->options['infoProvider'];
	}


	public function setFiles($files)
	{
		$rows = array();
		foreach ($files as $file) {
			if ($info = $this->getInfo($file)) {
				$rows[] = new DataProvider\HashMap($info);
			}
		}
		$this->setup = TRUE;
		$this->hasData = (bool) $rows;
		$this->dataProvider = new DataProvider\Table($rows);
	}


	private function getInfo($file)
	{
		return $this->infoProvider->getInfo($file);
	}

}
