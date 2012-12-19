<?php

namespace User\Resource;

use Model;

class Group extends Model\Base
{

    /**
     * Detect if user belongs to resource group
     *
     * @param object $type
     * @param object $resource
     * @param object $user
     * @param string $group
     * @return bool
     */
    function contains($type, $resource, $user, $group)
    {
        $user = $this->getUser($user);
        if (!$user) {
            return FALSE;
        }
        $groups = $this->getByTypeAndResource($type, $resource);
        return isset($groups[$group]) && in_array($user->id, $groups[$group]);
    }

    function add($type, $resource, $user, $group)
    {
        $user = $this->getUser($user);
        if (!$this->contains($type, $resource, $user, $group)) {
            $group = $this->model('resource.group')->getByTypeAndName($type, $group);
            $this->getTable()->insert(array(
                'resource_id' => $resource->id,
                'user_id' => $user->id,
                'resource_group_id' => $group->id,
            ));
            return $this->cache[$type->name][$resource->id][$group->name][] = $user->id;
        }
        return isset($groups[$group]) && in_array($user->id, $groups[$group]);
    }

    function remove($type, $resource, $user, $group)
    {
        $user = $this->getUser($user);
        if ($this->contains($type, $resource, $user, $group)) {
            $group = $this->model('resource.group')->getByTypeAndName($type, $group);
            $this->getTable()
                ->where('resource_id', $resource->id)
                ->where('user_id', $user->id)
                ->where('resource_group_id', $group->id)
                ->delete();
            $key = array_search($user->id, $this->cache[$type->name][$resource->id][$group->name]);
            unset($this->cache[$type->name][$resource->id][$group->name][$key]);
        }
    }

    function ids($type, $resource, $group)
    {
        $groups = $this->getByTypeAndResource($type, $resource);
        return isset($groups[$group]) ? $groups[$group] : array();
    }

    private $cache = array();

    /**
     * Get all assigned groups for resource
     * @param object $type
     * @param object $resource
     * @return id[name][]
     */
    function getByTypeAndResource($type, $resource)
    {
        $typeName = $type->name;
        if (!isset($this->cache[$typeName])) {
            $this->cache[$typeName] = array();
        }
        if (!isset($this->cache[$typeName][$resource->id])) {
            $grouped = array();
            $groups = $this->getTable()
                ->select('resource_group.name, GROUP_CONCAT(user_id) ids')
                ->where('resource_group.resource_type_id', $type->id)
                ->where('resource_id', $resource->id)
                ->group('resource_group.name');
            foreach ($groups as $group) {
                $grouped[$group->name] = explode(',', $group->ids);
            }
            $this->cache[$typeName][$resource->id] = $grouped;
        }
        return $this->cache[$typeName][$resource->id];
    }

    function getUser($user = NULL)
    {
        if ($user === NULL) {
            return $this->model('user')->getById($this->user->getId());
        } else {
            return $user;
        }
    }

}
