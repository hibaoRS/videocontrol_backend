<?php
/**
 * Created by PhpStorm.
 * User: 10324
 * Date: 2018/1/30
 * Time: 2:33
 */

error_reporting(E_ALL);
session_start();

require __DIR__ . "/../utils/Msg.php";

header("Access-Control-Allow-Origin:" . (array_key_exists("HTTP_ORIGIN", $_SERVER) ? $_SERVER["HTTP_ORIGIN"] : "*"));
header("Access-Control-Allow-Credentials:true");
header('Content-type: application/json;charset=utf-8');


$action = null;
if (array_key_exists("action", $_REQUEST)) {
    $action = $_REQUEST["action"];
}