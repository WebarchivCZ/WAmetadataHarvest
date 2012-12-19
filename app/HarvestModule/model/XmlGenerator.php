<?php

namespace HarvestModule;

use DOMDocument,
	DOMElement,
	Exception,
	Nette;

class XmlGenerator extends Nette\Object {


	private $record;

	private $collector;

	private $setting;

	public function __construct(Nette\Database\Table\ActiveRow $record, Source\Collector $collector, Config\Setting $setting)
	{
		$this->record = $record;
		$this->collector = $collector;
		$this->setting = $setting;
	}


	private $document;

	private $dataProviders;

	public function generate()
	{
		$sources = $this->setting->getSources();
		$this->dataProviders = $this->collector->collect($sources, $this->record->directory);
		$this->dataProviders['iterator'] = NULL;
		$this->dataProviders['harvest'] = new Source\DataProvider\DbActiveRow($this->record);
		$this->dataProviders['form'] = new Source\DataProvider\HashMap($this->record->form ? unserialize($this->record->form) : array());
		$template = $this->setting->getXml();
		$this->document = new DOMDocument('1.0', 'UTF-8');
		$this->createNode($template, $this->document);
		// I love PHP!
		return $this->document;
	}


	private $namespaces = array();

	private $namespaceBuffer = array();

	private $defaults = array();

	private $defaultsBuffer = array();

	private $dataProvider = NULL;

	private $dataProviderBuffer = array();

	private $empty = TRUE;

	private $emptyBuffer = array();

	private $value = FALSE;

	private $valuesBuffer = array();

	private $iterator = 0;

	private $iteratorBuffer = array();

	const XMLNS_URI = 'http://www.w3.org/2000/xmlns/';

	private function createNode($definition, $parent)
	{
		if ($parent instanceof DOMElement) {
			$this->emptyBuffer[] = $this->empty;
			$this->empty = TRUE;
			$this->valuesBuffer[] = $this->value;
			$this->value = FALSE;
		}

		$data = array();
		$children = array();
		array_walk($definition, function ($value, $key) use (&$data, &$children) {
			if (is_int($key)) {
				$children[] = $value;
			} else {
				$data[$key] = $value;
			}
		});

		if (isset($data['defaults'])) {
			$this->defaultsBuffer[] = $this->defaults;
			$this->defaults = $data['defaults'];
		}
		$data += $this->defaults;

		if (!isset($data['name'])) {
			throw new Exception("Node is missing name.");
		}
		$name = $data['name'];

		if (isset($data['xmlns'])) {
			$this->namespaceBuffer[] = $this->namespaces;
			$this->namespaces = $data['xmlns'] + $this->namespaces;
		}

		if (isset($data['source'])) {
			$this->dataProviderBuffer[] = $this->dataProvider;
			$this->dataProvider = $this->getDataProviderByName($data['source']);
			$this->value = TRUE;
		}

		$value = isset($data['value']) ? $this->getValue($data['value']) : NULL;
		if ($url = $this->getNamespaceUrl($name)) {
			$element = $this->document->createElementNS($url, $name, $value);
		} else {
			$element = $this->document->createElement($name, $value);
		}

		if (isset($data['xmlns'])) {
			foreach ($data['xmlns'] as $namespace => $url) {
				$element->setAttributeNS(self::XMLNS_URI, 'xmlns:' . $namespace, $url);
			}
		}

		if (isset($data['attrs'])) {
			foreach ($data['attrs'] as $_name => $value) {
				$value = $this->getValue($value);
				if ($url = $this->getNamespaceUrl($_name)) {
					$element->setAttributeNS($url, $_name, $value);
				} else {
					$element->setAttribute($_name, $value);
				}
			}
		}

		$parent->appendChild($element);

		if (isset($data['items'])) {
			$this->value = TRUE;
			if ($this->dataProvider instanceof Source\DataProvider\ITable) {
				$this->iteratorBuffer[] = $this->dataProviders['iterator'];
				$this->dataProviders['iterator'] = $iterator = new Source\DataProvider\Iterator(TRUE);
				foreach ($this->dataProvider->getRows() as $row) {
					$this->empty = FALSE;
					$this->dataProviderBuffer[] = $this->dataProvider;
					$this->dataProvider = $row;
					if (is_int(key($data['items']))) {
						foreach ($data['items'] as $item) {
							$this->createNode($item, $element);
						}
					} else {
						$this->createNode($data['items'], $element);
					}
					$this->dataProvider = array_pop($this->dataProviderBuffer);
					$iterator->inc();
				}
				$this->dataProviders['iterator'] = array_pop($this->iteratorBuffer);
			} elseif ($this->dataProvider === NULL) {
				$this->empty = TRUE;
			} else {
				throw new Exception("Items must have source data provider of ITable; '" . get_class($this->dataProvider) . "' given.");
			}
		}

		foreach ($children as $child) {
			$this->createNode($child, $element);
		}

		// Revert to previous state of things
		if (isset($data['source'])) {
			$this->dataProvider = array_pop($this->dataProviderBuffer);
		}
		if (isset($data['xmlns'])) {
			$this->namespaces = array_pop($this->namespaceBuffer);
		}
		if (isset($data['defaults'])) {
			$this->defaults = array_pop($this->defaultsBuffer);
		}

		if ($this->value && $this->empty
			&& isset($data['optional']) ? $data['optional'] : NULL) {
			$element->parentNode->removeChild($element);
		}
		if ($parent instanceof DOMElement) {
			$this->value = array_pop($this->valuesBuffer) || $this->value;
			$this->empty = array_pop($this->emptyBuffer) && $this->empty;
		}
	}


