<?php


return [
    
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
    "
    
];
