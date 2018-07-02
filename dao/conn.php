<?php
/**
 * Created by PhpStorm.
 * User: 10324
 * Date: 2017/12/4
 * Time: 16:15
 */

$config= require __DIR__ . "/../config/config.php";

//判断数据库是否存在
$isInited=true;
if(!file_exists($config["DB_NAME"])){
    $isInited=false;
}

//连接数据库
$db=new SQLite3($config["DB_NAME"]);
if(!$db){
    die("连接数据库异常");
}

//数据库不存在则进行初始化
if(!$isInited){
    init();
}

/**
 * 初始化数据库
 */
function init(){
    global $db;

    $file=fopen(__DIR__."/../config/videocontrol.sql","r");
    $sql="";
    while(!feof($file)){
        $sql.=fgets($file);
    }
    fclose($file);
    $db->exec($sql);

    if($db->lastErrorCode()){
        echo $db->lastErrorMsg();
    }
}