	private $getValueReplaceCallbackReplaced;

	/**
	 * Get processed value, if value is array and result is not NULL empty is changed to FALSE.
	 * "Text <[source:]query[|format][|format:param:*:param] ...> text text."
	 * Query can wrapped in quotes so can be format parameters.
	 * Unquoted asterisk as parameter in filter will be replaced with value.
	 * Format can be name of PHP function, name of Helper function or own function of value if value is object.
	 *
	 * @param string|array $value
	 * @return string|NULL string value is always escaped 
	 */
	private function getValue($value)
	{
		if (is_array($value)) {
			foreach ($value as $_value) {
				$_value = $this->getValue($_value);
				if ($_value !== NULL) {
					$this->empty = FALSE;
					return $_value;
				}
			}
			return NULL;
		}

		if (strpos($value, '<') === FALSE) {
			return htmlspecialchars($value);
		} else {
			$this->getValueReplaceCallbackReplaced = FALSE;
			$expanded = preg_replace_callback('/<((?P<source>[a-z]+):)?(?P<query>(?(?=")"[^"]+"|[^|>]+))(?P<format>(\|!?[a-z]+(:(?(?=")"[^"]*"|[^:|>]+))*)*)?>/imx', $this->getValueReplaceCallback, $value, -1, $count);
			if ($count) {
				$this->value = TRUE;
			}
			$this->empty = $this->empty && !$this->getValueReplaceCallbackReplaced;
			return $this->getValueReplaceCallbackReplaced ? htmlspecialchars($expanded) : NULL;
		}
	}

	public function getValueReplaceCallback($matches)
	{
		$dataProvider = $matches['source'] ? $this->getDataProviderByName($matches['source']) : $this->dataProvider;
		$format = isset($matches['format']) ? $matches['format'] : NULL;
		$query = $matches['query'];
		if ($query[0] === '"' && $query[strlen($query) - 1] === '"') {
			$query = substr($query, 1, -1);
		}
		if (!$dataProvider) {
			return NULL;
		}
		$data = $dataProvider->getData($query);
		if (NULL === $data) {
			return NULL;
		}
		if ($format && preg_match_all('/\|(!?)([a-z]+):?(((?(?=")"[^"]*"|[^:|>]+):?)*)/i', $format, $matches)) {
			foreach ($matches[2] as $index => $name) {
				$self = '!' === $matches[1][$index];
				$callback = NULL;
				// determine callback
				if ($self) {
					$callback = array($data, $name);
				// filter is one of helpers
				} elseif (method_exists(__NAMESPACE__ . '\\Helpers', $name)) {
					$callback = array(__NAMESPACE__ . '\\Helpers', $name);
				// filter is php function
				} elseif (is_callable($name)) {
					$callback = $name;
				} else {
					throw new Exception("Unsupported format '$name'.");
				}

				// process parameters
				$params = array();
				$replaced = FALSE;
				if ($matches[3][$index] !== '' && preg_match_all('/((?(?=")"[^"]*"|[^:]+)):?/', $matches[3][$index], $params)) {
					$params = $params[1];
					// strip quotes and replace unquoted * with $data
					array_walk($params, function (&$value) use ($data, &$replaced) {
						if ($value[0] === '"' && $value[strlen($value) - 1] === '"') {
							$value = substr($value, 1, -1);
						} elseif ($value === '*') {
							$value = $data;
						}
					});
				}
				if (!$self && !$replaced) {
					$params[] = $data;
				}
				$data = call_user_func_array($callback, $params);
			}
		} elseif ($format) {
			throw new Exception("Bad format '$format'.");
		}
		$this->getValueReplaceCallbackReplaced = $this->getValueReplaceCallbackReplaced || NULL !== $data;
		$value = $this->getValueReplaceCallbackReplaced ? (string) $data : NULL;
		return $value;
	}

	private function getDataProviderByName($name)
	{
		if (!array_key_exists($name, $this->dataProviders)) {
			throw new Exception("Unknown data provider '$name'.");
		}
		return $this->dataProviders[$name];
	}

	private function getNamespaceUrl($name)
	{
		if ($prefix = $this->getNamespacePrefix($name)) {
			if (isset($this->namespaces[$prefix])) {
				return $this->namespaces[$prefix];
			} else {
				throw new Exception("Namespace '$prefix' for '$name' is not defined.");
			}
		} else {
			return NULL;
		}
	}


	private function getNamespacePrefix($name)
	{
		$parts = explode(':', $name, 2);
		return isset($parts[1]) ? $parts[0] : NULL;
	}

}
