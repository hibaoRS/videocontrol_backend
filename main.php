<?php
/**
 * Created by PhpStorm.
 * User: 10324
 * Date: 2017/12/7
 * Time: 12:54
 */

require "utils/controller.php";
require "utils/CommonUtils.php";
require "utils/Validator.php";
require "utils/InteractUtils.php";
require "utils/Des.php";
require "utils/ApiUtils.php";


class main extends controller
{

    private $ip;
    private $port;
//    private $qt_ip;
//    private $qt_port;
    private $videoPath;
    private $des;
    private $curl;

    public function __construct()
    {

        $this->des = new Des();

        $this->ip = CommonUtils::getSystemConfig()["ip"];
        $this->port = CommonUtils::getSystemConfig()["port"];
//        $this->qt_port = CommonUtils::getSystemConfig()["qt_port"];
//        $this->qt_ip = CommonUtils::getSystemConfig()["qt_ip"];
        //        $this->videoPath = "D:/environment/Apache24/htdocs/videocontrol/videos/";
        $this->videoPath = "/media/disk/videos/";
        $this->curl = "export LD_LIBRARY_PATH=/usr/local/lib:/usr/lib:/nand/lib;/nand/curl-7.61.1/arm/bin/curl ";
//        $this->curl = "curl";
//        $this->videoPath = "/mnt/d/environment/Apache24/htdocs/videos/";

    }


    function getConfig()
    {
        echo json_encode(Msg::success(CommonUtils::readConfig()));
    }

    function getConfigOptions()
    {
        $configOptions = CommonUtils::readConfigOptions();
        die(json_encode(Msg::success($configOptions)));
    }

    function getConfigOptionsInverse()
    {
        $configOptions = CommonUtils::readConfigOptions();
        die(json_encode(Msg::success($this->inverseArray($configOptions)), JSON_FORCE_OBJECT));
    }

