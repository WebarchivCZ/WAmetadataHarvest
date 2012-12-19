<?php

use Nette\Application\UI\Control,
	Nette\Application\UI\Presenter,
	Nette\Security\User,
	Nette\Localization\ITranslator,
	Nette\Utils\Html;

class Menu extends Control {

	public function __construct($name = NULL)
	{
		parent::__construct(NULL, $name);
		$this->monitor('Nette\Application\UI\Presenter');
	}

	private $items;

	public function setItems($items)
	{
		$this->items = $this->processItems($items);
	}

	protected function processItems($items)
	{
		$result = array();
		$positioned = array('first' => array(), 'last' => array());
		foreach ($items as $item) {
			if ($item = $this->processItem($item)) {
				if (isset($item->position)) {
					if (!isset($positioned[$item->position])) {
						throw new InvalidArgumentException("Unsupported item position: '$item->position'.");
					}
					$positioned[$item->position][] = $item;
				} else {
					$result[] = $item;
				}
			}
		}
		return array_merge($positioned['first'], $result, $positioned['last']);
	}


	/**
	 * Add item to navigation
	 * @return Navigation\NavigationNode
	 */
	protected function processItem($item)
	{
		if (isset($item['separator']) || !count($item)) {
			return (object) ($item + array('separator' => TRUE));
		}
		if (!isset($item['label']) && !isset($item['__label'])) {
			throw new InvalidArgumentException('Item must have label.');
		}
		if (!isset($item['url']) && !isset($item['link']) && !isset($item['items'])) {
			throw new InvalidArgumentException('Item must have url or link.');
		}

		if (isset($item['role'])) {
			$roles = is_array($item['role']) ? $item['role'] : (array) $item['role'];
			if (!array_intersect($this->user->getRoles(), $roles)) {
				return NULL;
			}
		}

		if (isset($item['items']) && count($item['items'])) {
			$item['items'] = $this->processItems($item['items']);
		}
		return (object) $item;
	}

	private $user;

	public function injectUser(User $user)
	{
		$this->user = $user;
	}


	private $translator;

	public function injectTranslator(ITranslator $translator)
	{
		$this->translator = $translator;
	}


	private $elementPrototype;

	public function getElementPrototype()
	{
		if (NULL === $this->elementPrototype) {
			$this->elementPrototype = Html::el('ul', array('class' => 'nav'));
		}
		return $this->elementPrototype;
	}


	public function createTemplate($class = NULL)
	{
		$template = parent::createTemplate($class);
		if (NULL !== $this->translator) {
			$template->setTranslator($this->translator);
		}
		$template->element = $this->getElementPrototype();
		return $template;
	}


	private $filename;

	public function setTemplateFilename($filename)
	{
		$this->filename = $filename;
	}


	public function render()
	{
		$template = $this->createTemplate()
			->setFile($this->filename ?: __DIR__ . '/menu.latte');
		$template->items = $this->items;
		$template->user = $this->user;
		$template->render();
	}

}
