<?php
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

namespace Discuz\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

abstract class DzqModel extends Model
{

    private static $instance;
//    public function __clone(){}

    public static function instance() {
        $class = get_called_class();
        if (!isset(self::$instance[$class])) {
            self::$instance[$class] = new static();
        }
        return self::$instance[$class];
    }
//添加sql
//    public function save(array $options = [])
//    {
//        $saved = parent::save($options);
//        if ($saved && ) {
//            $cacheKey = md5(URL::full());
//            Cache::forget($cacheKey);
//        }
//        return $saved;
//    }
}
