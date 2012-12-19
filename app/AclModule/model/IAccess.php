<?php

/**
 * Declaration of access control for resource
 * expected hierarchy model.access
 * 
 * $model->getAccess($resource)
 * 
 * $model->can<Action>($resource) => $model->getAccess($resource)->action();
 */
interface IAccess
{

	/**
	 * @param object $model
	 * @param object $resource
	 */
	function __construct($model, $resource);

	/**
	 * @param string $action 
	 * @return bool
	 */
	function can($action);

}