    /**翻转数组
     * @param $array
     * @return array
     */
    private function inverseArray($array)
    {
        $result = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->inverseArray($value);
            } else {
                $result[$value] = $key;
            }
        }
        return $result;
    }

    /**文件浏览旧接口
     * function getFiles()
     * {
     * //根目录
     * //        $rootPath = "/media/disk/";
     * $rootPath = "D://";
     * //文件夹目录
     * $filePath = array_key_exists("filePath", $_REQUEST) ? $_REQUEST["filePath"] : "";
     * $path = $rootPath . $filePath . "/";
     *
     *
     * if (strpos(realpath($path), realpath($rootPath)) === false) {
     * die(json_encode(Msg::failed("非法操作")));
     * }
     *
     *
     * if (is_dir($rootPath) && $dir = opendir($path)) {
     * $files = array();
     * while (($fileName = readdir($dir)) != false) {
     * array_push($files, array(
     * "type" => filetype($path . $fileName),
     * "name" => $fileName
     * ));
     * }
     * $files = $this->my_sort($files, "type");
     * closedir($dir);
     * echo json_encode(Msg::success(array(
     * "files" => $files,
     * "path" => str_replace(realpath($rootPath), "", realpath($path))
     * )));
     * } else {
     * echo json_encode(Msg::failed("文件路径有误"));
     * }
     *
     * }**/


    function deleteFile()
    {
        if ($_SESSION["manager"]["type"] != 1) {
            die(json_encode(Msg::failed("非法操作")));
        }
        if (array_key_exists("relativePath", $_REQUEST)) {
            $relativePath = $_REQUEST["relativePath"];
            $path = $this->videoPath . $relativePath;
            if (!file_exists($path)) {
                die(json_encode(Msg::failed("操作失败，该文件已被删除")));
            }
            $this->deleteFiles($path);
            echo json_encode(Msg::success("操作成功"));
        } else {
            echo json_encode(Msg::failed("参数有误"));
        }
    }


    private function deleteFiles($path)
    {
        if (is_dir($path)) {
            $dir = opendir($path);
            while ($fileName = readdir($dir)) {
                if ($fileName == "." || $fileName == "..") {
                    continue;
                }
                $this->deleteFiles($path . "/" . $fileName);
            }
            closedir($dir);
            rmdir($path);
        } else {
            unlink($path);
        }

    }


    function getFiles()
    {
        //视频存放根目录
        if (!file_exists($this->videoPath)) {
            mkdir($this->videoPath);
        }
        $dir = opendir($this->videoPath);
        $data = array();
        $key = 0;
        //ftp配置
        $otherConfigs = CommonUtils::readConfig()->configs->other;
        $ftpConfig = $otherConfigs->ftp;
        $serial_number = $otherConfigs->interact_live->serial_number;
        while (($folderName = readdir($dir)) != false) {
            if ($folderName == "." || $folderName == "..") {
                continue;
            }
            $folderPath = $this->videoPath . $folderName;
            if (filetype($folderPath) == "dir") {
                $secondDir = opendir($folderPath);
                //ftp文件夹是否存在
                $folderExist = $this->ftpFileExist($ftpConfig, $serial_number, $folderName . "/");
                $children = array();
                while (($fileName = readdir($secondDir)) != false) {
                    if (strtolower(substr(trim($fileName), -4)) === ".mp4") {
                        $relativePath = $folderName . "/" . $fileName;
                        array_push($children, array(
                            "key" => $key,
                            "name" => $fileName,
                            "relativePath" => $relativePath,
                            "type" => 1,
                            "size" => $this->sizeAddUnit(filesize($this->videoPath . $relativePath)),
                            //ftp文件是否存在
                            "exist" => $folderExist ? $this->ftpFileExist($ftpConfig, $serial_number, $relativePath) : $folderExist
                        ));
                        $key++;
                    }
                }
                closedir($secondDir);
                //是否有子文件
                if (!empty($children)) {
                    array_push($data, array(
                        "key" => $key,
                        "name" => $folderName,
                        "relativePath" => $folderName,
                        "type" => 0,
                        "children" => $children,
                    ));
                } else {
                    array_push($data, array(
                        "key" => $key,
                        "name" => $folderName,
                        "relativePath" => $folderName,
                        "type" => 0,
                    ));
                }
                $key++;
            }

        }
        closedir($dir);
        echo json_encode(Msg::success($data));
    }


    private function sizeAddUnit($byte)
    {
        if ($byte < 1024) {
            $unit = "B";
        } else if ($byte < 10240) {
            $byte = $this->round_dp($byte / 1024, 2);
            $unit = "KB";
        } else if ($byte < 102400) {
            $byte = $this->round_dp($byte / 1024, 2);
            $unit = "KB";
        } else if ($byte < 1048576) {
            $byte = $this->round_dp($byte / 1024, 2);
            $unit = "KB";
        } else if ($byte < 10485760) {
            $byte = $this->round_dp($byte / 1048576, 2);
            $unit = "MB";
        } else if ($byte < 104857600) {
            $byte = $this->round_dp($byte / 1048576, 2);
            $unit = "MB";
        } else if ($byte < 1073741824) {
            $byte = $this->round_dp($byte / 1048576, 2);
            $unit = "MB";
        } else {
            $byte = $this->round_dp($byte / 1073741824, 2);
            $unit = "GB";
        }

        $byte .= $unit;
        return $byte;
    }

    private function round_dp($num, $dp)
    {
        $sh = pow(10, $dp);
        return (round($num * $sh) / $sh);
    }

    private function my_sort($arrays, $sort_key, $sort_order = SORT_ASC, $sort_type = SORT_NUMERIC)
    {
        if (is_array($arrays)) {
            foreach ($arrays as $array) {
                if (is_array($array)) {
                    $key_arrays[] = $array[$sort_key];
                } else {
                    return [];
                }
            }
        } else {
            return [];
        }
        array_multisort($key_arrays, $sort_order, $sort_type, $arrays);
        return $arrays;
    }


    //修改设置
    function setConfig()
    {

        $state = InteractUtils::recordLiveState();

        if ($state->recording == 1) {
            die(json_encode(Msg::failed("操作失败，在配置之前，请先停止录制")));
        }

        $fp = fopen(__DIR__ . "/configLock", "w+");

        if (flock($fp, LOCK_EX | LOCK_NB)) {
            Validator::notEmpty(array("configs"));
            $configs = json_decode($_REQUEST["configs"]);
            $allConfigs = CommonUtils::readConfig();
            $oldConfigs = $allConfigs->configs;
            if (InteractUtils::checkAndSendConfig($oldConfigs, $configs)) {
                //修改互动直播配置，重新发送开机请求
                $needBoot = false;
                if ($oldConfigs->other->interact_live != $configs->other->interact_live
                    || $oldConfigs->rtmp != $configs->rtmp) {
                    $needBoot = true;
                }
                $allConfigs->configs = $configs;
                CommonUtils::writeConfig($allConfigs);
                CommonUtils::writeToSystem($allConfigs->configs);
                if ($needBoot) {
                    CommonUtils::boot();
                }
                echo json_encode(Msg::success("操作成功"));
            } else {
                echo json_encode(Msg::failed("操作失败，请重启设备或稍后再试"));

            }
            flock($fp, LOCK_UN);    // 释放锁定
        } else {
            echo json_encode(Msg::failed("上次操作尚未完成，请稍后再试或重启设备"));
        }
        fclose($fp);
    }


    function reStoreDefault()
    {
        $state = InteractUtils::recordLiveState();
        if ($state->recording == 1) {
            die(json_encode(Msg::failed("操作失败，在恢复默认配置之前，请先停止录制")));
        }
        $fp = fopen(__DIR__ . "/configLock", "w+");
        if (flock($fp, LOCK_EX | LOCK_NB)) {
            $oldConfigs = CommonUtils::readConfig()->configs;
            $defaultConfigs = CommonUtils::readConfig()->configs;
            if (InteractUtils::checkAndSendConfig($oldConfigs, $defaultConfigs)) {
                unlink(__DIR__ . "/config/runtime_config.json");
                CommonUtils::writeToSystem(CommonUtils::readConfig()->configs);
                echo json_encode(Msg::success("操作成功"));
            } else {
                echo json_encode(Msg::failed("操作失败，请重启设备或稍后再试"));
            }
            flock($fp, LOCK_UN);    // 释放锁定
        } else {
            echo json_encode(Msg::failed("上次操作尚未完成，请稍后再试"));
        }
        fclose($fp);
    }


    function setRecordConfig()
    {
        Validator::notEmpty(array("key", "value"));

        $key = $_REQUEST["key"];
        $value = $_REQUEST["value"];

        $allConfigs = CommonUtils::readConfig();
        $configs = $allConfigs->configs;
        $record = $configs->record;
        $record->$key = $value;
        $configs->record = $record;
        $allConfigs->configs = $configs;
        CommonUtils::writeConfig($allConfigs);

        die(json_encode(Msg::success("操作成功")));

    }


    //暂停录制
    function startPause()
    {
        $state = InteractUtils::recordLiveState();
        if ($state->pause == 0) {
            $fp = fopen(__DIR__ . "/configLock", "w+");
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                $data = array("type" => "10");
                $response = InteractUtils::socketSendAndRead($this->ip, $this->port, json_encode($data));
                if (json_decode($response)->code == 1) {
                    //保存录制直播状态
                    CommonUtils::saveRecordLiveState(array(
                        "pause" => 1,
                        "lastPauseTime" => floatval($_REQUEST["time"]),
                    ));
                    echo json_encode(Msg::success("操作成功"));
                } else {
                    echo json_encode(Msg::failed("暂停录制失败，请重启系统"));
                }
                flock($fp, LOCK_UN);    // 释放锁定
            } else {
                echo json_encode(Msg::failed("上次操作尚未完成，请稍后再试或重启设备"));
            }
            fclose($fp);
        } else {
            echo json_encode(Msg::failed("操作失败，请刷新页面后再试"));
        }
    }

    //继续录制
    function stopPause()
    {
        $state = InteractUtils::recordLiveState();
        if ($state->pause == 1) {
            $fp = fopen(__DIR__ . "/configLock", "w+");
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                $data = array("type" => "9");
                $response = InteractUtils::socketSendAndRead($this->ip, $this->port, json_encode($data));
                if (json_decode($response)->code == 1) {
                    //保存录制直播状态
                    CommonUtils::saveRecordLiveState(array(
                        "pause" => 0,
                        "pausedTime" => $state->pausedTime + (floatval($_REQUEST["time"]) - $state->lastPauseTime),
                    ));
                    echo json_encode(Msg::success("操作成功"));
                } else {
                    echo json_encode(Msg::failed("继续录制失败，请重启系统"));
                }
                flock($fp, LOCK_UN);    // 释放锁定
            } else {
                echo json_encode(Msg::failed("上次操作尚未完成，请稍后再试或重启设备"));
            }
            fclose($fp);
        } else {
            echo json_encode(Msg::failed("操作失败，请刷新页面后再试"));
        }
    }


