<?php

namespace Discuz\Common;

use App\Common\ResponseCode;
use Discuz\Base\DzqLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Discuz\Http\DiscuzResponseFactory;

/**
 * Copyright (C) 2020 Tencent Cloud.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
class Utils
{
    /**
     * 判断设备
     *
     * @return bool
     * isMobile
     */
    public static function requestFrom()
    {
        $request = app('request');
        $headers = $request->getHeaders();
        $server = $request->getServerParams();
        if(!empty($headers['referer']) && stristr(json_encode($headers['referer']),'servicewechat.com')){
            return PubEnum::MinProgram;
        }
//        app('log')->info('get_request_from_for_test_' . json_encode(['headers' => $headers, 'server' => $server], 256));
        $requestFrom = PubEnum::PC;
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset($server['HTTP_X_WAP_PROFILE'])) {
            $requestFrom = PubEnum::H5;
        }

        // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset($server['HTTP_VIA']) && stristr($server['HTTP_VIA'], 'wap')) {
            $requestFrom = PubEnum::H5;
        }

        $user_agent = '';
        if (isset($server['HTTP_USER_AGENT']) && !empty($server['HTTP_USER_AGENT'])) {
            $user_agent = $server['HTTP_USER_AGENT'];
        }

        // 如果是 Windows PC 微信浏览器，返回 true 直接访问 index.html，不然打开是空白页
        if (stristr($user_agent, 'Windows NT') && stristr($user_agent, 'MicroMessenger')) {
            $requestFrom = PubEnum::H5;
        }

        $mobile_agents = [
            'iphone', 'android', 'phone', 'mobile', 'wap', 'netfront', 'java', 'opera mobi',
            'opera mini', 'ucweb', 'windows ce', 'symbian', 'series', 'webos', 'sony', 'blackberry', 'dopod',
            'nokia', 'samsung', 'palmsource', 'xda', 'pieplus', 'meizu', 'midp', 'cldc', 'motorola', 'foma',
            'docomo', 'up.browser', 'up.link', 'blazer', 'helio', 'hosin', 'huawei', 'novarra', 'coolpad',
            'techfaith', 'alcatel', 'amoi', 'ktouch', 'nexian', 'ericsson', 'philips', 'sagem', 'wellcom',
            'bunjalloo', 'maui', 'smartphone', 'iemobile', 'spice', 'bird', 'zte-', 'longcos', 'pantech',
            'gionee', 'portalmmm', 'jig browser', 'hiptop', 'benq', 'haier', '^lct', '320x320', '240x320',
            '176x220', 'windows phone', 'cect', 'compal', 'ctl', 'lg', 'nec', 'tcl', 'daxian', 'dbtel', 'eastcom',
            'konka', 'kejian', 'lenovo', 'mot', 'soutec', 'sgh', 'sed', 'capitel', 'panasonic', 'sonyericsson',
            'sharp', 'panda', 'zte', 'acer', 'acoon', 'acs-', 'abacho', 'ahong', 'airness', 'anywhereyougo.com',
            'applewebkit/525', 'applewebkit/532', 'asus', 'audio', 'au-mic', 'avantogo', 'becker', 'bilbo',
            'bleu', 'cdm-', 'danger', 'elaine', 'eric', 'etouch', 'fly ', 'fly_', 'fly-', 'go.web', 'goodaccess',
            'gradiente', 'grundig', 'hedy', 'hitachi', 'htc', 'hutchison', 'inno', 'ipad', 'ipaq', 'ipod',
            'jbrowser', 'kddi', 'kgt', 'kwc', 'lg ', 'lg2', 'lg3', 'lg4', 'lg5', 'lg7', 'lg8', 'lg9', 'lg-', 'lge-',
            'lge9', 'maemo', 'mercator', 'meridian', 'micromax', 'mini', 'mitsu', 'mmm', 'mmp', 'mobi', 'mot-',
            'moto', 'nec-', 'newgen', 'nf-browser', 'nintendo', 'nitro', 'nook', 'obigo', 'palm', 'pg-',
            'playstation', 'pocket', 'pt-', 'qc-', 'qtek', 'rover', 'sama', 'samu', 'sanyo', 'sch-', 'scooter',
            'sec-', 'sendo', 'sgh-', 'siemens', 'sie-', 'softbank', 'sprint', 'spv', 'tablet', 'talkabout',
            'tcl-', 'teleca', 'telit', 'tianyu', 'tim-', 'toshiba', 'tsm', 'utec', 'utstar', 'verykool', 'virgin',
            'vk-', 'voda', 'voxtel', 'vx', 'wellco', 'wig browser', 'wii', 'wireless', 'xde', 'pad', 'gt-p1000'
        ];
        foreach ($mobile_agents as $device) {
            if (stristr($user_agent, $device)) {
                $requestFrom = PubEnum::H5;
                break;
            }
        }

        return $requestFrom;
    }

    public static function isMobile()
    {
        $reqType = self::requestFrom();
        if ($reqType == PubEnum::PC) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * v2,v3接口输出
     * @param $code
     * @param string $msg
     * @param array $data
     * @param null $requestId
     * @param null $requestTime
     */
    public static function outPut($code, $msg = '', $data = [], $requestId = null, $requestTime = null)
    {
        $request = app('request');

        $apiPath = $request->getUri()->getPath();
        $api = str_replace(['/apiv3/', '/api/'], '', $apiPath);
        $dzqLog = null;
        $hasLOG = app()->has(DzqLog::APP_DZQLOG);
        if ($hasLOG) {
            $dzqLog = app()->get(DzqLog::APP_DZQLOG);
        }

        if (empty($msg)) {
            if (ResponseCode::$codeMap[$code]) {
                $msg = ResponseCode::$codeMap[$code];
            }
        }

        $isDebug = app()->config('debug');
        if ($msg != '') {
            if (stristr($msg, 'SQLSTATE')) {
                app('log')->info('database-error:' . $msg . ' api:' . $request->getUri()->getPath());
                !$isDebug && $msg = '数据库异常';
            } else if (stristr($msg, 'called') && stristr($msg, 'line')) {
                app('log')->info('internal-error:' . $msg . ' api:' . $request->getUri()->getPath());
                !$isDebug && $msg = '内部错误';
            }
        }

        if ($code != 0) {
            app('log')->info('result error:' . $code.' api:'.$request->getUri()->getPath().' msg:'.$msg);
        }

        $ret = [
            'Code' => $code,
            'Message' => $msg,
            'Data' => $data,
            'RequestId' => empty($requestId) ? Str::uuid() : $requestId,
            'RequestTime' => empty($requestTime) ? date('Y-m-d H:i:s') : $requestTime
        ];

        if (strpos($api, 'backAdmin') === 0) {
            DzqLog::inPut(DzqLog::LOG_ADMIN);
            DzqLog::outPut($ret, DzqLog::LOG_ADMIN);
        } elseif (! empty($dzqLog['openApiLog'])) {
            DzqLog::inPut(DzqLog::LOG_API);
            DzqLog::outPut($ret, DzqLog::LOG_API);
        }

        $crossHeaders = DiscuzResponseFactory::getCrossHeaders();
        foreach ($crossHeaders as $k => $v) {
            header($k . ':' . $v);
        }
        header('Content-Type:application/json; charset=utf-8', true, 200);
        header('Dzq-CostTime:'.((microtime(true) - DISCUZ_START)*1000).'ms');
        exit(json_encode($ret, 256));
    }
}
