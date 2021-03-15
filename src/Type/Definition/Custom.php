<?php

namespace GraphQL\Type\Definition;



use Exception;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\TypeNode;
use GraphQL\Language\Parser;
use GraphQL\Utils\AST;
use GraphQL\Utils\Utils;

class Custom
{
    public static $ROOT = "graphql";
    public static $TYPES = [];

    public static function InternalBuildOutputType(string $type)
    {
        $a = Type::getAllBuiltInTypes();
        if ($t = $a[$type]) {
            return $t;
        }

        if ($type == "JSON") {
            return CustomScalar::JSON();
        }
        return CustomType::$type();
    }


    public static function InternalBuildInputType(string $type)
    {
        $a = Type::getAllBuiltInTypes();
        if ($t = $a[$type]) {
            return $t;
        }

        if ($type == "JSON") {
            return CustomScalar::JSON();
        }

        return CustomInputType::$type();
    }

    public static function ParseOutputType(string $type)
    {
        $doc_node = Parser::parse("type Query{ a:$type }", ["noLocation" => true]);
        $field = $doc_node->definitions[0]->fields[0];
        return self::InternalBuildWrappedOuputType($field->type);
    }

    public static function ParseInputType(string $type)
    {
        $doc_node = Parser::parse("type Query{ a:$type }", ["noLocation" => true]);
        $field = $doc_node->definitions[0]->fields[0];

        return self::InternalBuildWrappedInputType($field->type);
    }

    public static function BuildInputType($ref)
    {
        if (is_string($ref)) {
            return self::InternalBuildInputType($ref);
        }

        return self::InternalBuildInputType($ref->name->value);
    }

    public static function BuildOutputType($ref)
    {
        if (is_string($ref)) {
            return self::InternalBuildOutputType($ref);
        }

        return self::InternalBuildOutputType($ref->name->value);
    }

    private static function BuildWrappedType(Type $innerType, TypeNode $inputTypeNode)
    {
        if ($inputTypeNode->kind == NodeKind::LIST_TYPE) {
            return Type::listOf(self::BuildWrappedType($innerType, $inputTypeNode->type));
        }
        if ($inputTypeNode->kind == NodeKind::NON_NULL_TYPE) {
            $wrappedType = self::BuildWrappedType($innerType, $inputTypeNode->type);
            return Type::nonNull(NonNull::assertNullableType($wrappedType));
        }
        return $innerType;
    }

    /**
     * @param TypeNode $typeNode
     * @return Type|InputType
     * @throws Error
     */
    public static function InternalBuildWrappedInputType(TypeNode $typeNode)
    {
        $typeDef = self::BuildInputType(self::GetNamedTypeNode($typeNode));
        return self::BuildWrappedType($typeDef, $typeNode);
    }

    public static function InternalBuildWrappedOuputType(TypeNode $typeNode)
    {
        $typeDef = self::BuildOutputType(self::GetNamedTypeNode($typeNode));
        return self::BuildWrappedType($typeDef, $typeNode);
    }

    /**
     * @param TypeNode|ListTypeNode|NonNullTypeNode $typeNode
     * @return TypeNode
     */
    private static function GetNamedTypeNode(TypeNode $typeNode)
    {
        $namedType = $typeNode;
        while ($namedType->kind === NodeKind::LIST_TYPE || $namedType->kind === NodeKind::NON_NULL_TYPE) {
            $namedType = $namedType->type;
        }
        return $namedType;
    }

    public static function ParseArgument(string $args): array
    {

        $doc_node = Parser::parse("type Query{ a($args):string }", ["noLocation" => true]);


        $field = $doc_node->definitions[0]->fields[0];
        $arguments = $field->arguments;

        $config = Utils::keyValMap(
            $arguments,
            function ($value) {
                return $value->name->value;
            },
            function ($value) {
                // Note: While this could make assertions to get the correctly typed
                // value, that would throw immediately while type system validation
                // with validateSchema() will produce more actionable results.
                //$type = $this->internalBuildWrappedType($value->type);

                $type = Custom::InternalBuildWrappedInputType($value->type);
                $config = [
                    'name' => $value->name->value,
                    'type' => $type,
                    //  'description' => $value->description->value,
                    'astNode' => $value
                ];

                if (isset($value->defaultValue)) {
                    $config['defaultValue'] = $value->defaultValue->value;
                }
                return $config;
            }
        );

        return $config;
    }
}
