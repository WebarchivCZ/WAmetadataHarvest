<?php

namespace HarvestModule\Source;

interface IFilesSource extends ISource {


	/** @param string[] $files */
	public function setFiles($files);

}
