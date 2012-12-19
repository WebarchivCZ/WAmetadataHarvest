<?php

namespace HarvestModule;

use Nette;

final class DownloadPresenter extends DetailPresenter {


	public function actionDefault()
	{
		$filename = $this->harvest->getXmlFilename($this->record);
		$this->sendResponse(new Nette\Application\Responses\FileResponse($filename, basename($filename), 'application/xml'));
	}

}
