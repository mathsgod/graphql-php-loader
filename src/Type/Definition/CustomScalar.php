<?php

namespace GraphQL\Type\Definition;

class CustomScalar
{
    private static $TYPES = [];

    public static function JSON()
    {
        if (self::$TYPES["JSON"]) {
            return self::$TYPES["JSON"];
        }

        self::$TYPES["JSON"] = new CustomScalarType([
            "name" => "JSON",
            "serialize" => function ($value) {
                return $value;
            }
        ]);
        return self::$TYPES["JSON"];
    }
}
