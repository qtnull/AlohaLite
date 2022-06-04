<?php
// Database interface management

class DatabaseNotAlive extends Exception {}
class UsernameDoesNotExist extends Exception {}
class UserDataFieldDoesNotExist extends Exception {}
class InvalidFieldDataType extends Exception {}
class UsernameAlreadyExists extends Exception {}

interface SimplifiedDatabaseInterface
{
    // User identity related
    public static function UsernameExists($username);
    public static function GetHashedPassword($username);
    public static function GetUserID($username);
    public static function GetUserName($id);
    // User data related
    public static function FetchUserData($username, $field);
    //public static function CreateField(string $fieldname, string $type);
    //public static function RemoveField(string $fieldname);
    public static function GetAvailableFields();
    public static function ModifyUserField($username, $field, $value);
    // User management related
    public static function AddUser($username, $password);
    //public static function ResetPassword($username, $newPassword);
    public static function DeleteUser($username);
    // Administrator related
    public static function IsAdmin($username);
    public static function QueryUserData($username);
    public static function ListUser();
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
        if ($conn->connect_error) { throw new DatabaseNotAlive(); }
        
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
        if ($conn->connect_error) { throw new DatabaseNotAlive(); }
        
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
        if ($conn->connect_error) { throw new DatabaseNotAlive(); }

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
        if ($conn->connect_error) { throw new DatabaseNotAlive(); }

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

    public static function ModifyUserField($username, $field, $value)
    {
        global $conn;

        // Check if PHP has connected to the database, if not, throw DatabaseNotAlive exception
        if ($conn->connect_error) { throw new DatabaseNotAlive(); }

        $db_fields = self::GetAvailableFields();
        if ( !array_key_exists($field, $db_fields) ) { throw new UserDataFieldDoesNotExist(); }
        
        $uid = self::GetUserID($username);
        $fieldDataType = $db_fields[$field];
        $sql = "UPDATE `data` SET `$field` = ? WHERE `id` = ?";
        switch ($fieldDataType) {
            case 'int(11)':
                if ( gettype($value) != "integer" ) { throw new InvalidFieldDataType(); }
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ii', $value, $uid);
                $stmt->execute();
                $stmt->close();
                return;
            
            case 'text':
                if ( gettype($value) != "string" ) { throw new InvalidFieldDataType(); }
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('si', $value, $uid);
                $stmt->execute();
                $stmt->close();
                return;
        }
    }

    public static function GetAvailableFields()
    {
        global $conn;

        // Check if PHP has connected to the database, if not, throw DatabaseNotAlive exception
        if ($conn->connect_error) { throw new DatabaseNotAlive(); }

        $sql = "SELECT `COLUMN_NAME`, `COLUMN_TYPE` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA` = 'aloha' AND `TABLE_NAME` = 'data'";
        $result = $conn->query($sql);
        $fields = array();
        while ($row = $result->fetch_assoc())
        {
            $fields[$row['COLUMN_NAME']] = $row['COLUMN_TYPE'];
        }
        return $fields;
    }

    public static function AddUser($username, $password)
    {
        if (self::UsernameExists($username)) { throw new UsernameAlreadyExists(); }
        global $conn;
        $sql = "INSERT INTO `users` (`username`, `password`, `admin`) VALUES (?, ?, 0)";
        $stmt = $conn->prepare($sql);
        $hashed_pw = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bind_param('ss', $username, $hashed_pw);
        $stmt->execute();
        $stmt->close();
    }

    public static function DeleteUser($username)
    {
        if ( !(self::UsernameExists($username)) ) { throw new UsernameDoesNotExist(); }
        global $conn;
        $sql = "DELETE FROM `users` WHERE `username` = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->close();
    }

    public static function IsAdmin($username)
    {
        if ( !(self::UsernameExists($username)) ) { throw new UsernameDoesNotExist(); }
        global $conn;
        $sql = "SELECT `admin` FROM `users` WHERE `username` = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $adminStatus = $row['admin'];
        $stmt->close();

        if ($adminStatus === 1) { return true; } else { return false; }
    }

    public static function QueryUserData($username)
    {
        if ( !(self::UsernameExists($username)) ) { throw new UsernameDoesNotExist(); }
        global $conn;
        $data = array();
        $available_fields = self::GetAvailableFields();
        foreach ($available_fields as $key => $value) {
            $field_value = self::FetchUserData($username, $key);
            $data[$key] = $field_value;
        }
        return $data;
    }

    public static function ListUser()
    {
        global $conn;

        // Check if PHP has connected to the database, if not, throw DatabaseNotAlive exception
        if ($conn->connect_error) { throw new DatabaseNotAlive(); }

        $sql = "SELECT `username` FROM `users`";
        $result = $conn->query($sql);
        $userlist = array();
        while ($row = $result->fetch_assoc())
        {
            array_push($userlist, $row['username']);
        }
        return $userlist;
    }
}
?>