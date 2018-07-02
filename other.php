<?php
/**
 * Created by PhpStorm.
 * User: 10324
 * Date: 2018/1/30
 * Time: 2:30
 */

require "utils/headers.php";
require "utils/CommonUtils.php";
require "utils/Validator.php";
require "utils/InteractUtils.php";

class other
{
    private $ip;
    private $port;

    public function __construct()
    {
        $this->ip = CommonUtils::getSystemConfig()["ip"];
        $this->port = CommonUtils::getSystemConfig()["port"];
    }

    public function __call($name, $arguments)
    {
        die(json_encode(Msg::validate("参数有误")));
    }

    function isStartLive()
    {
        echo json_encode(Msg::success(array(
            "live" => CommonUtils::readConfig()->configs->rtmp->rtmp,
//            "live" => 0,
            "switch" => CommonUtils::readConfig()->configs->misc->auto_switch,
        )));
    }


    private function getIpInfo($dev)
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
        //获取网关p
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


    private function ipSubZero($ip)
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


    private function testDev($dev)
    {
        exec("ip address show dev '$dev'", $result, $code);
        if ($code != 0) {
            die(json_encode(Msg::failed("操作失败，该网口未插网线")));
        }
    }

    function getIp()
    {
        echo json_encode(Msg::success(
            array(
                "eth0" => $this->getIpInfo("eth0"),
                "eth1" => $this->getIpInfo("eth1")
            )
        ));
    }


    function setIp()
    {
        Validator::notEmpty($_REQUEST, array("dev", "ip", "mask", "gateway"));

        $dev = $_REQUEST["dev"];
        $ip = $_REQUEST["ip"];
        $mask = $_REQUEST["mask"];
        $gateway = $_REQUEST["gateway"];
        $this->testDev($dev);

        //设置ip和子网掩码
        if ($mask == "无" || $ip == "无" || $mask == "000.000.000.000" || $ip == "000.000.000.000") {
            die(json_encode(Msg::failed("操作失败，ip或子网掩码未设置")));
        }
        $ip = $this->ipSubZero($ip);
        $mask = $this->ipSubZero($mask);
        exec("ifconfig '$dev' '$ip' netmask '$mask'", $result, $code);
        if ($code != 0) {
            die(json_encode(Msg::success("操作失败，请检查ip地址有误或子网掩码是否正确")));
        }

        //设置默认网关
        if ($gateway != "无" && $gateway != "000.000.000.000") {
            $gateway = $this->ipSubZero($gateway);
            if (!empty($this->getIpInfo($dev)["gateway"])) {
                exec("route delete default gw " . $this->getIpInfo($dev)["gateway"] . " '$dev'", $result, $code);
                if ($code != 0) {
                    die(json_encode(Msg::success("操作失败，请检查网关地址是否正确")));
                }
            }
            exec("route add default gw '$gateway' '$dev'", $result, $code);
            if ($code != 0) {
                die(json_encode(Msg::success("操作失败，请稍后再试或重启系统")));
            }
        }


        $allConfigs = CommonUtils::readConfig();
        $video_urls = $allConfigs->video_urls;
        $ips = $allConfigs->ips;
        if ("eth0" == $dev) {
            $ips->eth0 = array(
                "ip" => $ip,
                "mask" => $mask
            );
            for ($i = 0; $i <= 6; $i++) {
                if ($i != 5) {
                    $video_urls->$i = "http://" . $ip . ":8080/live/" . $i . ".flv";
                }
            }
        } else if ("eth1" == $dev) {
            $ips->eth1 = array(
                "ip" => $ip,
                "mask" => $mask
            );
            $i = 5;
            $video_urls->$i = "http://" . $ip . ":8080/live/" . 5 . ".flv";
        } else {
            die(json_encode(Msg::failed("操作失败")));
        }
        $allConfigs->video_urls = $video_urls;
        $allConfigs->ips = $ips;
        CommonUtils::writeConfig($allConfigs);


        echo json_encode(Msg::success("操作成功"));
    }

    function setTime()
    {

        Validator::notEmpty($_REQUEST, array("datetime"));
        $datetime = $_REQUEST["datetime"];
        exec("date -s '$datetime'", $result, $code);
        if ($code != 0) {
            die(json_encode(Msg::failed("操作失败，请稍后再试或重启系统")));
        } else {
            InteractUtils::socketSendAndRead($this->ip, $this->port, json_encode(array("type" => "13")));
            echo json_encode(Msg::success("操作成功"));
        }
    }

    function getTime()
    {
        $result = str_replace("\n", "", shell_exec("date +%s"));
        if (is_numeric($result)) {
            echo json_encode(Msg::success($result));
        } else {
            echo json_encode(Msg::failed());

        }
    }


}

$other = new other();
$other->$action();
