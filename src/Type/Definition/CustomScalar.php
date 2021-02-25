<?php

namespace GraphQL\Type\Definition;

class CustomType
{
    public static $TYPES = [];

    public function __callStatic($name, $arguments)
    {

        if (self::$TYPES[$name]) {
            return self::$TYPES[$name];
        }

        $config = [];
        //check file
        if (file_exists($file =  Custom::$ROOT . "/$name.php")) {
            $config = require_once($file);
        }


        //field
        foreach (glob(Custom::$ROOT . "/$name/*.php") as $p) {
            $field_name = pathinfo($p, PATHINFO_FILENAME);
            $stub = require_once($p);

            //arguments
            if (is_string($args = $stub["args"])) {
                $stub["args"] = Custom::ParseArgument($args);
            }

            $config["fields"][$field_name] = $stub;
        }
        $config["name"] = $name;
        return self::$TYPES[$name] = new ObjectType($config);
    }
}
