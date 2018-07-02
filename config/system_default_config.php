<?php
/**
 * Created by PhpStorm.
 * User: 10624
 * Date: 2017/12/6
 * Time: 14:11
 */

$video_urls = array();
for ($i = 6; $i >= 0; $i--) {
    if ($i > 3) {
        $video_urls[$i] = "http://192.168.1.223:8080/live/" . $i . ".flv";
    } else {
        $video_urls[$i] = "http://192.168.1.222:8080/live/" . $i . ".flv";
    }
//    $video_urls[$i] = "http://127.0.0.1:8080/live/" . $i . ".flv";
//    $video_urls[$i] = "http://192.168.1.222:8080/live/" . $i . ".flv";

}

return array(
    //当前布局
    "layout" => "layout_default",

    //直播流url
    "video_urls" => $video_urls,

    //课程名
    "recordName" => "课程",

    //课程分段时长
    "sectionTime" => 45,

    //是否显示应用管理
    "showAppManage" => 0,

    //是否显示应用管理控制面板
    "showControlAppManage" => 0,


    //是否显示主页面面板
    "mainPanel" => array(
        "enabled" => 0,
        "width" => "35rem",
        "height" => "720px",
    ),

    //备播url
    "standbyUrls" => array(),

    //ip
    "ips" => array(
        "eth0" => array(
            "ip" => "192.168.1.222",
            "mask" => "255.255.255.0",
        ),
        "eth1" => array(
            "ip" => "192.168.1.223",
            "mask" => "255.255.255.0",
        ),
    ),

    "configs" => array(
        "audio" => array(
            "samplerate" => "48000",
            "channels" => "2",
        ),
        "video" => array(
            "size_type" => "0",
            "norm_type" => "1",
            "rc_type" => "0",
            "bitrate_type" => "2",
            "adv7842_type" => "0"
        ),

        "serial" => array(
            "camera_serial_dev" => "/dev/ttyUSB0",
            "serial_dev" => "/dev/ttyUSB1",
            "bitrate_type" => "1"
        ),

        "misc" => array(
            "app_port" => "5000",
            "board_port" => "5001",
            "rtmp" => "1",
            "auto_switch" => "1",
        ),
        "rtmp" => array(
            "server_url" => "rtmp://127.0.0.1/live",
            "size_type" => "4",
            "bitrate_type" => "1"
        ),
        "main_screen" => array(
            "size_type" => "0"
        ),

    ),

    /*"video_urls" => $video_urls*/
);