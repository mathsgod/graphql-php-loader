<?php

namespace GraphQL\Type\Definition;

use Closure;
use Exception;
use GraphQL\Language\AST\FieldDefinitionNode;
use ReflectionFunction;

class CustomType
{

    public static function ParseFieldArgument($argument)
    {
        if (is_string($argument)) {
            return Custom::ParseArgument($argument);
        }

        $args = [];
        foreach ($argument as $arg_name => $arg) {
            $args[] = [
                "name" => $arg_name,
                "type" => is_string($arg) ? Custom::ParseInputType($arg) : $arg
            ];
        }
        return $args;
    }

    protected static function ParseFieldDirective($directive)
    {
        return  FieldDirective::Parse($directive);
    }

    public static function ParseField($field)
    {

        // parse as a function
        if ($field instanceof Closure) {
            $reflection = new ReflectionFunction($field);

            $config = [];


            $return_type = $reflection->getReturnType();
            switch ($return_type) {
                case "int":
                    $config["type"] = Custom::ParseOutputType("Int!");
                    break;
                case "bool":
                    $config["type"] = Custom::ParseOutputType("Boolean!");
                    break;
                case "string":
                    $config["type"] = Custom::ParseOutputType("String!");
                    break;
                default:
                    $config["type"] = Custom::ParseOutputType("String");
            }

            $config["resolve"] = $field;

            return $config;
        }

        //prase as a string
        if (is_string($field)) {
            return Custom::ParseOutputType($field);
        }

        //parse as a array
        $config = $field;
        $config["type"] = Custom::ParseOutputType($field["type"]);

        $config["args"] = self::ParseFieldArgument($config["args"]);

        if ($config["directives"]) {
            $config["astNode"] = new FieldDefinitionNode([
                "directives" => self::ParseFieldDirective($config["directives"])
            ]);
        }
        return $config;
    }

    public static function ParseConfig(string $name, string $path): ObjectType
    {
        //echo $name, "\n";
        $config = [];
        $config["name"] = $name;
        //check file
        //$config["fields"] = [];

        if (file_exists($file = "$path.php")) {
            $root = require_once($file);
            $config["fields"] = $root["fields"] ?? [];
        }

        //field
        foreach (glob($path . "/*.php") as $p) {
            $field_name = pathinfo($p, PATHINFO_FILENAME);

            $fieldDef = require_once($p);
            if (!is_array($fieldDef)) {
                throw new Exception("$p is not return array");
            }

            if (!$fieldDef["type"]) {
                throw new Exception("type must be defined in field define file $p");
            }

            $config["fields"][$field_name] = $fieldDef;
        }

        foreach (glob($path . "/*", GLOB_ONLYDIR) as $p) {
            $child = pathinfo($p, PATHINFO_FILENAME);

            $c = [];
            $c["type"] = self::ParseConfig($name . $child,  $p);

            
            // check if field file exist 
            if(!$config["fields"][$child]){
                $c["resolve"] = function ($root) {
                    return $root;
                };
            }else{
                $c["resolve"]=$config["fields"][$child]["resolve"];
            }
            
            $config["fields"][$child] = $c;
        }

        $fields =  [];
        foreach ($config["fields"] as $name => $value) {
            $fields[$name] = $value;
        }

        $config["fields"] = function () use (&$fields) {
            $ret = [];
            foreach ($fields as $name => $field) {
                $ret[$name] = self::ParseField($field);
            }
            return $ret;
        };
        return self::Create($config);
    }

    public function __callStatic($name, $arguments)
    {
        if (Custom::$TYPES[$name]) {
            return Custom::$TYPES[$name];
        }
        return self::ParseConfig($name, Custom::$ROOT . "/" . $name);
    }

    public static function Create(array $config)
    {
        if (Custom::$TYPES[$config["name"]]) {
            return Custom::$TYPES[$config["name"]];
        }

        return Custom::$TYPES[$config["name"]] = new ObjectType($config);
    }
}
