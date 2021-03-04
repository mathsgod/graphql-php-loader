<?php

namespace GraphQL\Type\Definition;

use GraphQL\Language\Parser;

class FieldDirective
{

    public static function Parse($s)
    {

        if (is_string($s)) {
            $doc_node = Parser::parse('type Query{ a:String @' . $s . '}', ["noLocation" => true]);
            return $doc_node->definitions[0]->fields[0]->directives;
        }
    }
}
