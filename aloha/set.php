<?php
    // fetch.php: fetch user data in database
    require_once('utility.php');
    require_once('tokendb.php');

    // Construct the skeleton for the json response
    $response = [
        "status" => null
    ];

    // If not GET, just exit
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
    if (    !isset($decoded_request["field"]) ||
            !is_string($decoded_request["field"]) ||
            !isset($decoded_request["value"])
    )
    {
        $response["status"] = "failure";
        $response["message"] = "Client Internal error: Invalid JSON format: field or value not set";
        $response["error"] = "InvalidJSONFormat";
        SetHTTPStatusCode(400);
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

    try {
        $token_data = TokenDB::GetTokenData($token);
        $token_username = $token_data['username'];
        DB::ModifyUserField($token_username, $decoded_request["field"], $decoded_request["value"]);
        $response["status"] = "success";
        header('Content-type: application/json');
        echo json_encode($response);
        exit();
    } catch (UserDataFieldDoesNotExist $e) {
        $response["status"] = "failure";
        $response["message"] = "Client Internal error: userdata update failed: requested field not found";
        $response["error"] = "InvalidUserDataField";
        SetHTTPStatusCode(400);
        header('Content-type: application/json');
        echo json_encode($response);
        exit();
    } catch (InvalidFieldDataType $e) {
        $response["status"] = "failure";
        $response["message"] = "Client Internal error: userdata update failed: requested field was found, but in wrong data type";
        $response["error"] = "InvalidUserDataFieldDataType";
        SetHTTPStatusCode(400);
        header('Content-type: application/json');
        echo json_encode($response);
        exit();
    }
?>