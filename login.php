<?php

$DB = new PDO("mysql:host=localhost;dbname=;charset=utf8", '', '');

class OAuthEacces
{
    public static function generateToken () {
        return substr(str_shuffle(MD5(microtime())), 0, 30);
    }
}

class SQl
{

    public static function initDataBase ()
    {
        $sqlClient =
            "CREATE TABLE client (

              client_id INT PRIMARY KEY AUTO_INCREMENT NOT NULL UNIQUE,
              client_ip VARCHAR(30),
              client_os VARCHAR(30),

              client_name VARCHAR(30), -- NOT NULL UNIQUE,
              password VARCHAR(30), -- NOT NULL UNIQUE,

              token VARCHAR(30) NOT NULL UNIQUE,
              status VARCHAR(30) -- NOT NULL UNIQUE
        )";

        $sqlPost =
            "CREATE TABLE posts (
              title VARCHAR(30),
              post VARCHAR(30),
              client_id INT,    
              FOREIGN KEY (client_id) REFERENCES client (client_id)
        )";

        global $DB;
        $db = $DB->prepare( $sqlClient )->execute();
        $db = $DB->prepare( $sqlPost )->execute();

        return $db;
    }

    public static function tokenVerification ()
    {
        if ( !empty( $_COOKIE['access_token'] ) )
        {
            $token = $_COOKIE['access_token'];

            // проверить токен
            global $DB;
            $db = $DB->prepare("SELECT * FROM client where token=:token");
            $db->bindParam(':token' , $token);
            $db->execute();
            $getToken = $db->fetchAll(PDO::FETCH_ASSOC);


            if ( !empty( $getToken[0]["token"] ) )
            {
                if ( $getToken[0]["token"] ==  $token ) return true;
                else return false;
            } else return false;
        } else return true;
    }

    public static function createClient ( $token )
    {
        global $DB;
        $db = $DB->prepare("INSERT INTO client (client_id ,token) VALUES (null, :token)");
        $db->bindParam( ':token', $token );
        $db->execute();
    }

    public static function fetchToken ( $token )
    {
        global $DB;
        $db = $DB->prepare("SELECT * FROM client where token=:token");
        $db->bindParam(':token' , $token);
        $db->execute();
        $getToken = $db->fetchAll(PDO::FETCH_ASSOC);

        return $getToken[0]['token'];
    }

    public static function update( $token, $set, $col)
    {
        $colName = 'client_' . $col;
        global $DB;
        $db = $DB->prepare("UPDATE client SET $colName=:$colName WHERE token=:token");
        $db->bindParam( ":$colName", $set );
        $db->bindParam( ':token', $token );
        $db->execute();
    }

    public static function getPosts( $token )
    {
        global $DB;
        $db = $DB->prepare("SELECT p.title, p.post FROM client c NATURAL JOIN posts p WHERE c.token = :token");
        $db->bindParam(':token' , $token);
        $db->execute();
        $getToken = $db->fetchAll(PDO::FETCH_ASSOC);

        return $getToken;
    }
}

class View
{
    public static function danger ( $message )
    {
        echo '<p style="color: crimson">'. $message .'</p>';
    }

    public static function fine ( $message )
    {
        echo '<p style="color: #53c328">'. $message .'</p>';
    }
}

class Client
{
    public  static function getIp ( )
    {
        $client     = @$_SERVER['HTTP_CLIENT_IP'];
        $forward    = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote     = @$_SERVER['REMOTE_ADDR'];

        if (filter_var($client, FILTER_VALIDATE_IP)) $ip = $client;
        elseif (filter_var($forward, FILTER_VALIDATE_IP)) $ip = $forward;
        else $ip = $remote;

        return $ip;
    }

    public  static function getOs ()
    {
        $agent = $_SERVER['HTTP_USER_AGENT'];
        $os = null;

        if (  strpos($agent, 'Android') ) $os = 'Android';
        else $os = 'Linux';
        if (  strpos($agent, 'Windows') ) $os = 'Windows';
        return $os;
    }

    function getStatus ( $bind )
    {
    }
}

class KernelLogicApp
{

    function init ()
    {

        /**
         * проверить наличее токена
         *
         * @если есть
         * --проверить токен совпадает ли
         *
         * @если нет
         * --создать токен в бд
         * --инициализировать токен в куки
         * --найти токен в бд и обновить поле client_ip и остольны
         *
         *
         */


        if ( !empty( $_COOKIE['access_token'] ) )
        {
            if ( SQl::tokenVerification() )
            {
                View::fine('successfully Verification');
            }
            else View::danger('sorry token not found');

        } else {
            $token = OAuthEacces::generateToken();

            SQl::createClient( $token );

            $token = SQl::fetchToken( $token );


            setcookie('access_token' ,$token);

            if ( SQl::tokenVerification() ) View::fine('your create and issued token');
            else View::danger('sorry token not foun');

            SQl::update( $token, Client::getIp(), 'ip' );
            SQl::update( $token, Client::getOs(), 'os' );
        }
    }
}