// media/disk
    function startRecord()
    {
        //激活判断
        if ($this->activateState()["activate"] !== 1) {
            die(Msg::failed("操作失败，设备未激活"));
        }

        $state = InteractUtils::recordLiveState();
        if ($state->recording == 0) {
            $fp = fopen(__DIR__ . "/configLock", "w+");
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                if (ApiUtils::start_record()) {
                    //保存录制直播状态
                    CommonUtils::saveRecordLiveState(array(
                        "recording" => 1,
                        "recordTime" => floatval($_REQUEST["time"]),
                        "lastPauseTime" => 0,
                        "pausedTime" => 0,
                        "pause" => 0
                    ));
                    echo json_encode(Msg::success("操作成功"));
                } else {
                    echo json_encode(Msg::failed("开始录制失败，请重启系统"));
                }
                flock($fp, LOCK_UN);    // 释放锁定
            } else {
                echo json_encode(Msg::failed("上次操作尚未完成，请稍后再试或重启设备"));
            }
            fclose($fp);

        } else {
            echo json_encode(Msg::failed("操作失败，请刷新页面后再试"));
        }

    }

    function stopRecord()
    {
        $state = InteractUtils::recordLiveState();
        if ($state->recording == 1) {
            $fp = fopen(__DIR__ . "/configLock", "w+");
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                if (ApiUtils::stop_record()) {
                    //保存录制直播状态
                    CommonUtils::saveRecordLiveState(array(
                        "recording" => 0,
                        "lastPauseTime" => 0,
                        "pausedTime" => 0,
                        "pause" => 0
                    ));
                    echo json_encode(Msg::success("操作成功"));
                } else {
                    echo json_encode(Msg::failed("操作失败"));
                }
                flock($fp, LOCK_UN);    // 释放锁定
            } else {
                echo json_encode(Msg::failed("上次操作尚未完成，请稍后再试或重启设备"));
            }
            fclose($fp);
        } else {
            echo json_encode(Msg::failed("操作失败，请刷新页面后再试"));
        }

    }


    function startLive()
    {
        $state = InteractUtils::recordLiveState();
        if ($state->living == 0) {
            $fp = fopen(__DIR__ . "/configLock", "w+");
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                if (ApiUtils::start_local_live()) {
                    //保存录制直播状态
                    CommonUtils::saveRecordLiveState(array(
                        "living" => 1,
                        "liveTime" => floatval($_REQUEST["time"])
                    ));
                    CommonUtils::live(1);
                    echo json_encode(Msg::success("操作成功"));
                } else {
                    echo json_encode(Msg::failed("开始直播失败，请重启系统"));
                }
                flock($fp, LOCK_UN);    // 释放锁定
            } else {
                echo json_encode(Msg::failed("上次操作尚未完成，请稍后再试或重启设备"));
            }
            fclose($fp);
        } else {
            echo json_encode(Msg::failed("操作失败，请刷新页面后再试"));
        }

    }

    function stopLive()
    {
        $state = InteractUtils::recordLiveState();
        if ($state->living == 1) {
            $fp = fopen(__DIR__ . "/configLock", "w+");
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                if (ApiUtils::stop_local_live()) {
                    //保存录制直播状态
                    CommonUtils::saveRecordLiveState(array(
                        "living" => 0
                    ));
                    CommonUtils::live(0);
                    echo json_encode(Msg::success("操作成功"));
                } else {
                    echo json_encode(Msg::failed("停止直播失败，请重启系统"));
                }
                flock($fp, LOCK_UN);    // 释放锁定
            } else {
                echo json_encode(Msg::failed("上次操作尚未完成，请稍后再试或重启设备"));
            }
            fclose($fp);
        } else {
            echo json_encode(Msg::failed("操作失败，请刷新页面后再试"));
        }
    }

    function setResourceMode()
    {
        Validator::notEmpty(array("resource_mode"));
        $allConfigs = CommonUtils::readConfig();
        $switch = $_REQUEST["resource_mode"];
        if (ApiUtils::change_record_mode($switch)) {
            $allConfigs->configs->misc->resource_mode = $switch;
            CommonUtils::writeConfig($allConfigs);
            echo json_encode(Msg::success("操作成功"));
        } else {
            echo json_encode(Msg::failed("操作失败，请刷新页面后再试"));
        }
    }

    function setRemoteLiving()
    {
        Validator::notEmpty(array("remoteLiving"));
        $remoteLiving = $_REQUEST["remoteLiving"];
        if ($remoteLiving) {
            ApiUtils::start_remote_live();
        } else {
            ApiUtils::stop_remote_live();
        }
        CommonUtils::saveRecordLiveState(array(
            "remoteLiving" => $remoteLiving,
        ));
        echo json_encode(Msg::success("操作成功"));
    }


    function manualSwitch()
    {
        $state = InteractUtils::recordLiveState();
        if ($state->autoSwitch == 1) {

            $fp = fopen(__DIR__ . "/configLock", "w+");
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                //保存状态
                CommonUtils::saveRecordLiveState(array(
                    "autoSwitch" => 0
                ));
                $allConfigs = CommonUtils::readConfig();
                $configs = $allConfigs->configs;
                $misc = $configs->misc;
                $misc->auto_switch = "0";
                $configs->misc = $misc;
                $allConfigs->configs = $configs;
                CommonUtils::writeConfig($allConfigs);
                CommonUtils::writeToSystem($allConfigs->configs);
                echo json_encode(Msg::success("操作成功"));

                flock($fp, LOCK_UN);    // 释放锁定
            } else {
                echo json_encode(Msg::failed("上次操作尚未完成，请稍后再试或重启设备"));
            }
            fclose($fp);
        } else {
            echo json_encode(Msg::failed("操作失败，请刷新页面后再试"));
        }
    }

    function autoSwitch()
    {
        $state = InteractUtils::recordLiveState();
        if ($state->autoSwitch != 1) {

            $fp = fopen(__DIR__ . "/configLock", "w+");
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                //保存状态
                CommonUtils::saveRecordLiveState(array(
                    "autoSwitch" => 1
                ));
                $allConfigs = CommonUtils::readConfig();
                $configs = $allConfigs->configs;
                $misc = $configs->misc;
                $misc->auto_switch = "1";
                $configs->misc = $misc;
                $allConfigs->configs = $configs;
                CommonUtils::writeConfig($allConfigs);
                CommonUtils::writeToSystem($allConfigs->configs);
                echo json_encode(Msg::success("操作成功"));
                flock($fp, LOCK_UN);    // 释放锁定
            } else {
                echo json_encode(Msg::failed("上次操作尚未完成，请稍后再试或重启设备"));
            }
            fclose($fp);
        } else {
            echo json_encode(Msg::failed("操作失败，请刷新页面后再试"));
        }
    }


    function changeLayout()
    {
        Validator::notEmpty(array("data"));
        $response = InteractUtils::socketSendAndRead($this->ip, $this->port, $_REQUEST["data"]);
        if (json_decode($response)->code == 1) {
            echo json_encode(Msg::success("操作成功"));
        } else {
            echo json_encode(Msg::failed("操作失败"));
        }

    }

    function switchMain()
    {
        if (InteractUtils::recordLiveState()->autoSwitch != 1) {
            Validator::notEmpty(array("chn"));

            $chn = $_REQUEST["chn"];
            if ($chn > 5 ||
                ($chn != 5 && json_decode(file_get_contents(__DIR__ . "/run/signal_states.json"))->signal[$chn] == 255)) {
                die(json_encode(Msg::failed("操作失败")));
            }
            if (ApiUtils::switch_($chn)) {
                echo json_encode(Msg::success("操作成功"));
            } else {
                echo json_encode(Msg::failed("操作失败"));
            }
        } else {
            echo json_encode(Msg::failed("操作失败"));

        }
    }

    function setRecordSecondDir()
    {
        $state = InteractUtils::recordLiveState();

        if ($state->recording == 1) {
            die(json_encode(Msg::failed("操作失败，在配置之前，请先停止录制")));
        }

        $fp = fopen(__DIR__ . "/configLock", "w+");
        if (flock($fp, LOCK_EX | LOCK_NB)) {
            Validator::notEmpty(array("second_dir"));
            $allConfigs = CommonUtils::readConfig();
            $configs = $allConfigs->configs;
            $record = $configs->record;
            $record->second_dir = $_REQUEST["second_dir"];
            $configs->record = $record;
            $allConfigs->configs = $configs;
            CommonUtils::writeConfig($allConfigs);
            echo json_encode(Msg::success("操作成功"));
            flock($fp, LOCK_UN);    // 释放锁定
        } else {
            echo json_encode(Msg::failed("上次操作尚未完成，请稍后再试或重启设备"));
        }
        fclose($fp);
    }


    function setRecordName()
    {
        $state = InteractUtils::recordLiveState();

        if ($state->recording == 1) {
            die(json_encode(Msg::failed("操作失败，在配置之前，请先停止录制")));
        }
        $fp = fopen(__DIR__ . "/configLock", "w+");
        if (flock($fp, LOCK_EX | LOCK_NB)) {
            Validator::notEmpty(array("name"));
            $allConfigs = CommonUtils::readConfig();
            $allConfigs->recordName = $_REQUEST["name"];
            CommonUtils::writeConfig($allConfigs);
            echo json_encode(Msg::success("操作成功"));
            flock($fp, LOCK_UN);    // 释放锁定
        } else {
            echo json_encode(Msg::failed("上次操作尚未完成，请稍后再试或重启设备"));
        }
        fclose($fp);
    }


    function powerOff()
    {
        ApiUtils::shutdown();
        echo json_encode(Msg::success("操作成功"));
    }

    function reboot()
    {
        ApiUtils::reboot();
        echo json_encode(Msg::success("操作成功"));
    }


    function getSignals()
    {
        echo json_encode(Msg::success(json_decode(file_get_contents(__DIR__ .
            "/run/signal_states.json"))));
    }

    function setLayout()
    {
        Validator::notEmpty(array("layout_name"));
        $allConfig = CommonUtils::readConfig();
        $allConfig->layout = $_REQUEST["layout_name"];
        CommonUtils::writeConfig($allConfig);
        echo json_encode(Msg::success("操作成功"));
    }


    /**function setIp()
     * {
     * Validator::notEmpty( array("name", "ip"));
     * $allConfigs = CommonUtils::readConfig();
     * $video_urls = $allConfigs->video_urls;
     * $ip = $_REQUEST["ip"];
     * if ("eth0" == $_REQUEST["name"]) {
     * for ($i = 0; $i <= 6 && $i != 5; $i++) {
     * $video_urls->$i = "http://" . $ip . ":8080/live/" . $i . ".flv";
     * }
     *
     * } else if ("eth1" == $_REQUEST["name"]) {
     * $video_urls[5] = "http://" . $ip . ":8080/live/" . 5 . ".flv";
     * } else {
     * die(json_encode(Msg::failed("操作失败")));
     * }
     * $allConfigs->video_urls = $video_urls;
     * CommonUtils::writeConfig($allConfigs);
     * die(json_encode(Msg::success("操作成功")));
     *
     * }**/

