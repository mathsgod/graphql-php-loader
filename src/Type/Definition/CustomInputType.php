<?php

namespace GraphQL\Type\Definition;

class CustomInputType
{
    public function __callStatic($name, $arguments)
    {

        $config = [];
        //check file
        if (file_exists($file = Custom::$ROOT . "/$name.php")) {
            $config = require_once($file);
        }


        //field
        foreach (glob(Custom::$ROOT  . "/$name/*.php") as $p) {
            $field_name = pathinfo($p, PATHINFO_FILENAME);
            $stub = require_once($p);
            $config["fields"][$field_name] = $stub;
        }

        $config["name"] = $name;



        return new InputObjectType($config);
    }
}
