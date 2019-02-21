<?php
/**
 * Created by PhpStorm.
 * User: 10324
 * Date: 2018/1/16
 * Time: 10:25
 */

class CommonUtils
{
    /**
     * @return object
     */
    static function readConfig()
    {

        $runtimeConfigPath = __DIR__ . "/../config/runtime_config.json";

        //读取配置文件
        $config = null;
        if (!file_exists($runtimeConfigPath)) {
            $config = require __DIR__ . "/../config/system_default_config.php";
            file_put_contents($runtimeConfigPath, json_encode($config));
        }
        //object
        return json_decode(file_get_contents($runtimeConfigPath));
    }


    /**
     * @param $config array
     */
    static function writeConfig($config)
    {
        $runtimeConfigPath = __DIR__ . "/../config/runtime_config.json";
        file_put_contents($runtimeConfigPath, json_encode($config));
    }

    /**
     * @return array
     */
    static function readDefaultConfig()
    {
        return require __DIR__ . "/../config/system_default_config.php";
    }


    static function writeToSystem($configs)
    {
        //去除不需交互的字段
        if (is_object($configs)) {
            unset($configs->other);
        } else if (is_array($configs)) {
            unset($configs["other"]);
        }

        if (PHP_OS != "WINNT") {
            $runtimeConfigPath = "/nand/conf/init.json";
            if (!file_exists("/nand/conf")) {
                mkdir("/nand/conf");
            }
            file_put_contents($runtimeConfigPath, json_encode($configs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }
    }


    static function getSystemConfig()
    {
        return require __DIR__ . "/../config/config.php";
    }


    static function initRecordLiveState()
    {
        $configs = self::readConfig()->configs;

        $state = array(
            "living" => $configs->misc->rtmp,
            "recording" => 0,
            "autoSwitch" => $configs->misc->auto_switch,
            "recordTime" => 0,
            "liveTime" => 0,
            "lastPauseTime" => 0,
            "pausedTime" => 0,
            "pause" => 0
        );
        file_put_contents(__DIR__ . "/../config/recordLiveState.json", json_encode($state, JSON_NUMERIC_CHECK));
    }

    static function getRecordLiveState()
    {
        $path = __DIR__ . "/../config/recordLiveState.json";
        if (!file_exists($path))
            self::initRecordLiveState();
        return json_decode(file_get_contents($path));
    }

    static function saveRecordLiveState($state)
    {
        $path = __DIR__ . "/../config/recordLiveState.json";
        file_put_contents($path,
            json_encode(
                array_merge(json_decode(file_get_contents($path), true), $state)
                , JSON_NUMERIC_CHECK));
    }


    static function str_has_empty()
    {
        $args = func_get_args();
        foreach ($args as $arg) {
            if ($arg === null || trim($arg) === "") {
                return true;
            }
        }
        return false;
    }

    static function send_get($url)
    {
        $curl = "export LD_LIBRARY_PATH=/usr/local/lib:/usr/lib:/nand/lib;/nand/curl-7.61.1/arm/bin/curl ";
        $url = self::encode_url($url);
        exec("$curl '$url'", $result, $code);
        return $result;
    }

    public static function encode_url($url)
    {
        $pos = strpos($url, "?");
        if ($pos === false) {
            return $url;
        }
        $pos = $pos + 1;
        $uri = substr($url, 0, $pos);
        $params = substr($url, $pos);
        $paramsArr = explode("&", $params);
        foreach ($paramsArr as $param) {
            if (trim($param) == "") continue;
            $keyPos = strpos($param, "=") + 1;
            if ($keyPos === false) {
                $uri .= urlencode($param);
            } else {
                $uri .= substr($param, 0, $keyPos) . urlencode(substr($param, $keyPos));
            }
            $uri .= "&";
        }
        return $uri;
    }


    static function validateConfig($liveConfig)
    {
        if (self::str_has_empty(
            $liveConfig->serial_number,
            $liveConfig->class_room_name,
            $liveConfig->resource_platform_ip,
            $liveConfig->resource_platform_port,
            $liveConfig->ip_address
        )) {
            return false;
        }
        return "http://$liveConfig->resource_platform_ip:$liveConfig->resource_platform_port"
            . "/web/live?serial_number=$liveConfig->serial_number";
    }


//互动直播
//调用录播主机启动接口
    static function boot()
    {
        $configs = self::readConfig()->configs;
        $liveConfig = $configs->other->interact_live;
        $rtmpServer = $configs->rtmp->server_url;
        if (!$urlPrefix = self::validateConfig($liveConfig)) {
            return false;
        }
        return self::send_get($urlPrefix . "&a=setting&class_room_name=$liveConfig->class_room_name"
                . "&ip_address=$liveConfig->ip_address&rtmp_direct_sending_address=$rtmpServer") == "successed";
    }


//调用开启或关闭直播接口
    static function live($status)
    {
        $configs = self::readConfig()->configs;
        $liveConfig = $configs->other->interact_live;
        if (!$urlPrefix = self::validateConfig($liveConfig)) {
            return false;
        }
        return self::send_get($urlPrefix . "&a=setting&direct_status=$status") == "successed";
    }

//提交资源平台
    static function upload($video)
    {
        $configs = self::readConfig()->configs;
        $liveConfig = $configs->other->interact_live;
        $ftpConfig = $configs->other->ftp;
        $ftpServer = $ftpConfig->server;
        $ftpServer = str_replace("ftp://", "", strtolower($ftpServer));
        if (!$urlPrefix = self::validateConfig($liveConfig)) {
            return false;
        }
        return self::send_get($urlPrefix . "&a=saveVideo&date=" . date("Y-m-d H:i:s")
                . "&picAddress=http://$ftpServer:$ftpConfig->on_demand_port/ftp/static/video_cover.jpg"
                . "&videoAddress=http://$ftpServer:$ftpConfig->on_demand_port/ftp/$liveConfig->serial_number/$video")
            == "successed";
    }


    //去除ip中多余的0
    static function ipSubZero($ip)
    {
        $result = "";
        $array = explode(".", $ip);

        for ($j = 0; $j < sizeof($array); $j++) {
            $item = $array[$j];
            if (strlen($item) == 3) {
                if ($item[0] == "0") {
                    $item = substr($item, 1, 2);
                    if ($item[0] == "0") {
                        $item = substr($item, 1, 1);
                    }
                }
            } else if (strlen($item) == 2) {
                if ($item[0] == "0") {
                    $item = substr($item, 1, 1);
                }
            }
            $result .= $item;
            if ($j != sizeof($array) - 1) {
                $result .= ".";
            }
        }
        return $result;

    }


    //获取设备ip信息
    static function getIpInfo($dev)
    {
        $ip = "";
        $mask = "";
        $gateway = "";

        //获取ip和子网掩码
        $output = shell_exec("ip address show dev '$dev'|grep inet");
        if (!empty($output)) {
            $addressInfo = explode(" ", trim($output));
            $ipMask = explode("/", $addressInfo[1]);
            if (!empty($ipMask)) {
                $ip = $ipMask[0];
                $mask = long2ip(0xFFFFFFFF << intval(32 - $ipMask[1]));
            }
        }


        $output = "";
        //获取网关
        $output = shell_exec("ip route |grep default |grep '$dev'");
        if (!empty($output)) {
            $gateway = explode(" ", trim($output))[2];
        }

        $info = array(
            "ip" => $ip,
            "mask" => $mask,
            "gateway" => $gateway
        );

        return $info;
    }

}

