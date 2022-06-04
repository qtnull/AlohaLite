<?php
    // fetch.php: fetch user data in database
    require_once('../utility.php');
    require_once('../tokendb.php');

    // Construct the skeleton for the json response
    $response = [
        "status" => null
    ];

    // If not GET, just exit
    if ($_SERVER['REQUEST_METHOD'] !== "GET")
    { 
        $response["status"] = "failure";
        $response["message"] = "Client Internal error: Unsupported HTTP request method: " . $_SERVER['REQUEST_METHOD'];
        $response["error"] = "InvalidHTTPRequestMethod";
        SetHTTPStatusCode(400);
        header('Content-type: application/json');
        echo json_encode($response);
        exit(); 
    }

    // If no Aloha-token is set in the header, return 401
    // Header checking
    if ( !isset($_SERVER["HTTP_ALOHA_TOKEN"]) )
    {
        $response["status"] = "failure";
        $response["message"] = "You are not authenticated";
        $response["error"] = "TokenNotSet";
        SetHTTPStatusCode(401);
        header('Content-type: application/json');
        echo json_encode($response);
        exit();
    }

    $token = $_SERVER["HTTP_ALOHA_TOKEN"];

    try {
        $tokenValid = TokenDB::IsTokenValid($token);
    } catch (TokenServerNotAlive $e) {
        $response["status"] = "failure";
        $response["message"] = "Server Internal error: Token server is not online, please try again later";
        $response["error"] = "TokenServerDown";
        SetHTTPStatusCode(503);
        header('Content-type: application/json');
        echo json_encode($response);
        exit();
    }

    if ( !$tokenValid )
    {
        $response["status"] = "failure";
        $response["message"] = "You are not authenticated";
        $response["error"] = "TokenInvalid";
        SetHTTPStatusCode(403);
        header('Content-type: application/json');
        echo json_encode($response);
        exit();
    }

    // OPENING DATABASE CONNECTION
    require_once('../userdb.php');
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

    // Check if user has administrative privileges
    $token_data = TokenDB::GetTokenData($token);
    $token_username = $token_data['username'];
    if ( !(DB::IsAdmin($token_username)) )
    {
        $response["status"] = "failure";
        $response["message"] = "You don't have administrative privileges";
        $response["error"] = "NotAdmin";
        SetHTTPStatusCode(403);
        header('Content-type: application/json');
        echo json_encode($response);
        exit();
    }

    try {
        $requested_userdata = DB::ListUser();
    } catch (DatabaseNotAlive $e) {
        // User(data) server offline, takeover and respond with a failure
        $response["status"] = "failure";
        $response["message"] = "Server Internal error: User(data) database server is not online, please try again later";
        $response["error"] = "DatabaseDown";
        SetHTTPStatusCode(503);
        header('Content-type: application/json');
        echo json_encode($response);
        exit();
    }

    $response["status"] = "success";
    $response["response"] = $requested_userdata;
    header('Content-type: application/json');
    echo json_encode($response);
    exit();
?>