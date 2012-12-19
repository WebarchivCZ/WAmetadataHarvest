<?php

namespace HarvestModule\Source;

class Helpers {


	/**
	 * Split contents to lines
	 *
	 * @param string $contents
	 * @param array $options
	 *		comments => FALSE or begin of comment
	 *		ignoreEmptyLines => TRUE
	 */
	public static function parseLines($contents, $options = array())
	{
		$options += array(
			'comments' => FALSE,
			'ignoreEmptyLines' => TRUE
		);
		$comments = $options['comments'];
		$ignoreEmptyLines = $options['ignoreEmptyLines'];

		$lines = preg_split('/(\R)/', $contents);
		if ($comments || $ignoreEmptyLines) {
			$lines = array_filter($lines, function ($line) use ($comments, $ignoreEmptyLines) {
			return (!$ignoreEmptyLines || trim($line) !== '')
				&& (!$comments || substr(ltrim($line), 0, strlen($comments)) !== $comments);
			});
		}
		return $lines;
	}

}
