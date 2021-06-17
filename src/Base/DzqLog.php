<?php

namespace Discuz\Base;

use Illuminate\Support\Str;

class DzqLog
{
    const APP_LOG = 'APP_LOG';//容器全局变量

    private static function getAppLog()
    {
        $dzqLog = null;
        $hasLOG = app()->has(DzqLog::APP_LOG);
        if ($hasLOG) {
            $dzqLog = app()->get(DzqLog::APP_LOG);
        }
        return $dzqLog;
    }

    private static function baseData(){
        $appLog     = self::getAppLog();
        $requestId  = $appLog['requestId'] ?: Str::uuid();
        $requestIP  = $_SERVER['REMOTE_ADDR'] ?: '127.0.0.1';
        $userId     = $appLog['userId'] ?: 0;
        $url        = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $post       = null;
        if (empty($_POST)) {
            $content = file_get_contents('php://input');
            $post    = (array)json_decode($content, true);
        } else {
            $post    = $_POST;
        }

        return [
            'IO'        => '',
            'requestId' => $requestId,
            'ip'        => $requestIP,
            'userId'    => $userId,
            'url'       => $url,
            'post'      => $post,
        ];
    }

    //接口入参日志
    public static function inPutLog($logTye = 'log'){
        $baseData           = self::baseData();
        $baseData['IO']     = 'input';
        app($logTye)->info(json_encode($baseData));
    }

    //接口出参日志
    public static function outPutLog($code = '', $msg = '', $data = [], $logTye = 'log'){
        $baseData           = self::baseData();
        $baseData['IO']     = 'output';
        $baseData['code']   = $code;
        $baseData['msg']    = $msg;
        $baseData['data']   = $data;
        app($logTye)->info(json_encode($baseData).PHP_EOL);
    }

    //异常日志
    public static function errorLog($errorMessage = '', $remake = '', $paramData = [], $logTye = 'errorLog'){
        $baseData = self::baseData();
        $baseData['IO']             = 'errorOutput';
        $baseData['errorMessage']   = $errorMessage;
        $baseData['remake']         = $remake;
        $baseData['paramData']      = $paramData;
        app($logTye)->info(json_encode($baseData));
    }

    //普通日志
    public static function infoLog($remake = '', $paramData = [], $tag= '', $logTye = 'log'){
        $baseData = self::baseData();
        $baseData['IO']             = 'processOutput';
        $baseData['tag']            = $tag;
        $baseData['remake']         = $remake;
        $baseData['paramData']      = $paramData;
        app($logTye)->info(json_encode($baseData));
    }
}
