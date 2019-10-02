<?php
/**
 * Created by PhpStorm.
 * User: 10324
 * Date: 2018/1/27
 * Time: 23:41
 */

require "ApiUtils.php";
require "CommonUtils.php";


class InteractUtils
{


    //  /media/disk
    static function checkAndSendConfig($oldConfigs, $newConfigs)
    {

        if ($oldConfigs->video->record != $newConfigs->video->record) {
            if (!ApiUtils::change_main_screen($newConfigs->video->record)) {
                return false;
            }
        }

        if ($oldConfigs->video->adv7842_type != $newConfigs->video->adv7842_type) {
            if (!ApiUtils::change_pc_capture_mode($newConfigs->video->adv7842_type)) {
                return false;
            }
        }


        if ($oldConfigs->trace->cmd != $newConfigs->trace->cmd) {
            if (!ApiUtils::change_switch_command($newConfigs->trace->cmd)) {
                return false;
            }
        }

        if ($oldConfigs->video->config != $newConfigs->video->config) {
            if (!ApiUtils::change_video($newConfigs->video->config)) {
                return false;
            }
            CommonUtils::rebootLive();
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