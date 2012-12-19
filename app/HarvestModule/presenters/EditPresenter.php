<?php

namespace HarvestModule;

use DOMDocument,
	DOMXpath;

final class EditPresenter extends DetailPresenter {


	public function beforeRender()
	{
		parent::beforeRender();

		$this->addBreadcrumb(array(
			'__label' => 'Edit',
			'link' => 'this'
		));
	}


	public function actionDefault()
	{
		if ($this->record->form === NULL) {
			$this->loadValuesFromXml();
		}
		$defaults = unserialize($this->record->form);
		$this['form']->setDefaults($defaults);
	}


	public function actionLoadValuesFromXml()
	{
		$this->loadValuesFromXml();
		$this->redirect('default');
	}


	private function loadValuesFromXml()
	{
		$document = new DOMDocument;
		$document->load($this->harvest->getXmlFilename($this->record));
		$xpath = new DOMXpath($document);
		$values = array();
		$setting = $this->settings->getSetting($this->record->setting);
		$form = $setting->getForm();
		foreach ($form['xmlns'] as $ns => $URI) {
			$xpath->registerNamespace($ns, $URI);
		}
		foreach ($form['items'] as $name => $path) {
			$result = $xpath->query($path);
			if ($result->length == 0) {
				continue;
			}
			$result = $result->item(0);
			if ($result) {
				$values[$name] = (string) $result->nodeValue;
			}
		}
		$this->record->form = serialize($values);
		$this->record->update();

		$this->flashMessage('Values were loaded from XML.', 'info');
	}


	public function createComponentForm()
	{
		$form = $this->formFactory->createForm();
		$form->addText('name', 'Name')
			->getControlPrototype()->class('input-xxlarge');
		$form->addTextarea('description', 'Description')
			->getControlPrototype()->class('input-xxlarge');
		$form->addText('operator', 'Operator')
			->getControlPrototype()->class('input-xxlarge');
		$form->addText('organization', 'Organization')
			->getControlPrototype()->class('input-xxlarge');
		$form->addText('audience', 'Audience')
			->getControlPrototype()->class('input-xxlarge');
		$form->addTextarea('note', 'Note')
			->getControlPrototype()->class('input-xxlarge');
		$form->addSubmit('update', 'Update')
			->getControlPrototype()->class('btn-info');
		$form->onSuccess[] = $this->onSuccess;
		return $form;
	}


	public function onSuccess($form)
	{
		$values = $form->getValues(TRUE);
		unset($values['update']);
		array_walk($values, function (&$value) {
			$value = trim($value);
		});
		$this->record->form = serialize(array_filter($values));
		$this->record->update();
		$this->redirect('Generate:');
	}


	public function renderDefault()
	{
		$firstAction = reset($this->template->actions);
		if ($firstAction[0] === 'Edit:') {
			array_shift($this->template->actions);
		}
		$this->template->harvest = $this->record;
	}


	private $formFactory;

	public function injectFormFactory(\FormFactory $formFactory)
	{
		$this->formFactory = $formFactory;
	}


	private $settings;

	public function injectSettings(Config\Settings $settings)
	{
		$this->settings = $settings;
	}

}
