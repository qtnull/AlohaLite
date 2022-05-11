
<?php
    // login.php: Login and token generation
    
    require_once('utility.php');

    // Construct the skeleton for the json response
    $response = [
        "status" => null
    ];

    // If not POST, just exit
    if ($_SERVER['REQUEST_METHOD'] !== "POST")
    { 
        $response["status"] = "failure";
        $response["message"] = "Client Internal error: Unsupported HTTP request method: " . $_SERVER['REQUEST_METHOD'];
        $response["error"] = "InvalidHTTPRequestMethod";
        SetHTTPStatusCode(400);
        header('Content-type: application/json');
        echo json_encode($response);
        exit(); 
    }
    
    // POST json validation
    $POST_body = file_get_contents("php://input");
    $decoded_request = json_decode($POST_body, true);
    if (json_last_error() != JSON_ERROR_NONE)
    {
        $response["status"] = "failure";
        $response["message"] = "Client Internal error: Invalid JSON";
        $response["error"] = "InvalidJSON";
        SetHTTPStatusCode(400);
        header('Content-type: application/json');
        echo json_encode($response);
        exit();
    }

    // JSON Validation
    if (    !isset($decoded_request["username"]) ||
            !isset($decoded_request["password"]) ||
            !is_string($decoded_request["username"]) ||
            !is_string($decoded_request["password"])
    )
    {
        $response["status"] = "failure";
        $response["message"] = "Client Internal error: Invalid JSON format: Username and/or password field(s) does not exist";
        $response["error"] = "InvalidJSONFormat";
        SetHTTPStatusCode(400);
        header('Content-type: application/json');
        echo json_encode($response);
        exit();
    }
    if ($decoded_request["username"] == "" || $decoded_request["password"] == "")
    {
        $response["status"] = "failure";
        $response["message"] = "Username and/or Password field(s) is empty";
        $response["error"] = "EmptyFields";
        SetHTTPStatusCode(406);
        header('Content-type: application/json');
        echo json_encode($response);
        exit();
    }

    // OPENING DATABASE CONNECTION

    require_once('userdb.php');
    if ($conn->connect_errno)
    {
        // User(data) server offline, takeover and respond with a failure
        $response["status"] = "failure";
        $response["message"] = "Server Internal error: User(data) database server is not online, please try again later";
        $response["error"] = "DatabaseDown";
        SetHTTPStatusCode(503);
        header('Content-type: application/json');
        echo json_encode($response);
        exit();
    }

    require_once('tokendb.php');

    // User credential validation
    $request_username = $decoded_request["username"];
    $request_password = $decoded_request["password"];

    if (!DB::UsernameExists($request_username))
    {
        $response["status"] = "failure";
        $response["message"] = "User does not exist";
        $response["error"] = "InvalidUsername";
        SetHTTPStatusCode(404);
        header('Content-type: application/json');
        echo json_encode($response);
        exit();
    }

    $database_hashed_password = DB::GetHashedPassword($request_username);
    if( password_verify($request_password, $database_hashed_password) )
    {
        try {
            $token = TokenDB::CreateToken($request_username);
        } catch (TokenServerNotAlive $e) {
            // Token server not Online, takeover and respond with a failure message
            $response["status"] = "failure";
            $response["message"] = "Server Internal error: Token server is not online, please try again later";
            $response["error"] = "TokenServerDown";
            SetHTTPStatusCode(503);
            header('Content-type: application/json');
            echo json_encode($response);
            exit();
        } catch (TokenCreationFailure $e)
        {
            // Redis mysteriously failed
            $response["status"] = "failure";
            $response["message"] = "Server Internal error: Failed to generate token, please try again later";
            $response["error"] = "TokenServerFailure";
            SetHTTPStatusCode(500);
            header('Content-type: application/json');
            echo json_encode($response);
            exit();
        }
        $response["status"] = "success";
        $response["token"] = $token;
        header('Content-type: application/json');
        echo json_encode($response);
    }
    else
    {
        $response["status"] = "failure";
        $response["message"] = "Incorrect password";
        $response["error"] = "InvalidPassword";
        SetHTTPStatusCode(403);
        header('Content-type: application/json');
        echo json_encode($response);
        exit();
    }
?>