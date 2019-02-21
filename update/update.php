<?php
/**
 * Created by PhpStorm.
 * User: 10324
 * Date: 2018/11/6
 * Time: 15:29
 */

require_once "../utils/controller.php";
require_once "../utils/CommonUtils.php";
require_once "../utils/InteractUtils.php";

class update extends controller
{
    function run()
    {
        $unValid = json_encode(Msg::failed("上传的不是有效的升级包"));
        if (!array_key_exists("file", $_FILES)) {
            die($unValid);
        }
        $file = $_FILES["file"];
        if (!$file || strpos($file["name"], ".zip") === false) {
            die($unValid);
        }
        $projectPath = "/nand/nginx/html/";
        $phpPath = $projectPath . "videocontrol/";
        $updatePath = __DIR__ . "/";
        $tempPath = $updatePath . "temp/";
        $backupPath = $updatePath . "backup/";
        //备份运行时文件
        $this->deleteTemp($tempPath, $backupPath);
        exec("cd '$phpPath';cp -r static customHtml config/runtime_config.json config/time.json config/usedCodes.json config/videocontrol.db '$backupPath' -r");
        $zipFile = $tempPath . "update.zip";
        $zipContentPath = $tempPath . "content/";
        move_uploaded_file($file["tmp_name"], $zipFile);
        //解压升级包
        mkdir($zipContentPath, 777, true);
        exec("unzip $zipFile -d $zipContentPath");
        $versionFile = $zipContentPath . "version.txt";
        $oldVersionFile = "$updatePath" . "version.txt";
        if (!file_exists($versionFile)) {
            $this->deleteTemp($tempPath, $backupPath);
            die($unValid);
        }
        if (!(file_get_contents($versionFile) > file_get_contents($oldVersionFile))) {
            $this->deleteTemp($tempPath, $backupPath);
            die(json_encode(Msg::failed("上传的升级包是旧版本升级包")));
        }
        //删除原来版本
        exec("cd '$projectPath';rm -rf  `ls | grep -v 'videocontrol\|disk'`");
        exec("cd '$phpPath';rm -rf  `ls | grep -v 'update'`");
        //复制新版本
        exec("cp -r $zipContentPath* $projectPath", $result, $code);
        //复制运行时文件
        exec("cp -r $backupPath* $phpPath");
        exec("cp -r $versionFile $oldVersionFile");
        //重启
        $ip = CommonUtils::getSystemConfig()["ip"];
        $port = CommonUtils::getSystemConfig()["port"];
        echo json_encode(Msg::success());
        $this->deleteTemp($tempPath, $backupPath);
        InteractUtils::socketSendAndRead($ip, $port, json_encode(array("type" => "14")));

    }

    private function deleteTemp($tempPath, $backupPath)
    {
        exec("rm -rf $tempPath*");
        exec("rm -rf $backupPath*");
    }

}

$update = new update();
$update->$action();

