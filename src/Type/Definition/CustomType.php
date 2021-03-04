<?php

namespace GraphQL\Type\Definition;

use GraphQL\Language\AST\FieldDefinitionNode;

class CustomType
{
    public static function ParseConfig(string $name, string $path): array
    {
        $config = [];
        //check file
        if (file_exists($file = "$path.php")) {
            $config = require_once($file);
        }




        //field
        foreach (glob($path . "/*.php") as $p) {
            $field_name = pathinfo($p, PATHINFO_FILENAME);
            $config["fields"][$field_name] = require_once($p);
        }

        foreach (glob($path . "/*", GLOB_ONLYDIR) as $p) {
            $child = pathinfo($p, PATHINFO_FILENAME);

            $config["fields"][$child] = [
                "type" => self::Create($name . $child, self::ParseConfig($name . $child, $p)),
                "resolve" => function ($root) {
                    return $root;
                }
            ];
        }


        $fields = $config["fields"];
        $config["fields"] = function () use (&$fields) {

            $fs = [];
            foreach ($fields as $field_name => $field_value) {
                $fs[$field_name] = $field_value;

                //type
                if (is_string($fs[$field_name])) {
                    $fs[$field_name]["type"] = Custom::ParseOutputType($fs[$field_name]);
                } elseif (is_array($fs[$field_name])) {
                    if (is_string($fs[$field_name]["type"])) {
                        $fs[$field_name]["type"] = Custom::ParseOutputType($fs[$field_name]["type"]);
                    }
                }

                //args
                if (is_array($fs[$field_name])) {
                    if (is_string($fs[$field_name]["args"])) {
                        $fs[$field_name]["args"] = Custom::ParseArgument($fs[$field_name]["args"]);
                    } else {
                        foreach ($fs[$field_name]["args"] as $arg_name => $arg) {
                            $fs[$field_name]["args"][$arg_name] = is_string($arg) ? Custom::ParseInputType($arg) : $arg;
                        }
                    }
                }
                if (is_array($fs[$field_name])) {
                    if (is_string($fs[$field_name]["directives"])) {
                        $fs[$field_name]["astNode"] = new FieldDefinitionNode([
                            "directives" => FieldDirective::Parse($fs[$field_name]["directives"])
                        ]);

                    }
                }
            }
            return $fs;
        };



        return $config;
    }

    public static function Create(string $name, array $config)
    {
        if (Custom::$TYPES[$name]) {
            return Custom::$TYPES[$name];
        }
        $config["name"] = $name;
        return new ObjectType($config);
    }

    public function __callStatic($name, $arguments)
    {
        if (Custom::$TYPES[$name]) {
            return Custom::$TYPES[$name];
        }
        $config = self::ParseConfig($name, Custom::$ROOT . "/" . $name);
        $config["name"] = $name;
        return Custom::$TYPES[$name] = new ObjectType($config);
    }
}
