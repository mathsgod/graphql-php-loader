<?php

namespace GraphQL\Type\Definition;

class CustomScalar
{


    public static function JSON()
    {
        if (Custom::$TYPES["JSON"]) {
            return Custom::$TYPES["JSON"];
        }

        Custom::$TYPES["JSON"] = new CustomScalarType([
            "name" => "JSON",
            "serialize" => function ($value) {
                return $value;
            }
        ]);
        return Custom::$TYPES["JSON"];
    }
}
