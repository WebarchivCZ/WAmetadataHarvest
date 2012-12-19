<?php

namespace Model;

class IAction
{

    function getSupportedActions();

    function getActionPermissions();

    function getAction();

    function create($data);

    function replace($resource, $data);

    function update($resource, $data);

    function delete($resource);


    function filter($data);

    function defaults($data);

    function validate($action, $data);

    function process($data);

}
