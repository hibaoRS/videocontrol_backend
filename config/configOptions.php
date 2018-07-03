<?php
/**
 * Created by PhpStorm.
 * User: 10324
 * Date: 2017/12/4
 * Time: 16:23
 */

return array(
    "audio" => array(
        "samplerate" => array(
            "16KHz" => "16000",
            "24KHz" => "24000",
            "32KHz" => "32000",
            "48KHz" => "48000",
        ),
        "channels" => array(
            "单声道" => "1",
            "双声道" => "2"
        )
    ),

    "video" => array(
        "size_type" => array(
            "1080P(1920 * 1080)" => "0",
            "720P(1280 * 720)" => "1"
        ),
        "norm_type" => array(
            "PAL(25fps)" => "0",
            "NTSC(30fps)" => "1"
        ),
        "rc_type" => array(
            "CBR " => "0",
            "VBR" => "1"
        ),
        "bitrate_type" => array(
            "1M" => "0",
            "2M" => "1",
            "4M" => "2",
            "6M" => "3",
            "8M" => "4",
//            "10M" => "5",
//            "12M" => "6"
        ),

        "adv7842_type" => array(
            "HDMI(分辨率自动匹配)" => "0",
            "VGA(1920x1080)" => "1",
            "VGA(1280x720)" => "2"
        )
    ),

    "serial" => array(
        "camera_serial_dev" => array(
            "/dev/ttyUSB0" => "/dev/ttyUSB0",
            "/dev/ttyUSB1" => "/dev/ttyUSB1",
            "/dev/ttyUSB2" => "/dev/ttyUSB2",
            "/dev/ttyUSB3" => "/dev/ttyUSB3"
        ),
        "serial_dev" => array(
            "/dev/ttyUSB0" => "/dev/ttyUSB0",
            "/dev/ttyUSB1" => "/dev/ttyUSB1",
            "/dev/ttyUSB2" => "/dev/ttyUSB2",
            "/dev/ttyUSB3" => "/dev/ttyUSB3"
        ),
        "bitrate_type" => array(
            "4800" => "0",
            "9600" => "1",
            "115200" => "2"
        )

    ),

    "main_screen" => array(
        "size_type" => array(
            "1920x1080(1080P)" => "0",
            "1280x720(720P)" => "1",
            "1280x1024" => "2",
            "1440x900" => "3",
        ),
    ),

    "rtmp" => array(
        "bitrate_type" => array(
            "1M" => "0",
            "2M" => "1"
        ),
        "size_type" => array(
            "960X540" => "4",
            "640X360" => "5"
        )
    ),
    "camera" => array(
        "value" => array(
            "1" => "1",
            "2" => "2",
            "3" => "3",
            "4" => "4",
        )
    )
);




