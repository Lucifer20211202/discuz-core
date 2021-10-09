<?php

namespace Discuz\Common;

use App\Common\CacheKey;
use App\Common\DzqConst;
use App\Common\ResponseCode;
use Discuz\Base\DzqCache;
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
        if (!empty($headers['referer']) && stristr(json_encode($headers['referer']), 'servicewechat.com')) {
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
            app('log')->info('result error:' . $code . ' api:' . $request->getUri()->getPath() . ' msg:' . $msg);
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
        } elseif (!empty($dzqLog['openApiLog'])) {
            DzqLog::inPut(DzqLog::LOG_API);
            DzqLog::outPut($ret, DzqLog::LOG_API);
        }

        $crossHeaders = DiscuzResponseFactory::getCrossHeaders();
        foreach ($crossHeaders as $k => $v) {
            header($k . ':' . $v);
        }
        header('Content-Type:application/json; charset=utf-8', true, 200);
        header('Dzq-CostTime:' . ((microtime(true) - DISCUZ_START) * 1000) . 'ms');
        exit(json_encode($ret, 256));
    }

    public static function getPluginList()
    {
        $cacheConfig = DzqCache::get(CacheKey::PLUGIN_LOCAL_CONFIG);
        if ($cacheConfig) return $cacheConfig;
        $pluginDir = base_path('plugin');
        $directories = array_diff(scandir($pluginDir), ['.', '..']);
        $plugins = [];
        foreach ($directories as $dirName) {
            $subPlugins = array_diff(scandir($pluginDir . '/' . $dirName), ['.', '..']);
            $configName = '';
            $viewName = '';
            $databaseName = '';
            $consoleName = '';
            foreach ($subPlugins as $item) {
                switch (strtolower($item)) {
                    case 'config.php':
                        $configName = $item;
                        break;
                    case 'view':
                        $viewName = $item;
                        break;
                    case 'database':
                        $databaseName = $item;
                        break;
                    case 'console':
                        $consoleName = $item;
                        break;
                }
            }
            if ($configName == '') {
                continue;
            }
            $basePath = $pluginDir . '/' . $dirName . '/';
            $configPath = $basePath . $configName;
            $viewPath = $viewName == '' ? null : $basePath . $viewName . '/';
            $databasePath = $databaseName == '' ? null : $basePath . $databaseName . '/';
            $consolePath = $consoleName == '' ? null : $basePath . $consoleName . '/';
            $config = require($configPath);
            if ($config['status'] == DzqConst::BOOL_YES) {
                $config['plugin_' . $config['app_id']] = [
                    'base' => $basePath,
                    'view' => $viewPath,
                    'database' => $databasePath,
                    'console' => $consolePath,
                    'config' => $configPath
                ];
            }

            if (isset($config['app_id']) && $config['app_id'] != '6130acd182770') {
                $plugins[$config['app_id']] = $config;
            }
        }
        DzqCache::set(CacheKey::PLUGIN_LOCAL_CONFIG, $plugins, 5 * 60);
        return $plugins;
    }

    public static function runConsoleCmd($cmd, $params)
    {
        $reader = function & ($object, $property) {
            return \Closure::bind(function & () use ($property) {
                return $this->$property;
            }, $object, $object)->__invoke();
        };
        $console = app()->make(\Discuz\Console\Kernel::class);
        $console->call($cmd, $params);
        $lastOutput = $reader($console, 'lastOutput');
        return $lastOutput->fetch();
    }

    public static function endWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($haystack, -$length) === $needle);
    }

    public static function startWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($haystack, $length) === $needle);
    }

    public static function downLoadFile($url, $path = '')
    {
        $url = self::ssrfDefBlack($url,$host);
        if (!$url) return false;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch,CURLOPT_HTTPHEADER,['HOST: '.$host]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        ob_start();
        curl_exec($ch);
        $content = ob_get_contents();
        ob_end_clean();
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($code == 200) {
            if (empty($path)) {
                return $content;
            } else {
                return @file_put_contents($path, $content);
            }
        }
        return false;
    }

    public static function ssrfDefBlack($url,&$originHost='')
    {
        $url = parse_url($url);
        if (isset($url['port'])) {
            $url['path'] = ':' . $url['port'] . $url['path'];
        }
        if (isset($url['scheme'])) {
            if (!($url['scheme'] === 'http' || $url['scheme'] === 'https')) {
                return false;
            }
        }
        $host = $url['host'];
        if (filter_var($host, FILTER_VALIDATE_IP)) {  //t2
            return false;
        } else {
            $ip = gethostbyname($host);
            if ($ip === $host || self::isInnerIp($ip)) {
                return false;
            }
            $query = $url['query'] ?? '';
            $originHost = $host;
            return $url['scheme'] . '://' . $url['host'] . $url['path'] . '?' . $query;
        }
    }

    public static function isInnerIp($ip)
    {
        $ips = app(\App\Settings\SettingsRepository::class)->get('inner_net_ip');
        $ips = json_decode($ips, true);
        if ($ips === null) return null;
        $ipLong = ip2long($ip);
        $ret = false;
        foreach ($ips as $ipNet) {
            $ipArr = explode('/', $ipNet);
            $p1 = $ipArr[0];
            $p2 = $ipArr[1] ?? 24;
            $net = ip2long($p1) >> $p2;
            if ($ipLong >> $p2 === $net) {
                $ret = true;
                break;
            }
        }
        return $ret;
    }

    public static function isCosUrl($url)
    {
        $parseUrl = parse_url($url);
        $host = $parseUrl['host'];
        $path = $parseUrl['path'];
        $domain = Request::capture()->getHost();
        if (!(self::endWith($host, 'myqcloud.com') || strstr($host, $domain)) || !strstr($path, 'public/attachments')) {
            return false;
        }
        return true;
    }
}
