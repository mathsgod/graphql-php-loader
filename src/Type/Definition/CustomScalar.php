<?php

namespace GraphQL\Type\Definition;

class CustomScalar
{

    public static function JSON()
    {
        return new CustomScalarType([
            "name" => "JSON",
            "serialize" => function ($value) {
                return $value;
            }
        ]);
    }
}
