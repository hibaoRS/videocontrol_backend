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


$response = InteractUtils::socketSendAndRead(CommonUtils::getSystemConfig()["ip"], CommonUtils::getSystemConfig()["port"], json_encode(
    array(
        "type" => "19"
    )
));
$productId = "";
if ($response) {
    $productId = json_decode($response)->uuid;
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
            "gateway" => "192.168.1.1",
            "mask" => "255.255.255.0",
        ),
        "eth1" => array(
            "ip" => "192.168.1.223",
            "gateway" => "192.168.1.1",
            "mask" => "255.255.255.0",
        ),
    ),

    //摄像头控制
    "camera_control" => array(
        "currCamera" => "1",
        "1" => array(
            "focal_length" => 0,
            "zoom_speed" => 2,
        ),
        "2" => array(
            "focal_length" => 0,
            "zoom_speed" => 2,
        ),
        "3" => array(
            "focal_length" => 0,
            "zoom_speed" => 2,
        ),
        "4" => array(
            "focal_length" => 0,
            "zoom_speed" => 2,
        ),
        "5" => array(
            "focal_length" => 0,
            "zoom_speed" => 2,
        ),
        "6" => array(
            "focal_length" => 0,
            "zoom_speed" => 2,
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
        "camera" => array(
            "student_closeUp" => "1",
            "teacher_closeUp" => "2",
            "student_panorama" => "3",
            "teacher_panorama" => "4",
            "board_closeUp" => "5",
            "custom" => "6",
        ),
        //ftp和互动直播
        "other" => array(
            "ftp" => array(
                "server" => "ftp://58.67.222.35",
                "port" => "21",
                "user" => "vision_ftp",
                "password" => "vision_upload",
                "on_demand_port" => "8085",
            ),
            "interact_live" => array(
                "serial_number" => $productId,
                "class_room_name" => "",
                "ip_address" => "",
                "picAddress" => "static/video_cover_default.jpg",
                "resource_platform_ip" => "58.67.222.35",
                "resource_platform_port" => "8080",
            )
        )
    ),

    /*"video_urls" => $video_urls*/
);