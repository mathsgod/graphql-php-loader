[![PHP Composer](https://github.com/mathsgod/graphql-php-loader/actions/workflows/php.yml/badge.svg?branch=main)](https://github.com/mathsgod/graphql-php-loader/actions/workflows/php.yml)

# graphql-php-loader

A simple loader to generate Schema for webonyx/graphql-php

## Usage

Use file structure to create and design graphql schema.

1. Create a folder "graphql" at your document root.
2. Create a folder "Query" in "graphql" folder.
3. Now you can create field for Query

### Init
```php

use GraphQL\Loader;
use GraphQL\Type\Schema;

$loader=new Loader();

$config = SchemaConfig::create()
    ->setQuery($loader->queryType())
    ->setMutation($loader->mutationType());

$schema = new Schema($config);

```

### Example 

#### Simple query
Create a file hello.php in "graphql/Query" folder
```
graphql
+-- Query
|  +-- hello.php
```

Query/hello.php
```php
return [
    "type"=>"String",
    "resolve"=>function($root,$args,$context){
        return "world!";
    }
];
```
It equivalent to build to following schema
```gql
type Query{
    hello:String
}
```

#### Custom type
If you want to use custom object type, just create type file at graphql folder

```
graphql
+-- Query
|  +-- me.php
+-- User.php
```

Query/me.php
```php
return [
    "type"=>"User",
    "resolve"=>function($root,$args,$context){
        return $context->me; //return object
    }
];
```

User.php
```php
return [
    "fields"=>[
        "first_name"=>"String",
        "last_name"=>"String"
    ]
];
```
It equivalent to build to following schema
```gql
type Query{
    me:User
}

type User{
    first_name:String
    last_name:String
}
```

#### Fields of custom type
If you want to create custom fields for custom object type, just create folder for the custom object

##### example
User has multiple phone number

graphql/User.php
```php
return [
    "fields"=>[
        "first_name"=>"String",
        "last_name"=>"String"
    ]
];
/* 
no need create phone in fields,
by create file phone.php in User folder, it auto generate fields in User type
*/
```

create phone.php in folder "User"

graphql/User/phone.php
```php
return [
    "type"=>"[String]",
    "resolve"=>function($user,$args,$context){
        return $user->getPhones(); //return multi phone
    }
];
```

```
graphql
+-- Query
|  +-- me.php
+-- User
|  +-- phone.php
+-- User.php
```
It equivalent to build to following schema
```gql
type Query{
    me:User
}

type User{
    first_name:String
    last_name:String
    phone:[String]
}
```


### Sub folder structure

```
graphql
+-- Query
|  +-- User
|      +-- list.php
|  +-- Invoice
|      +-- list.php
+-- User.php
+-- Invoice.php
```

now you can query by following
```gql
query{
    User{
        list{
            first_name
            last_name
        }
    }
    Invoice{
        list{
            invoice_no
        }
    }
}
```




