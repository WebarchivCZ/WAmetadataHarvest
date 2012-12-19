<?php

namespace Model;

class Rest
{


        /*
Actions based on REST
    item
        - GET <show> => <exists> ? <command> : <error>
        - PUT <update> => <exists> ? <command> : <error>
        - POST <create> => [<unique> ?] command> [: <error>] => <id> => GET <id>
        - DELETE <delete> => <exists> ? <command> : <error>
    set
        - GET <list> => get all resources
        - PUT <replace> => DELETE, @item.POST
        - POST => item.POST
        - DELETE <truncate> => GET@item.DELETE

         */

    static function getActionFromHttpMethod($method)
    {
        switch ($method) {
            case 'GET':
                return Action::DISPLAY;
            case 'POST':
                return Action::CREATE;
            case 'PUT':
                return Action::REPLACE;
            case 'PARTIAL':
                return Action::UPDATE;
            case 'DELETE':
                return Action::DELETE;
            default:
                throw new \Exception("Unsupported method '$method'.");
        }
    }

}