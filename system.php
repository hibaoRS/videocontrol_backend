<?php
require "utils/Validator.php";
require "utils/controller.php";
require "utils/InteractUtils.php";
require "utils/CommonUtils.php";

class system extends controller
{

    //端口
    private $qt_port;
    //ip
    private $qt_ip;

    public function __construct()
    {
        $config = CommonUtils::getSystemConfig();
        $this->qt_port = $config["qt_port"];
        $this->qt_ip = $config["qt_ip"];
    }


    function getInfo()
    {
        if (PHP_OS == "Linux") {
            Validator::notEmpty( array("command"));
            $command = $_REQUEST["command"];

            if ($result = InteractUtils::socketSendAndRead($this->qt_ip, $this->qt_port, $command)) {
                echo json_encode(Msg::success(json_decode($result)));
            } else {
                echo json_encode(Msg::failed("未知错误"));
            }
        }else{
            echo json_encode(Msg::failed("服务器端非Linux系统"));
        }
    }

    function recordLiveState()
    {
        echo json_encode(Msg::success(InteractUtils::recordLiveState()), true);
    }


}

$system = new system();
$system->$action();
