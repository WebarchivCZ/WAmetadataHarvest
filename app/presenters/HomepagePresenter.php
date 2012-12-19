<?php

class HomepagePresenter extends BasePresenter {


	public function actionDefault()
	{
		$this->redirect(':Harvest:Browse:');
	}

}