<?php

namespace HarvestModule;

use UserModule\BasePresenter,
	Nette\Utils\Finder;

final class BrowsePresenter extends BasePresenter {


	/** @persistent */
	public $path;

	protected function startup()
	{
		parent::startup();
		$this->authorize();
	}


	private $setting;

	private $actions = array();

	private $prefetch = array();

	private $total = 0;

	private $record;

	public function actionDefault()
	{
		if ($this->path === NULL) {
			$this->path = key($this->directories->getDirectories()) . Directories::SEPARATOR;
		}
		$this->initPath($this->path);

		$directory = rtrim(realpath($this->directories->getRealPath($this->path)), DIRECTORY_SEPARATOR);

		$harvest = $this->harvest->getByDirectory($directory);
		if (!$harvest) {
			$setting = $this->settingDetector->getBestSetting($directory);
			if ($setting) {
				$harvest = $this->harvest->discover($directory, $setting);
			}
		} else {
			$this->setting = $this->settings->getSetting($harvest->setting);
		}
		if ($harvest) {
			switch ($harvest->status) {
				case Harvest::DISCOVERED:
					$this->actions = array(
						array(
							'Generate:', 'Generate', 'cog', 'success'
						)
					);
					break;
				case Harvest::PROCESSED:
					$this->actions = array(
						array(
							'Edit:', 'Edit', 'edit'
						),
						array(
							'View:', 'View', 'eye-open'
						),
						array(
							'Download:', 'Download', 'download-alt'
						),
						FALSE /* separator */,
						array(
							'Generate:', 'Generate', 'cog'
						),
					);
					break;
			}
		}
		$this->record = $harvest;

		if ($this->setting) {
			// mark archives for prefetching
			$source = $this->setting->getSource('archives');
			$files = $this->locator->locateFiles($source, $directory);
			foreach ($files as $file) {
				if (!$this->webArchive->hasCachedInfo($file)) {
					$this->prefetch[] = $file;
				}
			}
			$this->total = count($files);
		}
	}


	public function handlePrefetch()
	{
		if ($this->isAjax()) {
			if ($this->prefetch) {
				$this->webArchive->getInfo(array_shift($this->prefetch));
				$this->payload->percent = round(100 * ($this->total - count($this->prefetch)) / $this->total);
			} else {
				$this->payload->percent = 100;
			}
			$this->sendPayload();
		} else {
			$this->redirect('default');
		}
	}


	private $slugs;

	private function initPath($path)
	{
		$this->slugs = $this->directories->slugify($path);
		try {
			if (in_array('..', $this->slugs, TRUE)
				|| in_array('.', $this->slugs, TRUE)) {
				$this->error('Please go home script kiddie.', 403);
			}
			$realpath = $this->directories->getRealPath($path);
			if (!is_dir($realpath)) {
				$this->error("Path not found '$this->path'.");
			}
			if (!is_readable($realpath)) {
				$this->error("Cannot read '$this->path'.");
			}
		} catch (\Nette\Application\BadRequestException $e) {
			throw $e;
		} catch (\Nette\Application\AbortException $e) {
			throw $e;
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}


	public function beforeRender()
	{
		parent::beforeRender();

		$this->addBreadcrumb(array(
			'__label' => 'Harvests',
			'link' => 'this'
		));
	}


	public function renderDefault()
	{
		$buff = array();
		$items = array();
		foreach (explode(Directories::SEPARATOR, rtrim($this->path, DIRECTORIES::SEPARATOR)) as $slug) {
			$buff[] = $slug;
			$this->addBreadcrumb(array(
				'label' => $slug,
				'url' => $this->link('this', array('path' => implode(Directories::SEPARATOR, $buff)))
			));
		}

		$this->template->path = $this->path;
		$this->template->slugs = $this->slugs;
		$this->template->tree = $this->expandDirectories($this->slugs);
		$this->template->directories = $this->getDirectories($this->path);
		$this->template->files = $this->getFiles($this->path);

		$this->template->setting = $this->setting;
		$this->template->harvest = $this->record;
		$this->template->actions = $this->actions;

		$this->template->percent = $this->total ? round(100 * ($this->total - count($this->prefetch)) / $this->total) : 100;
		$this->template->showXml = $this->record ? file_exists($this->harvest->getXmlFilename($this->record)) : FALSE;
	}


	protected function expandDirectories($slugs, $path = NULL)
	{
		$current = array_shift($slugs);
		$directories = array();
		if ($path === NULL) {
			$roots = array_keys($this->directories->getDirectories());
			sort($roots);
			foreach ($roots as $root) {
				if ($root === $current) {
					$directories[$root] = $this->expandDirectories($slugs, $root);
				} else {
					$directories[$root] = array();
				}
			}
		} else {
			$subdirectories = array_values(array_map(function ($directory) {
				return $directory->getBasename();
			}, iterator_to_array($this->getDirectories($path))));
			sort($subdirectories);
			foreach ($subdirectories as $directory) {
				if ($directory === $current) {
					$directories[$directory] = $this->expandDirectories($slugs, $path . Directories::SEPARATOR . $directory);
				} else {
					$directories[$directory] = array();
				}
			}
		}
		return $directories;
	}


	protected function getDirectories($path)
	{
		return Finder::findDirectories('*')
			->exclude('.*')
			->in($this->directories->getRealPath($path));
	}


	protected function getFiles($path)
	{
		return Finder::findFiles('*.*')
			->exclude('.*')
			->in($this->directories->getRealPath($path));
	}


	private $directories;

	public function injectHarvestDirectories(Directories $directories)
	{
		$this->directories = $directories;
	}


	private $settings;

	public function injectSettings(Config\Settings $settings)
	{
		$this->settings = $settings;
	}


	private $settingDetector;

	public function injectSettingDetector(Config\SettingDetector $settingDetector)
	{
		$this->settingDetector = $settingDetector;
	}


	private $harvest;

	public function injectHarvest(Harvest $harvest)
	{
		$this->harvest = $harvest;
	}


	private $locator;

	public function injectLocator(Source\FileLocator $locator)
	{
		$this->locator = $locator;
	}


	private $webArchive;

	public function injectWebArchive(WebArchive $webArchive)
	{
		$this->webArchive = $webArchive;
	}

}
