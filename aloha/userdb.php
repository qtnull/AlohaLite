<?php
// Database interface management

class DatabaseNotAlive extends Exception {}
class UsernameDoesNotExist extends Exception {}
class UserDataFieldDoesNotExist extends Exception {}

interface SimplifiedDatabaseInterface
{
    public static function UsernameExists($username);
    public static function GetHashedPassword($username);
    public static function GetUserID($username);
    public static function GetUserName($id);
    public static function FetchUserData($username, $field);
    //public static function ModifyUserData($username, $field, $value);
}

define('DB_SERVER', "localhost");
define('DB_USERNAME', "aloha_user");
define('DB_PASSWORD', "changeme");
define('DB_NAME', "aloha");
$conn = @new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

class DB implements SimplifiedDatabaseInterface
{
    // private static $SQLStatementForUpdate = array(); // $SQLStatementForUpdate[$field]

    public static function UsernameExists($username)
    {
        global $conn;

        // Check if PHP has connected to the database, if not, throw DatabaseNotAlive exception
        if ($conn === false) { throw new DatabaseNotAlive(); }
        
        $sql = "SELECT id FROM users WHERE username = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1)
        {
            return true;
        }
        else
        {
            return false;
        }
        
        $stmt->close();
    }

    public static function GetHashedPassword($username)
    {
        global $conn;

        // Check if PHP has connected to the database, if not, throw DatabaseNotAlive exception
        if ($conn === false) { throw new DatabaseNotAlive(); }
        
        $sql = "SELECT password FROM users WHERE username = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows != 1) { throw new UsernameDoesNotExist(); }
        $stmt->bind_result($hashed_password);
        $stmt->fetch();
        $stmt->close();
        return $hashed_password;
    }

    public static function GetUserID($username)
    {
        global $conn;
        
        // Check if PHP has connected to the database, if not, throw DatabaseNotAlive exception
        if ($conn === false) { throw new DatabaseNotAlive(); }

        $sql = "SELECT id FROM users WHERE username = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows != 1) { throw new UsernameDoesNotExist(); }
        $stmt->bind_result($id);
        $stmt->fetch();
        $stmt->close();
        return $id;
    }

    public static function GetUserName($id)
    {
        global $conn;

        // Check if PHP has connected to the database, if not, throw DatabaseNotAlive exception
        if ($conn === false) { throw new DatabaseNotAlive(); }

        $sql = "SELECT username FROM users WHERE id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows != 1) { throw new UsernameDoesNotExist(); }
        $stmt->bind_result($username);
        $stmt->fetch();
        $stmt->close();
        return $username;
    }

    public static function FetchUserData($username, $field)
    {
        global $conn;

        // Check not required since GetUserID() already checks for it
        $uid = self::GetUserID($username);
        $sql = "SELECT * FROM data WHERE id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $result = $stmt->get_result();
        $all_data_field = $result->fetch_assoc();
        if ( !isset($all_data_field[$field]) ) { throw new UserDataFieldDoesNotExist(); }
        $data = $all_data_field[$field];
        $stmt->close();
        return $data;
    }
}
?>