<?php

namespace HarvestModule\Source;

use Nette,
	HarvestModule\Config,
	Mishak\ArchiveTar\Reader as TarReader;

class Factory extends Nette\Object {


	private $locator;

	private $sources;

	public function __construct(FileLocator $locator, $sources)
	{
		$this->locator = $locator;
		$this->sources = $sources;
	}


	public function createSource(Config\Source $config, $directory)
	{
		$source = $config->getDataSource();
		if (!isset($this->sources[$source])) {
			throw new \Exception("Unknown data source '$source' for source '{$config->getName()}'.");
		}
		$files = $this->locator->locateFiles($config, $directory);
		$source = new $this->sources[$source];
		$source->setOptions($config->getDataSourceOptions());
		if ($source instanceof IFilesSource) {
			$source->setFiles($files);
		}
		if ($source instanceof IContentsSource && $files) {
			$file = reset($files);
			$contents = NULL;
			if (is_array($file)) {
				$found = FALSE;
				list($archive, $filename) = $file;
				$reader = new TarReader($archive);
				$contents = $reader->getContents($filename);
				unset($reader);
			} else {
				$contents = file_get_contents($file->getRealPath());
			}
			$source->setContents($contents);
		}
		return $source;
	}

}
