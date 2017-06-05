<?php


return [
    'introspectionQuery' => "
        query IntrospectionQuery {
            __schema {
                queryType { name }
                mutationType { name }
                types {
                    ...FullType
                }
                directives {
                    name
                    description
                    args {
                        ...InputValue
                    }
                    onOperation
                    onFragment
                    onField
                }
            }
        }

        fragment FullType on __Type {
            kind
            name
            description
            fields {
                name
                description
                args {
                    ...InputValue
                }
                type {
                    ...TypeRef
                }
                isDeprecated
                deprecationReason
            }
            inputFields {
                ...InputValue
            }
            interfaces {
                ...TypeRef
            }
            enumValues {
                name
                description
                isDeprecated
                deprecationReason
            }
            possibleTypes {
                ...TypeRef
            }
        }

        fragment InputValue on __InputValue {
            name
            description
            type { ...TypeRef }
            defaultValue
        }
        
        fragment TypeRef on __Type {
            kind
            name
            ofType {
                kind
                name
                ofType {
                    kind
                    name
                    ofType {
                        kind
                        name
                    }
              }
            }
        }
    ",
    'hello' =>  "
        query hello{hello}
    ",
      
    'singleObject' =>  "
        query user {
            user(id:\"2\") {
                id
                email
                email2
                photo(size:ICON){
                    id
                    url
                }
                firstName
                lastName

            }
        }
    ",
    'multiObject' =>  "
        query multiObject {
            user(id: \"2\") {
                id
                email
                photo(size:ICON){
                    id
                    url
                }
            }
            stories(after: \"1\") {
                id
                author{
                    id
                }
                body
            }
        }
    ",
    'repeatObject' =>  "
        query repeatObject {
            user(id: \"2\") {
                id
                email
            }
            stories(after: \"1\") {
                id
                author
                body
            }
        }
    ",
    'userModel'=>"
        query userModel{
            userModel(id: \"1001\") {
                id
                email
            }
        }
    ",
    'updateObject' =>  "
        mutation updateUserPwd{
            updateUserPwd(id: \"1001\", password: \"123456\") {
                id,
                username
            }
        }
    ",
    'mutationValidate' => "
        mutation updateUserPwd{
            updateUserPwd(id: \"1001\",password: \"123456\") {
                id,
                email
            }
        }
    ",
    'multiQuery' => "
        query hello{hello}
        query userModel{
            userModel(id: \"1001\") {
                id
                email
            }
        }
    ",
];
