<?php

namespace UserModule\SettingsModule;

use Nette;

abstract class BasePresenter extends \UserModule\BasePresenter {

	/**
	 * @var \FormFactory
	 */
	protected $formFactory;

	protected $userModel;

	public function __construct(Nette\DI\Container $context = NULL, \FormFactory $formFactory)
	{
		parent::__construct($context);
		$this->formFactory = $formFactory;
		$this->userModel = $this->context->userModel;
	}


	protected function startup()
	{
		parent::startup();
		$this->authorize();
	}


	public function beforeRender()
	{
		parent::beforeRender();

		$menu = $this['menu'];
		$config = $this->context->parameters['settings'];
		if ($homepage = $config['homepage']) {
			$this->addNavigationItem($homepage, $menu, 'setupHomepage');
			$this->addBreadcrumb($homepage);
		}
		if ($items = $config['items']) {
			foreach ($config['items'] as $item) {
				$this->addNavigationItem($item, $menu);
			}
		}
	}


	public function createComponentMenu()
	{
		$navigation = new \Navigation\Navigation;
		$navigation->setMenuTemplate(dirname(__FILE__) . '/../templates/@menu.latte');
		return $navigation;
	}


	protected $currentUser;

	public function getCurrentUser()
	{
		if (NULL === $this->currentUser) {
			$this->currentUser = $this->userModel->getById($this->getUser()->getId());
		}
		return $this->currentUser;
	}

}
