<?php
/**
 * Created by PhpStorm.
 * User: 10324
 * Date: 2017/12/4
 * Time: 16:23
 */

return array(
    "scene" => new ArrayObject(array(
        "教师特写" => "0",
        "学生特写" => "1",
        "教师全景" => "2",
        "学生全景" => "3",
        "板书特写" => "4",
        "电脑画面" => "5",
        "主画面" => "6"
    )),

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
        "resolution" => array(
            "1920x1080(1080P)" => "0",
            "1600x900" => "1",
            "1440x900" => "2",
            "1366x768" => "3",
            "1280x720(720P)" => "4",
            "960x540" => "5",
            "800x600" => "6",
            "640x480" => "7",
        ),
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
            "HDMI" => "0",
            "VGA" => "1",
        ),
        "record" => array(
            "mode" => array(
                "双屏拼接" => "2",
                "三屏拼接" => "3",
                "四屏拼接1" => "4",
                "四屏拼接2" => "5",
                "五屏拼接" => "6",
                "六屏拼接1" => "7",
                "六屏拼接2" => "8",
            )
        )
    ),

    "serial" => array(
        "camera_serial_dev" => array(
            "COM2" => "/dev/ttyUSB0",
            "COM3" => "/dev/ttyUSB1",
            "COM4" => "/dev/ttyUSB2",
            "COM5" => "/dev/ttyUSB3",
        ),
        "serial_dev" => array(
            "COM2" => "/dev/ttyUSB0",
            "COM3" => "/dev/ttyUSB1",
            "COM4" => "/dev/ttyUSB2",
            "COM5" => "/dev/ttyUSB3",
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
            "5" => "5",
            "6" => "6",
            "7" => "7",
            "8" => "8",
            "9" => "9",
            "10" => "10",
            "11" => "11",
            "12" => "12",
            "13" => "13",
            "14" => "14",
            "15" => "15",
            "16" => "16",
        )
    )
);




