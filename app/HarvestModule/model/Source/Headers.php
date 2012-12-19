<?php

namespace HarvestModule\Source;

use Exception;

class Headers extends Source implements IContentsSource {


	public function setOptions($options)
	{
		parent::setOptions($options + array(
			'ignoreBadLines' => FALSE,
			'ignoreCase' => TRUE,
		));
	}

	protected $setup = TRUE;

	/**
	 * Reads contents as http headers Header: Value
	 *
	 * @param string $contents
	 * @param array $options
	 *		ignoreBadLines => FALSE
	 */
	public function setContents($contents)
	{
		$options = $this->options;
		unset($options['ignoreBadLines']);
		unset($options['ignoreCase']);
		$lines = Helpers::parseLines($contents, $options);
		$data = array();
		$ignoreBadLines = $this->options['ignoreBadLines'];
		$ignoreCase = $this->options['ignoreCase'];
		foreach ($lines as $line) {
			$values = explode(': ', $line, 2);
			if (count($values) != 2) {
				if ($ignoreBadLines) {
					continue;
				} else {
					throw new Exception("Bad format expected '<name>: <value>'.");
				}
			}
			list($name, $value) = $values;
			if ($ignoreCase) {
				$name = strtolower($name);
			}
			if (isset($data[$name])) {
				if (!is_array($data[$name])) {
					$data[$name] = array($data[$name]);
				} else {
					$data[$name][] = $value;
				}
			} else {
				$data[$name] = $value;
			}
		}
		$this->hasData = (bool) $data;
		$this->dataProvider = new DataProvider\HashMap($data, $ignoreCase);
	}

}
