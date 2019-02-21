<?php
/**
 * Created by PhpStorm.
 * User: 10324
 * Date: 2018/1/27
 * Time: 23:41
 */


class InteractUtils
{


    //  /media/disk
    static function checkAndSendConfig($oldConfigs, $newConfigs)
    {

        $ip = CommonUtils::getSystemConfig()["ip"];
        $port = CommonUtils::getSystemConfig()["port"];

        if ($oldConfigs->audio != $newConfigs->audio) {
            $sendData = array("type" => "0", "audio" => $newConfigs->audio);
            $response = self::socketSendAndRead($ip, $port, json_encode($sendData));
            if ($response == false || json_decode($response)->code != 1) {
                return false;
            }
        }


        if ($oldConfigs->video != $newConfigs->video) {

            $sendData = array("type" => "1", "video" => $newConfigs->video);
            echo (json_encode($sendData));
            $response = self::socketSendAndRead($ip, $port, json_encode($sendData));
            if ($response == false || json_decode($response)->code != 1) {
                return false;
            }

        }


        if ($oldConfigs->rtmp != $newConfigs->rtmp) {
            $sendData = array("type" => "2", "rtmp" => $newConfigs->rtmp);
            $response = self::socketSendAndRead($ip, $port, json_encode($sendData));
            if ($response == false || json_decode($response)->code != 1) {

                return false;
            }
        }


        if ($oldConfigs->serial != $newConfigs->serial) {
            $sendData = array("type" => "3", "serial" => $newConfigs->serial);
            $response = self::socketSendAndRead($ip, $port, json_encode($sendData));
            if ($response == false || json_decode($response)->code != 1) {
                return false;
            }
//            InteractUtils::socketSendAndRead($qt_ip, $qt_port, "sizeChange_".$newConfigs->system->display_mode);
        }


        if ($oldConfigs->main_screen != $newConfigs->main_screen) {
            $sendData = array("type" => "16", "main_screen" => $newConfigs->main_screen);
            $response = self::socketSendAndRead($ip, $port, json_encode($sendData));
            if ($response == false || json_decode($response)->code != 1) {
                return false;
            }
        }

        return true;

    }


    static function socketSendAndRead($ip, $port, $data)
    {
        //创建 TCP/IP socket
        if (($socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) == false) {
            socket_close($socket);
            return false;
        }

        //设置socket超时时间
        //设置socket 发送超时10秒，接收超时10秒：
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 10, "usec" => 0));
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array("sec" => 10, "usec" => 0));

        if (($result = @socket_connect($socket, $ip, $port)) == false) {
            socket_close($socket);
            return false;
        }

        //发送命令
        socket_write($socket, $data, strlen($data));

        $out = socket_read($socket, 10240);
        socket_close($socket);


        if (strlen(trim($out)) > 0) {
            return $out;
        } else {
            return false;
        }
    }


    static function recordLiveState()
    {
        return CommonUtils::getRecordLiveState();
    }


}