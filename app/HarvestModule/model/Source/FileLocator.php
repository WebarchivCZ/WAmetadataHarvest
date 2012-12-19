<?php

namespace HarvestModule\Source;

use HarvestModule\Config,
	Mishak\ArchiveTar\Reader as TarReader,
	Nette,
	Nette\Caching\Cache,
	Nette\Utils\Finder;

class FileLocator extends Nette\Object {


	private $fileCache;

	private $archiveCache;

	public function __construct(Nette\Caching\Cache $cache)
	{
		$this->fileCache = $cache->derive('files');
		$this->archiveCache = $cache->derive('archives');
	}


	public function locateFiles(Config\Source $config, $directory)
	{
		$md5 = md5($directory);
		$key = $config->getSetting()->getName() . '/' . $config->getName() . '/' . $md5;
		// I am so sorry for this
		if (NULL === $directory) {
			throw new Exception("Source must be called with directory prior retrieving files without one.");
		}

		$archiveCache = $this->archiveCache;
		$fileCache = $this->fileCache;
		if (NULL === ($files = $this->fileCache->load($key . '/files'))) {
			$finder = Finder::findFiles($config->getFileMask())
				->from($directory)
				->limitDepth($config->getDepth());
			$files = $this->fetchFilenamesFromFinder($finder);
			$this->fileCache->save($key . '/files', $files, array(
				Cache::EXPIRATION => '+15 minutes',
				Cache::FILES => $files, // In case they get in trouble
			));
		}

		// Archives in Directory
		if (NULL === ($archives = $this->fileCache->load($md5 . '/archives'))) {
			$finder = Finder::findFiles(array('*.tar', '*.tgz', '*.tar.gz', '*.tar.bz2', '*.tb2', '*.tbz'))
				->from($directory)
				->limitDepth($config->getDepth());
			$this->fileCache->save($md5 . '/archives', $archives = $this->fetchFilenamesFromFinder($finder), array(
				Cache::EXPIRATION => '+15 minutes' // Prevent rescanning when user is browsing directories
			));
		}

		// Find Matching Records in Archives
		$usedArchives = array();
		$pattern = self::buildPattern((array) $config->getFileMask());
		foreach ($archives as $archive) {
			if (NULL === ($archiveRecords = $this->archiveCache->load($archive))) {
				$reader = new TarReader($archive);
				$reader->setReadContents(FALSE);
				$reader->setBuffer(PHP_INT_MAX);
				$this->archiveCache->save($archive, $archiveRecords = iterator_to_array($reader), array(
					Cache::FILES => $archive, // Keep archive records fresh
				));
			}
			foreach ($archiveRecords as $record) {
				if (preg_match($pattern, $record['filename'])) {
					$files[] = array($archive, $record['filename']);
				}
			}
		}
		// Archives don't hold too many records no advantage of caching matching records too.
		return $files;
	}


	private function fetchFilenamesFromFinder($finder)
	{
		$files = iterator_to_array($finder);
		array_walk($files, function (&$file) { $file = (string) $file; });
		return array_values($files);
	}


	/**
	 * Converts Finder pattern to regular expression.
	 * Copied from Nette\Utils\Finder
	 * @param  array
	 * @return string
	 */
	private static function buildPattern($masks)
	{
		$pattern = array();
		// TODO: accept regexp
		foreach ($masks as $mask) {
			$mask = rtrim(strtr($mask, '\\', '/'), '/');
			$prefix = '';
			if ($mask === '') {
				continue;

			} elseif ($mask === '*') {
				return NULL;

			} elseif ($mask[0] === '/') { // absolute fixing
				$mask = ltrim($mask, '/');
				$prefix = '(?<=^/)';
			}
			$pattern[] = $prefix . strtr(preg_quote($mask, '#'),
				array('\*\*' => '.*', '\*' => '[^/]*', '\?' => '[^/]', '\[\!' => '[^', '\[' => '[', '\]' => ']', '\-' => '-'));
		}
		return $pattern ? '#/(' . implode('|', $pattern) . ')\z#i' : NULL;
	}

}