//    function setTime()
//    {
//        InteractUtils::socketSendAndRead($this->ip, $this->port, json_encode(array("type" => "15")));
//        echo json_encode(Msg::success("操作成功"));
//    }

    function getHtml()
    {
        $htmlPath = __DIR__ . "/customHtml/index.html";
        if (!file_exists($htmlPath)) {
            echo json_encode(Msg::success
            ("<span style='display:flex;justify-content:center;color:red;font-size: larger;font-weight: bolder'>尚未上传自定义html页面文件</span>"));
        } else {
            echo json_encode(Msg::success(file_get_contents($htmlPath)));

        }
    }

    function getHtmlMain()
    {
        $htmlPath = __DIR__ . "/customHtml/indexMain.html";
        if (!file_exists($htmlPath)) {
            echo json_encode(Msg::success
            ("<span style='display:flex;justify-content:center;color:red;font-size: larger;font-weight: bolder'>尚未上传自定义html页面文件</span>"));
        } else {
            echo json_encode(Msg::success(file_get_contents($htmlPath)));

        }
    }


    function setSectionTime()
    {
        if (array_key_exists("sectionTime", $_REQUEST)) {
            if (InteractUtils::recordLiveState()->recording) {
                echo json_encode(Msg::failed("录制中不能更改课程分段时长"));
            } else {
                $allConfigs = CommonUtils::readConfig();
                $sectionTime = intval($_REQUEST["sectionTime"]);
                if ($sectionTime < 30 || $sectionTime > 60) {
                    die(json_encode(Msg::failed("分段时间有误，需要在30-60分钟区间内")));
                }
                $allConfigs->sectionTime = $sectionTime;
                CommonUtils::writeConfig($allConfigs);
                echo json_encode(Msg::success("设置分段时长成功"));
            }
        } else {
            echo json_encode(Msg::failed("参数有误"));
        }
    }

    //是否显示应用管理面板
    function getShowAppManage()
    {
        echo json_encode(Msg::success(CommonUtils::readConfig()->showAppManage));
    }


    //设置是否显示应用管理面板
    function setShowAppManage()
    {
        if (array_key_exists("showAppManage", $_REQUEST)) {
            $allConfigs = CommonUtils::readConfig();
            $allConfigs->showAppManage = intval($_REQUEST["showAppManage"]);
            CommonUtils::writeConfig($allConfigs);
            echo json_encode(Msg::success("操作成功"));
        } else {
            echo json_encode(Msg::failed("操作失败"));
        }

    }


    //获取是否显示控制应用管理面板
    function getShowControlAppManage()
    {
        echo json_encode(Msg::success(CommonUtils::readConfig()->isShowControlAppManage));

    }

    //设置是否显示控制应用管理面板
    function setShowControlAppManage()
    {
        if (array_key_exists("showControlAppManage", $_REQUEST)) {
            $allConfigs = CommonUtils::readConfig();
            $allConfigs->showControlAppManage = intval($_REQUEST["showControlAppManage"]);
            CommonUtils::writeConfig($allConfigs);
            echo json_encode(Msg::success("操作成功"));
        } else {
            echo json_encode(Msg::failed("操作失败"));
        }
    }


    //通用设置接口,设置主页控制面板、摄像头控制等
    function setConfigValue()
    {
        Validator::notEmpty(array("key", "val", "configKey"));
        $configKey = $_REQUEST["configKey"];
        $allConfigs = CommonUtils::readConfig();
        $config = $allConfigs->$configKey;
        $key = $_REQUEST["key"];
        $config->$key = $_REQUEST["val"];
        $allConfigs->$configKey = $config;
        CommonUtils::writeConfig($allConfigs);
        echo json_encode(Msg::success("操作成功"));
    }

    function setShowMainPanel()
    {
        if (array_key_exists("showMainPanel", $_REQUEST)) {
            $allConfigs = CommonUtils::readConfig();
            $mainPanel = $allConfigs->mainPanel;
            $mainPanel->enabled = intval($_REQUEST["showMainPanel"]);
            $allConfigs->mainPanel = $mainPanel;
            CommonUtils::writeConfig($allConfigs);
            echo json_encode(Msg::success("操作成功"));
        } else {
            echo json_encode(Msg::failed("操作失败"));
        }
    }


    function showControlAppManage()
    {
        $allConfigs = CommonUtils::readConfig();
        $allConfigs->showControlAppManage = 1;
        CommonUtils::writeConfig($allConfigs);
        header('Content-type: text/html;charset=utf-8');
        echo "<script> alert('操作成功')</script>";
    }


    function setHtml()
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) == "OPTIONS") {
            header("access-control-allow-headers: authorization,x-requested-with");
            header("access-control-allow-methods: GET,HEAD,PUT,PATCH,POST,DELETE");
            http_response_code("204");
            return;
        }
        if (PHP_OS == "Linux") {
//        if (true) {
            $rootPath = __DIR__ . DIRECTORY_SEPARATOR;

            //上传校验
            if (!array_key_exists("file", $_FILES)) {
                die(json_encode(Msg::failed("参数有误")));
            }
            $file = $_FILES["file"];
            if (!$file["type"] == "application/zip") {
                die(json_encode(Msg::failed("上传的不是一个zip压缩包")));
            }
            if ($file["error"] > 0) {
                die(json_encode(Msg::failed("未知错误，请重新上传文件")));
            }

            if (!file_exists($rootPath . "temp/")) {
                mkdir($rootPath . "temp/");
            }

            //上传文件
            $filePath = $rootPath . "temp/temp.zip";
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            move_uploaded_file($file["tmp_name"], $filePath);


            //解压文件
            $extractPath = $rootPath . "temp/customHtml/";
            if (file_exists($extractPath)) {
                $this->deleteFiles($extractPath);
            }
            mkdir($extractPath);
            exec("unzip " . $filePath . " -d " . $extractPath, $myTemp, $result);
            if ($result != 0) {
                die(json_encode(Msg::failed("解压文件失败，请检查是否为正确的压缩包格式")));
            }

            //校验文件
//            if (!file_exists($extractPath . "index.html")) {
//                die(json_encode(Msg::failed("操作失败，所上传压缩包文件根目录不存在index.html文件，请检查后再上传")));
//            }

            //复制文件
            $customHtmlPath = $rootPath . "customHtml/";
            if (file_exists($customHtmlPath)) {
                $this->deleteFiles($customHtmlPath);
            }
            exec("cp " . $extractPath . " " . $customHtmlPath . " -r", $myTemp, $result2);

            if ($result2 != 0) {
                die(json_encode(Msg::failed("解压复制文件失败，请稍后再试或重启系统")));
            }
            echo json_encode(Msg::success("操作成功"));
        } else {
            echo json_encode(Msg::failed("操作失败，服务器端非Linux系统"));
        }

    }


    function initLiveTime()
    {
        Validator::notEmpty(array("time"));
        CommonUtils::saveRecordLiveState(array(
            "liveTime" => floatval($_REQUEST["time"])
        ));
    }

    function formatDisk()
    {
        if ($_SESSION["manager"]["type"] != 1) {
            die(json_encode(Msg::failed("非法操作")));
        } else {
            $state = InteractUtils::recordLiveState();
            if ($state->recording == 1) {
                die(json_encode(Msg::failed("操作失败，在格式化硬盘之前，请先停止录制")));
            }
            exec("rm /media/disk/* -rf", $result, $code);
            if ($code == 0) {
                echo json_encode(Msg::success("操作成功"));
            } else {
                echo json_encode(Msg::failed("操作失败，请稍后再试"));
            }
        }
    }

    //设置备用url地址
    function setStandByUrl()
    {
        Validator::notEmpty(array("index"));

        $allConfigs = CommonUtils::readConfig();
        $standbyUrls = $allConfigs->standbyUrls;
        $standbyUrls[$_REQUEST["index"]] = $_REQUEST["url"];
        $allConfigs->standbyUrls = $standbyUrls;
        CommonUtils::writeConfig($allConfigs);
        echo json_encode(Msg::success("操作成功"));
    }


    //摄像头控制
    function cameraControl()
    {
        Validator::notEmpty(array("addr", "cmd", "value"));

        if (ApiUtils::camera_control($_REQUEST["addr"], $_REQUEST["cmd"], $_REQUEST["value"])) {
            echo json_encode(Msg::success("操作成功"));
        } else {
            echo json_encode(Msg::failed("操作失败，请稍后再试"));
        }
    }

    //设置摄像头参数
    function setCameraValue()
    {
        Validator::notEmpty(array("camera", "focal_length", "zoom_speed"));
        $camera = $_REQUEST["camera"];
        $focal_length = intval($_REQUEST["focal_length"]);
        $zoom_speed = intval($_REQUEST["zoom_speed"]);
        if (intval($camera) < 0 || intval($camera) > 4) {
            die(json_encode(Msg::failed("camera参数有误")));
        }
        if ($focal_length < 0 || $focal_length > 1023) {
            die(json_encode(Msg::failed("focal_length参数有误")));
        }
        if ($zoom_speed < 2 || $zoom_speed > 7) {
            die(json_encode(Msg::failed("zoom_speed参数有误")));
        }


        $allConfigs = CommonUtils::readConfig();
        $camera_control = $allConfigs->camera_control;

        $camera_control->$camera->focal_length = $focal_length;
        $camera_control->$camera->zoom_speed = $zoom_speed;

        $allConfigs->camera_control = $camera_control;
        CommonUtils::writeConfig($allConfigs);
        echo json_encode(Msg::success("操作成功"));
    }


    function activate()
    {
        if (!array_key_exists("activateCode", $_REQUEST)) {
            die(json_encode(Msg::failed("激活失败，激活码格式有误")));
        }
        $activateCode = $_REQUEST["activateCode"];
        if (!preg_match("/^[0-9A-Z]{48}$/", $activateCode)) {
            die(json_encode(Msg::failed("激活失败，激活码格式有误")));
        }
        $codePath = "./config/usedCodes.json";
        if (file_exists($codePath)) {
            $codes = json_decode(file_get_contents($codePath));
        } else {
            $codes = array();
        }
        if (in_array($activateCode, $codes)) {
            die(json_encode(Msg::failed("激活失败，该激活码已被使用过")));
        }

        $fp = fopen(__DIR__ . "/configLock", "w+");
        if (flock($fp, LOCK_EX | LOCK_NB)) {

            if ($time = $this->isActivate($activateCode)) {
                $timePath = "./config/time.json";
                $configTime = json_decode(file_get_contents($timePath));
                if (!property_exists($configTime, "expiryTime")) {
                    $configTime->expiryTime = 0;
                }
                if ($time == 99) {
                    $time = 10000;
                }
                //增长有效期
                $addTime = $time * 30.5 * 24 * 60 * 60;
                if ($configTime->fakeTime < $configTime->expiryTime) {
                    $configTime->expiryTime = $configTime->expiryTime + $addTime;
                } //激活
                else {
                    $configTime->expiryTime = $configTime->fakeTime + $addTime;
                    $configTime->activateTime = $configTime->fakeTime;
                }
                file_put_contents($timePath, json_encode($configTime));
                array_push($codes, $activateCode);
                file_put_contents($codePath, json_encode($codes));
                echo json_encode(Msg::success(array(
                    "msg" => "激活成功，感谢使用本产品",
                    "expiryTime" => $configTime->expiryTime,
                    "activateTime" => $configTime->activateTime,
                )));
            } else {
                echo json_encode(Msg::failed("激活失败，该激活码无效"));
            }

            flock($fp, LOCK_UN);    // 释放锁定
        } else {
            echo json_encode(Msg::failed("激活失败，操作过于频繁"));
        }
        fclose($fp);

    }


    private function isActivate($activateCode)
    {
        $salt1 = "HAIBAO";
        $salt2 = "RECORD";
        $salt3 = "SYSTEM";
        $salt4 = "haibaoLB";

        $productId = $this->getProductId();
        $code = strtolower(md5(strtolower($salt1 . $productId)));
        $code = strtolower(md5(strtolower($code)));
        $code = strtolower(md5(strtolower($code . $salt2)));
        $code = strtoupper(md5(strtoupper(substr_replace($code, $salt3, 16, 0))));
        $code = strtoupper(substr(md5($code), 8, 16));
        $pCode = substr_replace($this->des->decrypt(hex2bin($activateCode), $salt4)
            , "", 0, 1);
        $pCode = substr_replace($pCode, "", 4, 1);
        $pCode = substr_replace($pCode, "", 9, 1);
        $pCode = substr_replace($pCode, "", 13, 1);
        $pCode = substr_replace($pCode, "", 18, 1);
        $time = substr($pCode, 8, 2);
        $pCode = substr_replace($pCode, "", 8, 2);
        $times = ["01", "03", "06", "12", "24", "36", "60", "99"];

        if ($code == $pCode && in_array($time, $times)) {
            return intval($time);
        }
        return false;
    }


    function getActivateState()
    {
        echo json_encode(Msg::success($this->activateState()));
    }

    private function activateState()
    {
        if (file_exists("./config/time.json")) {
            $configTime = json_decode(file_get_contents("./config/time.json"));
        } else {
            $configTime = (object)array(
                "trueTime" => 1545308939,
                "fakeTime" => 1545308939,
                "expiryTime" => null,
                "activateTime" => null,
            );
            file_put_contents("./config/time.json", json_encode($configTime));
        }
        return array(
            "activate" => $configTime->fakeTime < $configTime->expiryTime ? 1 : 0,
            "expiryTime" => $configTime->expiryTime,
            "activateTime" => $configTime->activateTime,
        );

    }

    private function getProductId()
    {
        if (!CommonUtils::isLinux()) {
            return "3426d560-8832-4469-a70b-74d6a35245ce";
        }
        $dev = trim(shell_exec("df|grep media/disk|awk {'print $1'}"));
        $response = trim(shell_exec("blkid|grep '$dev'|awk -F '\\\"' {'print $2'}"));
        if ($response) {
            return $response;
        } else {
            die(json_encode(Msg::failed("操作失败，请稍后再试")));
        }
    }

    function productId()
    {
        echo json_encode(Msg::success($this->getProductId()));
    }


    //校准时间（用于激活）
    function setConfigTime()
    {
        Validator::notEmpty(array("time", "isTrueTime"));
        $isTrueTime = $_REQUEST["isTrueTime"];
        $time = intval($_REQUEST["time"]);
        $timePath = "./config/time.json";
        if (file_exists($timePath)) {
            $configTime = json_decode(file_get_contents($timePath));
        } else {
            $configTime = (object)array(
                "trueTime" => 1545308939,
                "fakeTime" => 1545308939,
                "expiryTime" => null,
                "activateTime" => null,
            );
        }

        //直接请求服务器时间
        @file_get_contents(
            "https://seeyouweb.com/time/getTime", false,
            stream_context_create(array(
                'http' => array(
                    'timeout' => 1 //超时时间，单位为秒
                )))
        );
        if (isset($http_response_header)) {
            foreach ($http_response_header as $v) {
                if (($pos = strpos($v, "Date:")) !== false) {
                    $time = strtotime(substr($v, 5));
                    $isTrueTime = true;
                    break;
                }
            }
        }

        if ($isTrueTime) {
            $configTime->trueTime = $time;
            $configTime->fakeTime = $time;
        } else {
            $oldTrueTime = $configTime->trueTime;
            if ($time > $oldTrueTime && $time > $configTime->fakeTime && $time < ($oldTrueTime + 60 * 60 * 24 * 30 * 12 * 10)) {
                $configTime->fakeTime = $time;
            }
        }
        file_put_contents($timePath, json_encode($configTime));
        echo json_encode(Msg::success());
    }



    //FTP和互动直播
    //视频封面上传
    function setVideoCover()
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) == "OPTIONS") {
            header("access-control-allow-headers: authorization,x-requested-with");
            header("access-control-allow-methods: GET,HEAD,PUT,PATCH,POST,DELETE");
            http_response_code("204");
            return;
        }
        if (PHP_OS == "Linux") {
            $rootPath = __DIR__ . DIRECTORY_SEPARATOR;
            //上传校验
            if (!array_key_exists("file", $_FILES)) {
                die(json_encode(Msg::failed("参数有误")));
            }
            $file = $_FILES["file"];
            //上传文件
            $relativePath = "static/video_cover.jpg";
            $filePath = $rootPath . $relativePath;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            move_uploaded_file($file["tmp_name"], $filePath);
            $allConfigs = CommonUtils::readConfig();
            $allConfigs->configs->other->interact_live->picAddress = $relativePath;
            CommonUtils::writeConfig($allConfigs);
            //上传封面到ftp服务器
            $otherConfig = CommonUtils::readConfig()->configs->other;
            $ftpConfig = $otherConfig->ftp;
            $serial_number = $otherConfig->interact_live->serial_number;
            exec("$this->curl  --ftp-create-dirs -T '$filePath'"
                . " '$ftpConfig->server/$relativePath'"
                . " -u $ftpConfig->user:$ftpConfig->password");
            echo json_encode(Msg::success($relativePath));
        } else {
            echo json_encode(Msg::failed("操作失败，服务器端非Linux系统"));
        }

    }


    function ftpFileExist($ftpConfig, $serial_number, $path, $timeOut = 0.5)
    {

        $ftpServer = str_replace("ftp://", "", strtolower($ftpConfig->server));
        $cmd = "$this->curl -I 'http://$ftpServer:$ftpConfig->on_demand_port/ftp/$serial_number/$path' -r 0-1 --connect-timeout $timeOut -m $timeOut";
        exec($cmd, $myTemp, $result);
        if (!$result && array_key_exists(0, $myTemp) && !strstr($myTemp[0], "404")) {
            return true;
        }
        return false;
    }


    //ftp上传
    function ftpUpload()
    {
        Validator::notEmpty(array("relativePath"));
        $relativePath = $_REQUEST["relativePath"];
        $otherConfig = CommonUtils::readConfig()->configs->other;
        $ftpConfig = $otherConfig->ftp;
        $serial_number = $otherConfig->interact_live->serial_number;

        //写入正在上传的文件信息到配置文件
        $ftpFileNamePath = __DIR__ . "/runtime/ftpFileName.txt";
        $logPath = __DIR__ . "/runtime/ftpStatus.txt";

        $log = file_get_contents($logPath);
        $content = explode("\r", $log);
        $content = explode(" ", $content[sizeof($content) - 1])[0];
        if (is_numeric($content) && $content !== "100" && trim($log) != "") {
            die(json_encode(Msg::failed("有其他视频文件正在上传，请稍后再试")));
        }
        //检查文件是否已存在
        if ($this->ftpFileExist($ftpConfig, $serial_number, $relativePath, 1)) {
            die(json_encode(Msg::failed("该视频文件上传中或已上传，请勿重复操作")));
        }
        file_put_contents($ftpFileNamePath, $relativePath);
        //上传文件
        exec("$this->curl  --ftp-create-dirs -T '$this->videoPath$relativePath'"
            . " '$ftpConfig->server/$serial_number/$relativePath'"
            . " -u $ftpConfig->user:$ftpConfig->password > /dev/null  2>$logPath &");
        CommonUtils::upload($relativePath);
        echo json_encode(Msg::success("视频文件开始从后台上传..."));

    }


    function login()
    {
        CommonUtils::upload("12312selkdgj sdgsdg ljl/lkjkg test sdkljgklsd saklfj");
    }

    function ftpStatus()
    {
        $content = explode("\r", file_get_contents(__DIR__ . "/runtime/ftpStatus.txt"));
        $arr = explode(" ", $content[sizeof($content) - 1]);
        $result = array();
        foreach ($arr as $value) {
            if (trim($value) !== "") {
                array_push($result, str_replace("\n", "", $value));
            }
        }
        if (sizeof($result) == 12) {
            echo json_encode(Msg::success(array(
                "relativePath" => file_get_contents(__DIR__ . "/runtime/ftpFileName.txt"),
                "status" => $result,
            )));;
        } else {
            echo json_encode(Msg::failed());;
        }
    }

    function softwareVersion()
    {
        echo json_encode(Msg::success(file_get_contents(__DIR__ . "/update/version.txt")));
    }


}

$main = new main();
$main->$action();

