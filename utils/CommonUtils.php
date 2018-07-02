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
     * @return array
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
        if (PHP_OS != "WINNT") {
            $runtimeConfigPath = "/nand/conf/init.json";
            if(!file_exists("/nand/conf")){
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
                array_merge(json_decode(file_get_contents($path),true), $state)
                , JSON_NUMERIC_CHECK));
    }

}