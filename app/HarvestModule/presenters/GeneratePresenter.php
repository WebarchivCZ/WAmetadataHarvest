<?php

namespace HarvestModule;

use GeSHi,
	Exception;

final class GeneratePresenter extends DetailPresenter {


	private $document;

	public function actionDefault()
	{
		$setting = $this->settings->getSetting($this->record->setting);
		$generator = new XmlGenerator($this->record, $this->collector, $setting);
		$filename = $this->harvest->getXmlFilename($this->record);
		if (!is_dir($dir = dirname($filename))) {
			if (!mkdir($dir, 0777, TRUE)) {
				throw new Exception("Cannot create directory '$dir'.");
			}
		}

		$this->record->status = 'pending';
		$this->record->update();

		$document = $generator->generate();
		// Force well formed output
		$document->loadXML($document->saveXML());
		$document->preserveWhiteSpace = FALSE;
		$document->formatOutput = TRUE;
		$document->save($filename);

		$this->record->status = 'processed';
		$this->record->update();

		$this->flashMessage('XML was successfully generated!', 'success');
		$this->redirect('View:');
	}


	private $settings;

	public function injectSettings(Config\Settings $settings)
	{
		$this->settings = $settings;
	}


	private $collector;

	public function injectSourceCollector(Source\Collector $collector)
	{
		$this->collector = $collector;
	}

}
