<?php

namespace AdminModule;

abstract class BasePresenter extends \UserModule\BasePresenter {


	protected function startup()
	{
		parent::startup();
		$this->authorize('admin');
	}


	public function beforeRender()
	{
		parent::beforeRender();

		$menu = $this['menu'];
		$config = $this->context->parameters['admin'];
		if ($homepage = $config['homepage']) {
			$this->addNavigationItem($homepage, $menu, 'setupHomepage');
			$this->addBreadcrumb($homepage);
		}
		if ($items = $config['items']) {
			foreach ($items as $item) {
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


	public function formatLayoutTemplateFiles()
	{
		return array(
			__DIR__ . '/../templates/@layout.latte'
		);
	}

}
