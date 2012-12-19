<?php

namespace Resource;

use Model;

class Group extends Model\Base
{

	private $cache = array();

	/**
	 * @param object $type
	 * @return object[]
	 */
	function getByType($type)
	{
		if (!isset($this->cache[$type->name])) {
			$groups = array();
			foreach ($type->related($this->getTableName()) as $group) {
				$this->cache[$type->name][$group->name] = $group;
			}
			$this->cache[$type->name] = $groups;
		}
		return $this->cache[$type->name];
	}

	/**
	 * @param object $type
	 * @param string $name
	 * @return object
	 */
	function getByTypeAndName($type, $name)
	{
		$this->getByType($type);
		if (!isset($this->cache[$type->name][$name])) {
			$data = array(
				'resource_type_id' => $type->id,
				'name' => $name,
			);
			$group = $this->getTable()->where($data)->limit(1)->fetch();
			if (!$group) {
				$group = $this->getTable()->insert($data);
			}
			$this->cache[$type->name][$name] = $group;
		}
		return $this->cache[$type->name][$name];
	}

}
