<?php

namespace HarvestModule\Source;

use Exception;

class Table extends Source implements IContentsSource {


	protected $columns;

	public function setOptions($options)
	{
		parent::setOptions($options + array(
			'ignoreBadLines' => FALSE,
			'ignoreExtraColumns' => TRUE,
			'compensateShorterWithNULL' => FALSE,
		));
	}


	protected $setup = TRUE;

	/**
	 * # header
	 * [column] [#column] [column]
	 * # row
	 * column column column
	 */
	public function setContents($contents)
	{
		$options = $this->options;
		unset($options['ignoreBadLines']);
		unset($options['ignoreExtraColumns']);
		unset($options['compensateShorterWithNULL']);

		$lines = Helpers::parseLines($contents, $options);
		if ($this->hasData = (bool) $lines) {
			$header = array_shift($lines);
			if (FALSE === preg_match_all('/\[#?([^\]]+)\][ \t]?/', $header, $matches)) {
				throw new BadFormatException("Unknown header.");
			}
			$this->columns = $matches[1];
			$rows = array();
			$colCount = count($this->columns);
			$ignoreBadLines = $this->options['ignoreBadLines'];
			$compensateShorterWithNULL = $this->options['compensateShorterWithNULL'];
			$ignoreExtraColumns = $this->options['ignoreExtraColumns'];
			foreach ($lines as $index => $line) {
				$values = preg_split('/[ \t]/', $line);
				$valCount = count($values);
				if ($valCount != $colCount) {
					if ($ignoreExtraColumns && $valCount > $colCount) {
						$values = array_slice($values, 0, $colCount);
					} elseif ($compensateShorterWithNULL) {
						while (count($values) < $colCount) {
							$values[] = NULL;
						}
					} elseif ($ignoreBadLines) {
						continue;
					} else {
						// Line index is not equal to line number in source file!
						throw new BadFormatException("Column count does not match header on line " . ($index + 1) .".");
					}
				}
				$rows[] = array_combine($this->columns, $values);
			}
			$this->dataProvider = new DataProvider\Table($rows);
		}
	}

}
