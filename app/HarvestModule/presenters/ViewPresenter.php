<?php

namespace HarvestModule;

use DOMDocument,
	GeSHi;

final class ViewPresenter extends DetailPresenter {


	private $document;

	public function actionDefault()
	{
		$document = new DOMDocument;
		$document->load($this->harvest->getXmlFilename($this->record));
		$this->document = $document;
	}


	public function beforeRender()
	{
		parent::beforeRender();

		$this->addBreadcrumb(array(
			'__label' => 'View XML',
			'link' => 'this'
		));
	}


	public function renderDefault()
	{
		$this->template->harvest = $this->record;
		$this->template->raw = $this->document->saveXML();
		$geshi = new GeSHi($this->template->raw, 'xml');
		$this->template->highlighted = $geshi->parse_code();
	}


}
