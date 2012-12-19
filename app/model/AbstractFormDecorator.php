<?php

use Nette\Forms\Form,
	Nette\Utils\Html;

abstract class AbstractFormDecorator {

	protected $name;

	private $context;

	private $translator;

	private $modelManager;

	public function __construct(Nette\DI\Container $context, Nette\Localization\ITranslator $translator, \Model\Manager $modelManager)
	{
		$this->context = $context;
		$this->translator = $translator;
		$this->modelManager = $modelManager;
	}

	const
		TYPE_TEXT = 'text',
		TYPE_TEXTAREA = 'textarea',
		TYPE_SET = 'set',
		TYPE_LIST = 'list',
		TYPE_RANGE_LIST = 'range-list',
		TYPE_RANGE_INPUT = 'range-input',
		TYPE_GROUP = 'group';

	public function decorateForm($form)
	{
		$parameters = $this->getParameters();
		$prefix = $this->name . '-';
		$special = array();
		foreach ($parameters as $name => $definition) {
			$values = isset($definition['values']) ? $definition['values'] : array();
			$type = $this->getType($definition);
			switch ($type) {
				case self::TYPE_GROUP:
					$group = $form->addGroup(ltrim($name, '_'))->setOption('label', NULL);
					$input = NULL;
					break;
				case self::TYPE_TEXT:
					$input = $form->addText($name, $prefix . $name);
					if (isset($definition['required']) && $definition['required']) {
						$input->setRequired();
					}
					if (isset($definition['html5type'])) {
						switch ($definition['html5type']) {
							case 'web':
								$input
									->setDefaultValue('http://www.')
									->addRule(Form::FILLED)
									->addCondition(~Form::EQUAL, 'http://www.')
										->addRule(Form::URL);
								break;
						}
					}
					$input->getControlPrototype()->addClass('input-xlarge');
					break;
				case self::TYPE_TEXTAREA:
					$icons = isset($definition['icons']) ? $definition['icons'] : NULL;
					if (isset($definition['locale'])) {
						foreach ($definition['locale'] as $locale) {
							$label = Html::el()->setText($this->translator->translate($prefix . $name . '-' . $locale));
							if ($icons && isset($icons[$locale])) {
								$label->insert(0, Html::el('i', array('class' => 'icon-lang-' . $icons[$locale])));
								$label->insert(1, ' ');
							}
							$input = $form->addTextarea($name . '_' . $locale, $label);
							$control = $input->getControlPrototype();
							$control->addClass('localized');
							$control->addClass('input-xlarge');
							if (isset($definition['html']) && $definition['html']) {
								$control->addClass('html');
							}
						}
						$input = NULL;
					} else {
						throw new \Exception('Adding textareas without locale array is not implemented.');
					}
					break;
				case 'select':
					$input = $form->addSelect($name . (isset($definition['id']) ? '_id' : ''), $prefix . $name);
					if ($values) {
						$input->setItems($values);
					}
					if (isset($definition['empty'])) {
						$input->addRule(~Form::EQUAL, $definition['empty'], '');
					}
					if (isset($definition['fill']) && $definition['fill']) {
						$this->fillSelectWithItems($name, $input);
					}
					break;

				case self::TYPE_RANGE_INPUT:
					list($min, $max) = explode('-', $values, 2);
					$branch = $input = $form->addText($name, $prefix . $name, strlen($max) + 1);
					if (isset($special[$name])) {
						$branch = $input->addConditionOn($form->getComponent($special[$name][0]), Form::EQUAL, $special[$name][1]);
					}
					$branch
						->addRule(Form::FILLED)
						->addRule(Form::NUMERIC)
						->addRule(Form::RANGE, NULL, [$min, $max]);
					if (isset($definition['unit'])) {
						$input->setOption('input-append', Html::el('span', array('class' => 'add-on'))->setText($this->translator->translate($definition['unit'])));
					}
					$input->getControlPrototype()->setClass('input-mini');
					break;
				case self::TYPE_RANGE_LIST:
					list($min, $max) = explode('-', $values, 2);
					$input = $form->addRadioList($name, $prefix . $name, range((int) $min, (int) $max));
					break;
				case self::TYPE_LIST:
					$input = $form->addRadioList($name, $prefix . $name, $this->escape($values, $name));
					if (isset($definition['special'])) {
						foreach ($definition['special'] as $value => $controls) {
							$special = array_merge($special, array_fill_keys($controls, array($name, $value)));
						}
					}
					break;
				case self::TYPE_SET:
					$translated = array();
					foreach ($values as $value) {
						$escaped = $this->escapeValue($value);
						$translated[$value] = $name . '-' . $escaped;
					}
					$input = $form->addCheckboxList($name, $prefix . $name, $translated);
					break;
			}
			if (NULL !== $input) {
				if (isset($definition['default'])) {
					$defaultValue = $type === self::TYPE_SET ? array_fill_keys($definition['default'], TRUE) : $definition['default'];
					$input->setDefaultValue($defaultValue);
				}
				if (isset($definition['description'])) {
					$input->setOption('description', $definition['description']);
				}
				if (isset($definition['readOnly'])) {
					$input->getControlPrototype()
						->readOnly = TRUE;
				}
			}
		}
		$form->setCurrentGroup();
		return $form;
	}

