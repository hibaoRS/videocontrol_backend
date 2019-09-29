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
                "segment_duration" => 21600,
                "need_to_segment" => true,
            ]])
        ];
        //资源模式
        if ($runtimeConf->configs->misc->resource_mode) {
            $requestData = [];
            $save_path = $path . "_" . date("h_i_s_", time()) . "_resource_mode/"
                . $runtimeConf->recordName . "_";
            if (!file_exists($save_path)) {
                mkdir($save_path, 0777, true);
            }
            for ($i = 0; $i <= 6; $i++) {
                array_push($requestData, new ArrayObject(["$i" => [
                    "filename" => $save_path . $i . $systemConfig["suffix"],
                    "segment_duration" => 21600,
                    "need_to_segment" => true,
                ]]));
            }
        }
        return NetworkUtils::get("start_record", $requestData);
    }

    static function stop_record()
    {
        return NetworkUtils::get("stop_record");
    }

    static function start_remote_live()
    {
        $config = CommonUtils::readConfig();
        $rtmpUrl = $config->configs->rtmp->server_url;
        return NetworkUtils::get("start_remote_live", ["url" => $rtmpUrl]);
    }

    static function stop_remote_live()
    {
        return NetworkUtils::get("stop_remote_live");
    }

    static function start_local_live()
    {

        $rtmpUrl = "rtmp://127.0.0.1/live";
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
        $mode = (int)$data->mode;
        $mapping = array();
        if ($mode != 0) {
            $i = 0;
            $num = 0;
            if ($mode == 2) $num = 2;
            else if ($mode == 3) $num = 3;
            else if ($mode == 4) $num = 4;
            else if ($mode == 5) $num = 4;
            else if ($mode == 6) $num = 5;
            else if ($mode == 7) $num = 6;
            else if ($mode == 8) $num = 6;

            foreach ($data->mapping as $k => $v) {
                array_push($mapping, new ArrayObject([$k => (int)$v]));
                $i++;
                if ($i >= $num) {
                    break;
                }
            }
        } else {
            array_push($mapping, new ArrayObject(["0" => 6]));
        }

        return NetworkUtils::get("change_main_screen", array(
            "mode" => $mode,
            "mapping" => $mapping,
        ));
    }

    static function change_video($config)
    {
        $configOptions = array_flip(CommonUtils::readConfigOptions()["video"]["resolution"]);
        $normal_live_bitrate = $config->live_bitrate;
        $normal_record_bitrate = $config->normal_bitrate;
        $res_bitrate = $config->resource_bitrate;

        $resource_resolution = ApiUtils::getResolution($configOptions[$config->resource_resolution]);
        $normal_resolution = ApiUtils::getResolution($configOptions[$config->normal_resolution]);
        $live_resolution = ApiUtils::getResolution($configOptions[$config->live_resolution]);

        return NetworkUtils::get("change_video", [
            "normal_live_bitrate" => (int)$normal_live_bitrate,
            "normal_live_height" => $live_resolution["height"],
            "normal_live_width" => $live_resolution["width"],
            "normal_record_bitrate" => (int)$normal_record_bitrate,
            "normal_record_height" => $normal_resolution["height"],
            "normal_record_width" => $normal_resolution["width"],
            "res_bitrate" => (int)$res_bitrate,
            "res_height" => $resource_resolution["height"],
            "res_width" => $resource_resolution["width"]
        ]);
    }

    private static function getResolution($res)
    {
        $res = explode("(", $res)[0];
        $res = explode("x", $res);
        return [
            "width" => (int)$res[0],
            "height" => (int)$res[1],
        ];
    }


    static function change_display_screen($data)
    {
        return NetworkUtils::get("change_display_screen", $data);
    }


    static function change_pc_capture_mode($data)
    {
        return NetworkUtils::get("change_pc_capture_mode", array(
            "pc_capture_mode" => (int)$data
        ));
    }


    static function camera_control($camera_addr, $cmd, $value)
    {

        return NetworkUtils::get("camera_control", array(
            "camera_addr" => (int)$camera_addr,
            "cmd" => (int)$cmd,
            "value" => (int)$value,
        ));
    }


    /**
     * {
     * "bb_fea": "FF 02 00 07 00 52 5B",
     * "pc_capture": "FF 02 00 ff ff ff ff",
     * "stu_fea": "FF 02 00 07 00 50 59",
     * "stu_full": "FF 02 00 07 00 40 49",
     * "tea_fea": "FF 02 00 07 00 51 5A",
     * "tea_full": "FF 02 00 07 00 41 4A"
     * }
     */
    static function change_switch_command($data)
    {
        return NetworkUtils::get("change_switch_command", $data);
    }


    static function shutdown()
    {
        return NetworkUtils::get("shutdown");
    }

    static function reboot()
    {
        return NetworkUtils::get("reboot");
    }


    static function save_time()
    {
        return NetworkUtils::get("save_time");
    }


}