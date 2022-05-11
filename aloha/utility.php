<?php
    function SetHTTPStatusCode($code)
    {
        switch ($code) {
            case 400:
                $sapi_type = php_sapi_name();
                if (substr($sapi_type, 0, 3) == 'cgi')
                    header("Status: 400 Bad Request");
                else
                    header("HTTP/1.1 400 Bad Request");
                break;
            case 401:
                $sapi_type = php_sapi_name();
                if (substr($sapi_type, 0, 3) == 'cgi')
                    header("Status: 401 Unauthorized");
                else
                    header("HTTP/1.1 401 Unauthorized");
                break;
            case 406:
                $sapi_type = php_sapi_name();
                if (substr($sapi_type, 0, 3) == 'cgi')
                    header("Status: 406 Not Acceptable");
                else
                    header("HTTP/1.1 406 Not Acceptable");
                break;
            case 403:
                $sapi_type = php_sapi_name();
                if (substr($sapi_type, 0, 3) == 'cgi')
                    header("Status: 403 Forbidden");
                else
                    header("HTTP/1.1 403 Forbidden");
                break;
            case 404:
                $sapi_type = php_sapi_name();
                if (substr($sapi_type, 0, 3) == 'cgi')
                    header("Status: 404 Not Found");
                else
                    header("HTTP/1.1 404 Not Found");
                break;
            case 500:
                $sapi_type = php_sapi_name();
                if (substr($sapi_type, 0, 3) == 'cgi')
                    header("Status: 500 Internal Server Error");
                else
                    header("HTTP/1.1 500 Internal Server Error");
                break;
            case 503:
                $sapi_type = php_sapi_name();
                if (substr($sapi_type, 0, 3) == 'cgi')
                    header("Status: 503 Service Unavailable");
                else
                    header("HTTP/1.1 503 Service Unavailable");
                break;
        }
    }
?>