	public function setValues($form, $values)
	{
		foreach ($this->getParameters() as $name => $definition) {
			$type = $this->getType($definition);
			if ($type === self::TYPE_GROUP) {
				continue;
			}
			$id = 'select' === $type && isset($definition['id']);
			$name = $key;
			if ($id) {
				$name = $name . '_id';
			}
			if (self::TYPE_TEXTAREA === $type && isset($definition['locale'])) {
				foreach ($definition['locale'] as $locale) {
					$localeName = $name . '_' . $locale;
					if (isset($values[$localeName])) {
						$form[$localeName]->setValue($values[$key]);
					}
				}
			}
			if (!isset($values[$key])) {
				continue;
			}
			$help = self::TYPE_SET === $type ? array_fill_keys($values[$key], TRUE) : $values[$key];
			if ($id && is_array($help)) {
				$help = $help['id'];
			}
			$form[$name]->setValue($help);
		}
	}

	public function setDefaults($form, $defaults)
	{
		foreach ($this->getParameters() as $key => $definition) {
			$type = $this->getType($definition);
			if ($type === self::TYPE_GROUP) {
				continue;
			}
			$id = 'select' === $type && isset($definition['id']);
			$name = $key;
			if ($id) {
				$name = $name . '_id';
			}
			if (self::TYPE_TEXTAREA === $type && isset($definition['locale'])) {
				foreach ($definition['locale'] as $locale) {
					$localeName = $name . '_' . $locale;
					if (isset($defaults[$localeName])) {
						$form[$localeName]->setDefaultValue($defaults[$localeName]);
					}
				}
			}
			if (!isset($defaults[$key])) {
				continue;
			}
			$help = self::TYPE_SET === $type ? array_fill_keys($defaults[$key], TRUE) : $defaults[$key];
			if ($id && is_array($help)) {
				$help = $help['id'];
			}
			$form[$name]->setDefaultValue($help);
		}
	}

	private function getType(&$definition)
	{
		if (isset($definition['type'])) {
			return $definition['type'];
		}
		$values = $definition['values'];
		if (is_array($values)) {
			return self::TYPE_LIST;
		} else {
			list($min, $max) = explode('-', $values, 2);
			if ($max - $min < 10) {
				return self::TYPE_RANGE_LIST;
			} else {
				return self::TYPE_RANGE_INPUT;
			}
		}
	}

	public function getScriptVariables()
	{
		return array();
	}

	public function translateSets($values)
	{
		foreach (array_filter($this->getParameters(), function ($definition) {
			return isset($definition['set']) && $definition['set'];
		}) as $name => $definition) {
			$set = array();
			foreach ($definition['values'] as $value) {
				$key = $name . '_' . $this->escapeValue($value);
				if (isset($values[$key]) && $values[$key] === TRUE) {
					$set[] = $value;
				}
				unset($values[$key]);
			}
			$values[$name] = $set;
		}
		return $values;
	}

	protected function getParameters()
	{
		return $this->context->parameters[$this->name];
	}

	private function escapeValue($value)
	{
		return strtr($value , ' ', '_');
	}

	private function escape($array, $name)
	{
		$result = array();
		foreach ($array as $value) {
			$result[$value] = $name . '-' . $value;
		}
		return $result;
	}

	protected function fillSelectWithItems($model, $select)
	{
		$model = $this->modelManager->model($model);
		$resources = iterator_to_array($model->getForCurrentUser()->order('id DESC'));
		array_walk($resources, function (&$value, $key) use ($model) {
			$text = $model->outputCondensed($value);
			$value = Html::el('option', array('value' => $key))->setText($text);
		});
		$items = $select->getItems() + $resources;
		$select->setItems($items);
	}

}