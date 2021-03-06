# AlohaLite
A simple, basic, RESTful authentication API and user data API.

# API Documentation

## POST `/aloha/session`
Takes username and password in json format, checks through a database, and if user exists and hashed password matches, returns a **token** with a limited lifetime. Essentially logging the user in.

Takes JSON as http body

Header requirements: none

Request
---
```json
{
    "username": "foo", // Takes User's login handle
    "password": "$uper$ecretPassw0rd" // User's password
}
```

Returns
---
If login successful
```json
{
    "status": "success",
    "token": "PretendThisToBeARandomToken", // Random token generated by the server
}
```

If login failed
```json
{
    "status": "failure",
    "message": "Incorrect username or password" // User friendly error message
}
```

## POST `/aloha/logout`
Revokes user's token validity, essentially logging the user out.

Header requirement: **token**

The header's token **MUST** match the json request body "revokeToken"'s token, else the API will return a 400 Bad Request

Request
---
```json
{
    "revokeToken": "PretendThisToBeARandomToken" // Valid token to be revoked
}
```

Retuns
---
If successful
```json
{
    "status": "success"
}
```

If failure
```json
{
    "status": "failure",
    "message": "The token was (already) invalid"
}
```

On bad request
```json
{
    "status": "failure",
    "message": "Header's token did not match request body's token"
}
```

## GET `/aloha/fetch/(query item)`
Queries the database for the column (query item) of the user.

Header requirement: **token**

Request
---
GET `/aloha/fetch/(query item)`

This will just return a json querying `(query item)` in the database in the user's row.

Examples: `/aloha/fetch/first_name`, `/aloha/fetch/last_name`

Returns
---
Successful response
```json
{
    "status": "success",
    "response": "DataFromDatabase"
}
```

On failure (The column does not exist in the database)
```json
{
    "status": "failure",
    "error": "NoSuchField",
    "message": "The database does not store such data"
}
```

Not authenticated (No token on the header)

Returns a 403 HTTP status code
```json
{
    "status": "failure",
    "error": "NotAuthenticated",
    "message": "You haven't logged in"
}