<?php
/**
 * Created by PhpStorm.
 * User: 10324
 * Date: 2017/12/6
 * Time: 14:05
 */

require __DIR__ . "/../utils/CommonUtils.php";


//初始化设置直播ip
//$video_urls = array();
//for ($i = 6; $i >= 0; $i--) {
//    if ($i == 5) {
//        $video_urls[$i] = "http://192.168.1.223:8080/live/" . $i . ".flv";
//    } else {
//        $video_urls[$i] = "http://192.168.1.222:8080/live/" . $i . ".flv";
//    }
//}
//$allConfigs = CommonUtils::readConfig();
//$allConfigs->video_urls = $video_urls;
//CommonUtils::writeConfig($allConfigs);

//设置ip
if (PHP_OS == "Linux") {
    foreach (CommonUtils::readConfig()->ips as $dev => $ipInfo) {
        exec("ifconfig '$dev' '$ipInfo->ip' netmask '$ipInfo->mask'", $result, $code);
    }
}


//初始化录制状态
CommonUtils::initRecordLiveState();


error_reporting(0);

$address = CommonUtils::getSystemConfig()["my_ip"];
//$address = "192.168.1.101";
$port = CommonUtils::getSystemConfig()["my_port"];
$qt_port = CommonUtils::getSystemConfig()["qt_port"];
$qt_ip = CommonUtils::getSystemConfig()["qt_ip"];


if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) == false) {
    echo "socket_create() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\r\n";
    return;
}

if (socket_bind($sock, $address, $port) === false) {
    echo "socket_bind() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\r\n";
    return;
}


if (socket_listen($sock, 5) === false) {
    echo "socket_listen() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\r\n";
    return;
}

do {
    if (($msgsock = socket_accept($sock)) === false) {
        echo "socket_accept() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\r\n";
        break;
    }

    if (false === ($buff = socket_read($msgsock, 2048, PHP_NORMAL_READ))) {
        file_put_contents(__DIR__ . "/log.txt", socket_strerror(socket_last_error($msgsock)));
        echo "socket_read() failed: reason:" . socket_strerror(socket_last_error($msgsock)) . "\r\n";
    }

    if (!$buff = trim($buff)) {
        continue;
    }
    file_put_contents(__DIR__ . "/signal_states.json", $buff);
    socket_close($msgsock);
} while (true);

socket_close($sock);