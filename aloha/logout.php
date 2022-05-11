<?php
    // Logout.php: Logging out and destroy token
    require_once('utility.php');
    require_once('tokendb.php');

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

    // Header checking
    if ( !isset($_SERVER["HTTP_ALOHA_TOKEN"]) )
    {
        $response["status"] = "failure";
        $response["message"] = "Client Internal error: sign out failed, Aloha-Token is not found in the request";
        $response["error"] = "TokenNotSet";
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
    if (    !isset($decoded_request["revokeToken"]) ||
            !is_string($decoded_request["revokeToken"])
    )
    {
        $response["status"] = "failure";
        $response["message"] = "Client Internal error: Invalid JSON format: field revokeToken does not exist or is in the wrong data type";
        $response["error"] = "InvalidJSONFormat";
        SetHTTPStatusCode(400);
        header('Content-type: application/json');
        echo json_encode($response);
        exit();
    }

    // Header token == request token
    if ( $_SERVER["HTTP_ALOHA_TOKEN"] != $decoded_request["revokeToken"] )
    {
        $response["status"] = "failure";
        $response["message"] = "Client Internal error: header token mismatch. Header " . $_SERVER["HTTP_ALOHA_TOKEN"] . ", request: " . $decoded_request["revokeToken"];
        $response["error"] = "HeaderTokenMismatch";
        SetHTTPStatusCode(400);
        header('Content-type: application/json');
        echo json_encode($response);
        exit();
    }

    // Validate token
    try {
        $tokenRevoked = TokenDB::DestroyToken($decoded_request["revokeToken"]);
        if ($tokenRevoked)
        {
            $response["status"] = "success";
            header('Content-type: application/json');
            echo json_encode($response);
        }
        else
        {
            $response["status"] = "failure";
            $response["message"] = "Token is invalid or was already revoked";
            $response["error"] = "InvalidToken";
            SetHTTPStatusCode(403);
            header('Content-type: application/json');
            echo json_encode($response);
            exit();
        }
    } catch (TokenServerNotAlive $e) {
        $response["status"] = "failure";
        $response["message"] = "Server Internal error: Token server is not online, please try again later";
        $response["error"] = "TokenServerDown";
        SetHTTPStatusCode(503);
        header('Content-type: application/json');
        echo json_encode($response);
        exit();
    }
?>