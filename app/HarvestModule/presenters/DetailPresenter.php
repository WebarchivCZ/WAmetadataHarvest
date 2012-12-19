<?php

namespace HarvestModule;

use UserModule\BasePresenter,
	Nette\Utils\Finder;

abstract class DetailPresenter extends BasePresenter {


	/** @persistent */
	public $id;

	protected $record;

	protected function startup()
	{
		parent::startup();
		$this->authorize();
		$this->record = $this->load('harvest', $this->id);
	}


	public function beforeRender()
	{
		parent::beforeRender();

		$this->addBreadcrumb(array(
			'__label' => 'Harvests',
			'link' => 'Browse:'
		));

		$buff = array();
		foreach (explode(Directories::SEPARATOR, $this->directories->getPath($this->record->directory)) as $slug) {
			$buff[] = $slug;
			$this->addBreadcrumb(array(
				'label' => $slug,
				'url' => $this->link('Browse:', array('path' => implode(Directories::SEPARATOR, $buff)))
			));
		}

		switch ($this->record->status) {
			case Harvest::DISCOVERED:
				$this->template->actions = array(
					array(
						'Generate:', 'Generate', 'cog', 'success'
					)
				);
				break;
			case Harvest::PROCESSED:
				$this->template->actions = array(
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
					array(
						'Edit:loadValuesFromXml', 'Load values from XML', 'refresh'
					),
				);
				break;
		}
	}


	protected function redirectToDetail()
	{
		$this->redirect($this->getDetailUrl());
	}


	public function getDetailUrl()
	{
		return $this->link('Browse:', array('path' => $this->directories->getPath($this->record->directory)));
	}


	protected $directories;

	public function injectHarvestDirectories(Directories $directories)
	{
		$this->directories = $directories;
	}


	protected $harvest;

	public function injectHarvest(Harvest $harvest)
	{
		$this->harvest = $harvest;
	}

}
