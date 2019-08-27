<?php


require "NetworkUtils.php";

$videoPath = "/media/disk/videos/";

class ApiUtils
{

    //TODO 资源模式时间和分段配置
    static function start_record()
    {
        global $videoPath;

        $systemConfig = CommonUtils::getSystemConfig();
        $path = $videoPath . date("Y-m-d", time());
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $runtimeConf = CommonUtils::readConfig();
        $save_path = $path . "/" . $runtimeConf->recordName;
        if (file_exists($save_path . $systemConfig["suffix"])) {
            $save_path = $save_path . "_" . date("h时i分s秒", time()) . $systemConfig["suffix"];
        } else {
            $save_path = $save_path . $systemConfig["suffix"];
        }
        $requestData = [
            new ArrayObject(["7" => [
                "filename" => $save_path,
                "segment_duration" => 0,
                "need_to_segment" => false,
            ]])
        ];
        //资源模式
        if ($runtimeConf->configs->video->record->type == "2") {
            $requestData = [];
            $save_path = $path . "_" . date("h时i分s秒", time()) . "_资源模式/"
                . $runtimeConf->recordName . "_";
            if (!file_exists($save_path)) {
                mkdir($save_path, 0777, true);
            }
            for ($i = 0; $i <= 6; $i++) {
                array_push($requestData, new ArrayObject(["$i" => [
                    "filename" => $save_path . $i . $systemConfig["suffix"],
                    "segment_duration" => 0,
                    "need_to_segment" => false,
                ]]));
            }
        }
        return NetworkUtils::get("start_record", $requestData);
    }

    static function stop_record()
    {
        return NetworkUtils::get("stop_record");
    }

    static function start_remote_live($data)
    {
        return NetworkUtils::get("start_remote_live", $data);
    }

    static function stop_remote_live()
    {
        return NetworkUtils::get("stop_remote_live");
    }

    static function start_local_live()
    {
        $config = CommonUtils::readConfig();
        $rtmpUrl = $config->configs->rtmp->server_url;
        $urls = array();
        for ($i = 0; $i <= 6; $i++) {
            $arrayObject = new ArrayObject(array("$i" => array("url" => "$rtmpUrl/$i")));
            array_push($urls, $arrayObject);
        }
        return NetworkUtils::get("start_local_live", $urls);
    }

    static function stop_local_live()
    {
        return NetworkUtils::get("stop_local_live");
    }


    static function switch_($chn)
    {
        return NetworkUtils::get("switch", array("scene" => (int)$chn));
    }


    static function change_main_screen($data)
    {
        $mapping = array();
        foreach ($data->mapping as $k => $v) {
            array_push($mapping, new ArrayObject([$k => $v]));
        }
        return NetworkUtils::get("change_main_screen", array(
            "mode" => (int)$data->mode,
            "mapping" => $mapping,
        ));
    }

    static function change_video($data)
    {
        return NetworkUtils::get("change_video", $data);
    }


    static function change_display_screen($data)
    {
        return NetworkUtils::get("change_display_screen", $data);
    }


    static function change_pc_capture_mode($data)
    {
        return NetworkUtils::get("change_pc_capture_mode", $data);
    }


}