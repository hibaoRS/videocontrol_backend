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


class main extends controller
{

    private $ip;
    private $port;
//    private $qt_ip;
//    private $qt_port;
    private $videoPath;


    public function __construct()
    {

        $this->ip = CommonUtils::getSystemConfig()["ip"];
        $this->port = CommonUtils::getSystemConfig()["port"];
//        $this->qt_port = CommonUtils::getSystemConfig()["qt_port"];
//        $this->qt_ip = CommonUtils::getSystemConfig()["qt_ip"];
//        $this->videoPath = "D:/environment/Apache24/htdocs/videocontrol/videos/";
        $this->videoPath = "/media/disk/videos/";
    }


    function getConfig()
    {
        echo json_encode(Msg::success(CommonUtils::readConfig()));
    }

    function getConfigOptions()
    {
        $configOptions = require __DIR__ . "/config/configOptions.php";
        die(json_encode(Msg::success($configOptions)));
    }

    function getConfigOptionsInverse()
    {
        $configOptions = require __DIR__ . "/config/configOptions.php";
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
        while (($folderName = readdir($dir)) != false) {

            if ($folderName == "." || $folderName == "..") {
                continue;
            }

            $folderPath = $this->videoPath . $folderName;
            if (filetype($folderPath) == "dir") {
                $secondDir = opendir($folderPath);
                $children = array();
                while (($fileName = readdir($secondDir)) != false) {
                    if (strtolower(substr(trim($fileName), -4)) === ".mp4") {
                        array_push($children, array(
                            "key" => $key,
                            "name" => $fileName,
                            "relativePath" => $folderName . "/" . $fileName,
                            "type" => 1
                        ));
                        $key++;
                    }
                }

                closedir($secondDir);

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


    function setConfig()
    {

        $state = InteractUtils::recordLiveState();

        if ($state->recording == 1) {
            die(json_encode(Msg::failed("操作失败，在配置之前，请先停止录制")));
        }

        $fp = fopen(__DIR__ . "/configLock", "w+");
        if (flock($fp, LOCK_EX | LOCK_NB)) {
            Validator::notEmpty($_REQUEST, array("configs"));
            $configs = json_decode($_REQUEST["configs"]);
            $allConfigs = CommonUtils::readConfig();
            if (InteractUtils::checkAndSendConfig($allConfigs->configs, $configs)) {
                $allConfigs->configs = $configs;
                CommonUtils::writeConfig($allConfigs);
                CommonUtils::writeToSystem($allConfigs->configs);
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
        Validator::notEmpty($_REQUEST, array("key", "value"));

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
        $state = InteractUtils::recordLiveState();

        if ($state->recording == 0) {

            $fp = fopen(__DIR__ . "/configLock", "w+");
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                $systemConfig = CommonUtils::getSystemConfig();
                $path = $this->videoPath . date("Y-m-d", time()) . "/";
                if (!file_exists($path)) {
                    mkdir($path, 0777, true);
                }
                $save_path = $path . CommonUtils::readConfig()->recordName;
                if (file_exists($save_path . $systemConfig["suffix"])) {
                    $save_path = $save_path . "_" . date("Y_m_d_h_i_s", time()) . $systemConfig["suffix"];
                } else {
                    $save_path = $save_path . $systemConfig["suffix"];
                }
//            $save_path = "/nfsroot/1.mp4";

                $data = array("type" => "7", "record" => array(
                    "film_path" => $save_path,
                    "segment_time" => strval(CommonUtils::readConfig()->sectionTime * 60)
                ));
                $response = InteractUtils::socketSendAndRead($this->ip, $this->port, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                if (json_decode($response)->code == 1) {
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
                $data = array("type" => "8");
                $response = InteractUtils::socketSendAndRead($this->ip, $this->port, json_encode($data));
                if (json_decode($response)->code == 1) {
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
                $data = array("type" => "11");
                $response = InteractUtils::socketSendAndRead($this->ip, $this->port, json_encode($data));
                if (json_decode($response)->code == 1) {
                    //保存录制直播状态
                    CommonUtils::saveRecordLiveState(array(
                        "living" => 1,
                        "liveTime" => floatval($_REQUEST["time"])
                    ));
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
                $data = array("type" => "12");
                $response = InteractUtils::socketSendAndRead($this->ip, $this->port, json_encode($data));
                if (json_decode($response)->code == 1) {
                    //保存录制直播状态
                    CommonUtils::saveRecordLiveState(array(
                        "living" => 0
                    ));
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


    function manualSwitch()
    {
        $state = InteractUtils::recordLiveState();
        if ($state->autoSwitch == 1) {

            $fp = fopen(__DIR__ . "/configLock", "w+");
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                $data = array("type" => "5");
                $response = InteractUtils::socketSendAndRead($this->ip, $this->port, json_encode($data));
                if (json_decode($response)->code == 1) {
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
                } else {
                    echo json_encode(Msg::failed("操作失败，请稍后再试或重启系统"));
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

    function autoSwitch()
    {
        $state = InteractUtils::recordLiveState();
        if ($state->autoSwitch != 1) {

            $fp = fopen(__DIR__ . "/configLock", "w+");
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                $data = array("type" => "4");
                $response = InteractUtils::socketSendAndRead($this->ip, $this->port, json_encode($data));
                if (json_decode($response)->code == 1) {
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
                } else {
                    echo json_encode(Msg::failed("操作失败，请稍后再试或重启系统"));
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


    function changeLayout()
    {
        Validator::notEmpty($_REQUEST, array("data"));
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
            Validator::notEmpty($_REQUEST, array("chn"));

            $chn = $_REQUEST["chn"];
            if ($chn > 5 ||
                ($chn != 5 && json_decode(file_get_contents(__DIR__ . "/run/signal_states.json"))->signal[$chn] == 255)) {
                die(json_encode(Msg::failed("操作失败")));
            }

            $response = InteractUtils::socketSendAndRead(
                $this->ip,
                $this->port,
                json_encode(array(
                    "type" => "6",
                    "switch_chn" => array("chn" => $chn . "")
                )));
            if (json_decode($response)->code == 1) {
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
            Validator::notEmpty($_REQUEST, array("second_dir"));
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
            Validator::notEmpty($_REQUEST, array("name"));
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
        InteractUtils::socketSendAndRead($this->ip, $this->port, json_encode(array("type" => "15")));
        echo json_encode(Msg::success("操作成功"));
    }

    function reboot()
    {
        InteractUtils::socketSendAndRead($this->ip, $this->port, json_encode(array("type" => "14")));
        echo json_encode(Msg::success("操作成功"));
    }


    function getSignals()
    {
        echo json_encode(Msg::success(json_decode(file_get_contents(__DIR__ .
            "/run/signal_states.json"))));
    }

    function setLayout()
    {
        Validator::notEmpty($_REQUEST, array("layout_name"));
        $allConfig = CommonUtils::readConfig();
        $allConfig->layout = $_REQUEST["layout_name"];
        CommonUtils::writeConfig($allConfig);
        echo json_encode(Msg::success("操作成功"));
    }


    /**function setIp()
     * {
     * Validator::notEmpty($_REQUEST, array("name", "ip"));
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
        Validator::notEmpty($_REQUEST, array("key", "val", "configKey"));
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
        Validator::notEmpty($_REQUEST, array("time"));
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
            $response = InteractUtils::socketSendAndRead($this->ip, $this->port, json_encode(array("type" => "17")));
            if (json_decode($response)->code == 1) {
                echo json_encode(Msg::success("操作成功"));
            } else {
                echo json_encode(Msg::failed("操作失败，请稍后再试"));
            }
        }
    }

    //设置备用url地址
    function setStandByUrl()
    {
        Validator::notEmpty($_REQUEST, array("index"));

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
        Validator::notEmpty($_REQUEST, array("addr", "cmd", "value"));
        $response = InteractUtils::socketSendAndRead($this->ip, $this->port, json_encode(
            array(
                "type" => "18",
                "camera" => array(
                    "addr" => strval($_REQUEST["addr"]),
                    "cmd" => strval($_REQUEST["cmd"]),
                    "value" => strval($_REQUEST["value"]),
                )
            )
        ));
        if (@json_decode($response)->code == 1) {
            echo json_encode(Msg::success("操作成功"));
        } else {
            echo json_encode(Msg::failed("操作失败，请稍后再试"));
        }
    }

    //设置摄像头参数
    function setCameraValue()
    {
        Validator::notEmpty($_REQUEST, array("camera", "focal_length", "zoom_speed"));
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


}

$main = new main();
$main->$action();

