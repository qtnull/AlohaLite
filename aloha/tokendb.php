<?php

class TokenServerNotAlive extends Exception {}
class TokenCreationFailure extends Exception {}
class InvalidToken extends Exception {}

interface SimplifiedTokenManager
{
    public static function CreateToken($username);
    public static function DestroyToken($username);
    public static function IsTokenValid($token);
    public static function GetTokenData($token);
}

class TokenDB implements SimplifiedTokenManager
{
    private static $token_duration = 600;
    public static function CreateToken($username)
    {
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
        } catch (Exception $e) {
            throw new TokenServerNotAlive();
        }
        $bytes = random_bytes(20);
        $token = bin2hex($bytes);
        $query_reply = $redis->hSet($token, 'username', $username);
        if ($query_reply == false) { throw new TokenCreationFailure(); }
        $query_reply = $redis->hSet($token, 'id', DB::GetUserID($username));
        if ($query_reply == false) { throw new TokenCreationFailure(); }
        $query_reply = $redis->expire($token, self::$token_duration);
        if ($query_reply == false) { throw new TokenCreationFailure(); }
        return $token;
    }
    public static function IsTokenValid($token)
    {
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
        } catch (Exception $e) {
            throw new TokenServerNotAlive();
        }
        $query_reply = $redis->exists($token);
        if ($query_reply == 1) { return true; } else { return false; } 
    }
    public static function GetTokenData($token)
    {
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
        } catch (Exception $e) {
            throw new TokenServerNotAlive();
        }
        if (self::IsTokenValid($token))
        {
            $resp = array('id' => null, 'username' => null);
            $resp['username'] = $redis->hGet($token, 'username');
            $resp['id'] = $redis->hGet($token, 'id');
            return $resp;
        }
        else
        {
            throw new InvalidToken();
        }
    } 
    public static function DestroyToken($token)
    {
        // Will return true if the deletion was successful, returns false when the token is already invalid
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
        } catch (Exception $e) {
            throw new TokenServerNotAlive();
        }
        if (self::IsTokenValid($token))
        {
            $query_reply = $redis->del($token);
            if ($query_reply == 1) { return true; } else { return false; }
        }
        else
        {
            return false;
        }
    }
    public static function GetTTL($token)
    {
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
        } catch (Exception $e) {
            throw new TokenServerNotAlive();
        }
        return $redis->ttl($token);
    }
}

?>