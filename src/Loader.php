<?php

namespace GraphQL;

use GraphQL\Type\Definition\Custom;
use GraphQL\Type\Definition\CustomType;

class Loader
{
    public function __construct(string $path = "graphql")
    {
        Custom::$ROOT = $path;
    }

    public function queryType()
    {
        return CustomType::Query();
    }

    public function mutationType()
    {
        return CustomType::Mutation();
    }

    public function subscriptionType()
    {
        return CustomType::Subscription();
    }
}
