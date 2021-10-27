<?php
/**
 * Copyright (C) 2021 Tencent Cloud.
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

namespace Discuz\Base;


class DzqCacheV2
{

    const CACHE_TTL = true;

    public static function set($key, $value, $ttl = 0)
    {
        if (self::CACHE_TTL) {
            if ($ttl == 0) {
                return app('cache')->put($key, $value, 10 * 60);
            } else {
                return app('cache')->put($key, $value, $ttl);
            }
        }
        return app('cache')->put($key, $value);
    }

    public static function get($key, callable $callBack = null)
    {
        $data = app('cache')->get($key);
        if ($data) {
            $ret = $data;
        } else {
            if (!empty($callBack)) {
                $ret = $callBack();
                self::set($key, $ret);
            } else {
                $ret = false;
            }
        }
        return $ret;
    }

    public static function del($key)
    {
        return app('cache')->forget($key);
    }

    public static function clear()
    {
        return app('cache')->flush();
    }

    public static function hSet($key, $hashKey, $value)
    {
        $pKey = md5($key . $hashKey);
        return self::set($pKey, $value);
    }

    public static function hGet($key, $hashKey, callable $callBack = null)
    {
        $pKey = md5($key . $hashKey);
        return self::get($pKey, $callBack);
    }

    public static function h2Set($key, $hashKey1, $hashKey2, $value)
    {
        $pKey = md5($key . $hashKey1 . $hashKey2);
        return self::set($pKey, $value);
    }

    public static function h2Get($key, $hashKey1, $hashKey2, callable $callBack = null)
    {
        $pKey = md5($key . $hashKey1 . $hashKey2);
        return self::get($pKey, $callBack);
    }

    public static function hDel($key, $hashKey)
    {
        $pKey = md5($key . $hashKey);
        return self::del($pKey);
    }

    public static function h2Del($key, $hashKey1, $hashKey2)
    {
        $pKey = md5($key . $hashKey1 . $hashKey2);
        return self::del($pKey);
    }

    public static function hMSet($key, $values, $index)
    {

    }

    public static function hMGet($key, $hashKeys, callable $callBack = null, $index = null, $mutiColumn = false)
    {

    }
}
