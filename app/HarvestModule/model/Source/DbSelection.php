<?php

namespace HarvestModule\Source;

class DbSelection extends Source implements IContentsSource {


	protected $columns;

	public function setOptions($options)
	{
		parent::setOptions($options + array(
			'ignoreUnknown' => TRUE,
			'connection' => NULL,
			'table' => NULL,
			'field' => 'id',
			'ignoreEmptyRows' => FALSE,
		));
	}

	protected $setup = TRUE;

	/**
	 * # seeds
	 * www.mishak.net
	 * ...
	 */
	public function setContents($contents)
	{
		/**
		 * I forgot on different source kinds for seeds (line vs table)
		 * There was no time for elegant solution so this is the aftermath.
		 */
		// <hack>
		$success = FALSE;
		try {
			$source = new Table;
			$source->setOptions(array(
				'ignoreBadLines' => TRUE,
				'compensateShorterWithNULL' => TRUE,
			));
			$source->setContents($contents);
			$success = TRUE;
		} catch (\Exception $e) {
			throw $e;
			$success = FALSE;
		}
		if ($success) {
			$rows = array();
			foreach ($source->getDataProvider()->getRows() as $row) {
				$rows[] = $row->getData($this->options['tableDataSourceQuery']);
			}
		} else {
		// </hack>
			$options = $this->options;
			unset($options['ignoreUnknown']);
			unset($options['connection']);
			unset($options['table']);
			unset($options['ignoreUnknown']);

			$rows = Helpers::parseLines($contents, $options);
		}
		if ($this->hasData = (bool) $rows) {

			$connection = $this->options['connection'];
			if (!$connection) {
				throw new \Exception("Source is missing connection.");
			}
			$table = $this->options['table'];
			if (!$table) {
				throw new \Exception("Source must have table defined.");
			}
			$field = $this->options['field'];
			if (!$field) {
				throw new \Exception("Source must have field defined.");
			}
			$table = $connection->table($table)->limit(1);
			$activeRows = array();
			$ignoreEmptyRows = $this->options['ignoreEmptyRows'];
			foreach ($rows as $value) {
				$selection = clone $table;
				$activeRow = $selection->where($field, $value)->fetch();
				if ($activeRow) {
					$activeRows[] = new DataProvider\DbActiveRow($activeRow);
				} elseif (!$ignoreEmptyRows) {
					throw new \Exception("Table has empty row for '$value' ($field).");
				}
			}
			$this->hasData = (bool) $activeRows;
			$this->dataProvider = new DataProvider\Table($activeRows);
		}
	}

}
