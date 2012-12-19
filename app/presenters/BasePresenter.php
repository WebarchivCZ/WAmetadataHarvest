<?php

use Nette\Http,
	Nette\Utils\Html;

abstract class BasePresenter extends CommonPresenter
{

	/** @persistent */
	public $locale;

	protected function startup()
	{
		parent::startup();
		$this->context->getByType('Nette\Localization\ITranslator')->setLocale($this->locale);
	}

	public function createComponentNavigation()
	{
		$navigation = new Navigation\Navigation;
		$navigation->setMenuTemplate(dirname(__DIR__) . '/templates/@menu.latte');
		return $navigation;
	}

	/**
	 * Add item to navigation
	 * @return Navigation\NavigationNode
	 */
	protected function addNavigationItem($item, $parent = NULL, $method = 'add')
	{
		if (!isset($item['label']) && !isset($item['__label'])) {
			throw new InvalidArgumentException('Navigation item must have label.');
		}
		if (!isset($item['url']) && !isset($item['link'])) {
			throw new InvalidArgumentException('Navigation item must have url or link.');
		}

		if (isset($item['role'])) {
			$roles = is_array($item['role']) ? $item['role'] : (array) $item['role'];
			if (!array_intersect($this->getUser()->getRoles(), $roles)) {
				return NULL;
			}
		}

		if (NULL === $parent) {
			$parent = $this['navigation'];
		}

		$label = isset($item['__label']) ? $this->translate($item['__label']) : $item['label'];
		$url = isset($item['link']) ? $this->link($item['link']) : $item['url'];

		$node = $parent->$method($label, $url);
		if (isset($item['items']) && count($item['items'])) {
			foreach ($item['items'] as $subitem) {
				$this->{__FUNCTION__}($subitem, $node);
			}
		}
		return $node;
	}

	public function createComponentBreadcrumbs()
	{
		$breadcrumbs = new Navigation\Navigation;
		$breadcrumbs->setBreadcrumbsTemplate(dirname(__DIR__) . '/templates/@breadcrumbs.latte');
		return $breadcrumbs;
	}

	public function addBreadcrumb($item, $current = TRUE)
	{
		static $last = NULL;
		$breadcrumbs = $this['breadcrumbs'];
		if (NULL == $last) {
			$last = $breadcrumbs;
		}
		$breadcrumb = $this->addNavigationItem($item, $last);
		if ($current) {
			$breadcrumbs->setCurrentNode($breadcrumb);
		}
		$last = $breadcrumb;
	}

	public function beforeRender()
	{
		$this->template->setTranslator($this->getContext()->getByType('Nette\Localization\ITranslator'));

		$navigation = $this['navigation'];
		$config = $this->getContext()->parameters['navigation'];
		if ($homepage = $config['homepage']) {
			$this->addNavigationItem($homepage, $navigation, 'setupHomepage');
			$this->addBreadcrumb($homepage);
		}
		if ($items = $config['items']) {
			foreach ($config['items'] as $item) {
				$this->addNavigationItem($item);
			}
		}

		$this->template->lang = substr($this->locale, 0, 2);
		$this->template->locale = $this->locale;
		$this->template->availableLocales = $this->getContext()->parameters['locale']['available'];
		$this->template->staticMessages = $this->staticMessages;
		$this->template->projectName = $this->getContext()->parameters['name'];
	}

	public function translate()
	{
		$args = func_get_args();
		static $translate = NULL;
		if (NULL === $translate) {
			$translate = new Nette\Callback($this->context->getByType('Nette\Localization\ITranslator'), 'translate');
		}
		return $translate->invokeArgs($args);
	}

	/**
	 * Displays message that will appear for one time. Message will be translated
	 * @param string $message
	 * @param array $params
	 * @param string $type
	 */
	public function flashMessage($message, $params = array(), $type = 'info')
	{
		if (!is_array($params)) {
			$type = $params;
			$params = array();
		}
		$message = $this->prepareMessage($message, $params);
		return parent::flashMessage($message, $type);
	}

	private $staticMessages = array();
	/**
	 * Displays message that will not go away. Message will be translated
	 * @param string $message
	 * @param array $params
	 * @param string $type
	 */
	public function staticMessage($message, $params = array(), $type = 'info')
	{
		if (!is_array($params)) {
			$type = $params;
			$params = array();
		}
		$message = $this->prepareMessage($message, $params);
		$this->staticMessages[] = (object) array(
				'message' => $message,
				'type' => $type,
		);
	}

	private function prepareMessage($message, $params)
	{
		if ($params) {
			array_unshift($params, $message);
			$message = call_user_func_array(callback($this, 'translate'), $params);
		} else {
			$message = $this->translate($message);
		}
		return $message;
	}

	public function createComponentUserMenu()
	{
		$user = $this->getUser();
		$menu = new Menu;
		$menu->injectUser($user);
		$menu->injectTranslator($this->getContext()->getByType('Nette\Localization\ITranslator'));
		$items = $this->getContext()->parameters['user']['menu'];
		if ($user->isLoggedIn()) {
			$items = array(
				array(
					'label' => Html::el()->add($this->translate(ucfirst($user->getIdentity()->group) . ': <strong>%s</strong>', $user->getIdentity()->username)),
					'items' => $items['loggedIn'],
				)
			);
		} else {
			$items = $items['guest'];
		}
		$menu->setItems($items);
		$element = $menu->getElementPrototype();
		$element->addClass('pull-right');
		$element->setId('user');
		return $menu;
	}

}
