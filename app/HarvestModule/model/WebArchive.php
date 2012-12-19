<?php

namespace HarvestModule;

use Mishak\WebArchive\Reader as WebArchiveReader,
	Nette,
	Nette\Caching\Cache;

class WebArchive extends Nette\Object {


	private $cache;

	public function __construct(Cache $cache)
	{
		$this->cache = $cache->derive('webArchive');
	}


	public function getInfo($filename)
	{
		if (NULL === ($info = $this->cache->load($filename))) {
			$webarchive = new WebArchiveReader($filename);
			$info = $webarchive->getInfo();
			unset($webarchive);
			$info['md5'] = md5_file($filename);
			$info['size'] = filesize($filename);
			$info['basename'] = basename($filename);
			$this->cache->save($filename, $info, array(
				Cache::FILES => $filename,
			));
		}
		return $info;
	}


	public function hasCachedInfo($filename)
	{
		return NULL !== $this->cache->load($filename);
	}

}