<?php

namespace HarvestModule\AdminModule;

use Nette\Caching\Cache;

final class CachePresenter extends \AdminModule\BasePresenter {


	public function createComponentForm()
	{
		$form = $this->formFactory->createForm();
		$form->addProtection();
		$form->addSubmit('purge', 'Purge Cache')
			->getControlPrototype()->class('btn-warning');
		$form->onSuccess[] = $this->onSuccess;
		return $form;
	}


	public function onSuccess($form)
	{
		$cache = $this->getContext()->getService('harvest.cache');
		foreach (array('archives', 'files', 'webArchive') as $namespace) {
			$cache->derive($namespace)->clean(array(
				Cache::ALL => TRUE,
			));
		}
		$this->flashMessage('Cache was successfully purged.', 'success');
		$this->redirect('Dashboard:');
	}


	private $formFactory;

	public function injectFormFactory(\FormFactory $formFactory)
	{
		$this->formFactory = $formFactory;
	}

}