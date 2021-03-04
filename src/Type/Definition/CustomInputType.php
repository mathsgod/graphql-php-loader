<?php

namespace GraphQL\Type\Definition;

class CustomInputType
{

    public function __callStatic($name, $arguments)
    {
        if (Custom::$TYPES[$name]) {
            return Custom::$TYPES[$name];
        }

        $config = [];
        //check file
        if (file_exists($file = Custom::$ROOT . "/$name.php")) {
            $config = require_once($file);
        }

        //field
        foreach (glob(Custom::$ROOT  . "/$name/*.php") as $p) {
            $field_name = pathinfo($p, PATHINFO_FILENAME);
            $config["fields"][$field_name] = require_once($p);
        }

        foreach ($config["fields"]  as $field_name => $field_value) {

            $field = $field_value;
            if (is_string($field)) {
                $field = Custom::ParseInputType($field_value);
            } elseif (is_array($field)) {
                if (is_string($field["type"])) {
                    $field["type"] = Custom::ParseInputType($field["type"]);
                }
            }
            $config["fields"][$field_name] = $field;
        }

        $config["name"] = $name;
        return Custom::$TYPES[$name] = new InputObjectType($config);
    }
}
