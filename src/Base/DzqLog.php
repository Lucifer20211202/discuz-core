<?php

namespace Discuz\Base;

class DzqLog
{
    const APP_LOG = 'APP_LOG';//容器全局变量
    public static $data = [];

    private static function getAppLog($key)
    {
        $data = null;
        $hasLOG = app()->has(DzqLog::APP_LOG);
        if ($hasLOG) {
            $dzqLog = app()->get(DzqLog::APP_LOG);
            $data = $dzqLog[$key] ?? null;
        }
        if (empty($data)) {
            $data = self::get($key);
        }
        return $data;
    }

    public static function get($key)
    {
        return app('request')->get($key);
    }

    public static function baseData($request = ''){
        $request        = self::getAppLog('request');
        $requestId      = self::getAppLog('requestId');
        $requestIP      = ip($request->getServerParams());
        $requestTarget  = $request->getRequestTarget();

        $user           = $request->getAttribute('actor');
        $userId         = !empty($user->id) ? $user->id : 0;

        $template =    '[requestId:]'       . $requestId
                    . ';[requestIP:]'       . $requestIP
                    . ';[requestTarget:]'   . $requestTarget
                    . ';[userId:]'          . $userId . PHP_EOL
        ;

        self::$data = [
            'template'      => $template,

            'request'       => $request,
            'requestId'     => $requestId,
            'requestIP'     => $requestIP,
            'requestTarget' => $requestTarget,

            'queryParams'   => $request->getQueryParams(),
            'parseBody'     => $request->getParsedBody(),

            'user'          => $user,
            'userId'        => $userId,
        ];
        return self::$data;
    }

    /*
     * 接口入参日志
     */
    public static function inPutLog($name = '',$logTye = 'log'){
        $baseData = self::baseData();
        $p = '';
        if (isset($baseData['queryParams'][$name])) {
            $p = $baseData['queryParams'][$name];
        }
        if (isset($baseData['parseBody'][$name])) {
            $p = $baseData['parseBody'][$name];
        }
        if (!is_array($p)) {
            app($logTye)->info(
                '[inPutParam:]'
                . $baseData['template']
                . ';[inPutData:]'
                . ';[' . $name . ':]' . json_encode($p)
            );
        } else {
            app($logTye)->info(
                '[inPutParam:]'
                . $baseData['template']
                . ';[inPutData:]' . json_encode($name)
            );
        }
    }

    /*
     * 接口出参日志
     */
    public static function outPutLog($code = '', $msg = '', $data = [], $logTye = 'log'){
        $baseData = self::baseData();
        app($logTye)->info(
            '[outPutParam:]'
            . $baseData['template']
            . ';[outPutData:]'
            . ';[code:]' . $code
            . ';[msg:]' . json_encode($msg)
            . ';[data:]' . json_encode($data)
            . PHP_EOL
        );
    }

    /*
     * 异常日志
     */
    public static function errorLog($errorMessage = '', $remake = '', $paramData = [], $logTye = 'errorLog'){
        $baseData = self::baseData();
        app($logTye)->info(
            $baseData['template']
            . ';[remake:]' . $remake
            . ';[paramData:]' . json_encode($paramData)
            . ';[errorMessage:]' . $errorMessage
        );
    }

    /*
     * 普通日志
     */
    public static function infoLog($remake = '', $paramData = [], $tag= '', $logTye = 'log'){
        $baseData = self::baseData();
        app($logTye)->info(
            '[infoParam:]'
            . $baseData['template']
            . ';[tag:]' . $tag
            . ';[remake:]' . $remake
            . ';[paramData:]' . json_encode($paramData)
        );
    }
}
