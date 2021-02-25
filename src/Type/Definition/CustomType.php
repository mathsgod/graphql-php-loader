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

            //fields process
            $fields = [];
            foreach ($config["fields"] as $field_name => $type) {
                $fields[$field_name] = is_string($type) ? Custom::ParseOutputType($type) : $type;
            }

            $config["fields"] = $fields;
        }


        //field
        foreach (glob(Custom::$ROOT . "/$name/*.php") as $p) {
            $field_name = pathinfo($p, PATHINFO_FILENAME);
            $stub = require_once($p);

            //arguments
            if (is_string($args = $stub["args"])) {
                $stub["args"] = Custom::ParseArgument($args);
            } else {

                $args = [];
                foreach ($stub["args"] as $name => $arg) {
                    $args[$name] = is_string($arg) ? Custom::ParseInputType($arg) : $arg;
                }
                $stub["args"] = $args;
            }




            if (is_string($stub["type"])) {
                $stub["type"] = Custom::ParseOutputType($stub["type"]);
            }

            $config["fields"][$field_name] = $stub;
        }
        $config["name"] = $name;
        return self::$TYPES[$name] = new ObjectType($config);
    }
}
