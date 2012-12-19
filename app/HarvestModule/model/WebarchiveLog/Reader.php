<?php

namespace HarvestModule\WebarchiveLog;

use Nette;

class Reader extends Nette\Object {


	public function __construct($contents)
	{
		$this->parse($contents);
	}


	private $columns;


	private $values;


	private function parse($contents)
	{
		$lines = explode("\n", $contents);
		$header = array_shift($lines);
		$result = preg_match_all('/\[#?([^\]]+)\][ \t]?/', $header, $matches);
		if ($result === FALSE) {
			throw new BadFormatException("Unknown header.");
		}
		$this->columns = $matches[1];
		$colCount = count($this->columns);
		foreach ($lines as $index => $line) {
			$line = rtrim($line, "\r\n");
			if ($line === '') {
				continue;
			}
			$values = explode(' ', $line);
			if (count($values) != $colCount) {
				throw new BadFormatException("Column count does not match header on line " . $index + 1 . ".");
			}
			$this->values[] = array_combine($this->columns, $values);
		}
	}


	public function getRecords()
	{
		return $this->values;
	}


	public static function fromFile($filename)
	{
		$contents = file_get_contents($filename);
		return new static($contents);
	}


	public static function fromString($string)
	{
		return new static($string);
	}

}
