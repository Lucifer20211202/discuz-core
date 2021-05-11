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

use App\Common\ResponseCode;
use App\Repositories\UserRepository;
use DateTime;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Common\Utils;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Illuminate\Database\ConnectionInterface;

/**
 * @method  beforeMain($user)
 */
abstract class DzqController implements RequestHandlerInterface
{
    public $request;
    protected $requestId;
    protected $requestTime;
    protected $platform;
    protected $app;
    protected $openApiLog = true;//后台是否开启接口层日志
    protected $user = null;
    protected $isLogin = false;

    protected $isDebug = true;


    private $queryParams = [];
    private $parseBody = [];

    public $providers = [];

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;
        $this->queryParams = $request->getQueryParams();
        $this->parseBody = $request->getParsedBody();
        $this->requestId = Str::uuid();
        $this->requestTime = date('Y-m-d H:i:s');
        $this->app = app();
        $this->registerProviders();
        $this->user = $request->getAttribute('actor');
        $this->c9IbQHXVFFWu($this->user);//添加辅助函数

        try {
            if (!$this->checkRequestPermissions(app(UserRepository::class))) {
                throw new PermissionDeniedException();
            }
        } catch (PermissionDeniedException $e) {
            $this->outPut(ResponseCode::UNAUTHORIZED, $e->getMessage());
        }

        $this->main();
    }

    /*
     * 控制器权限检查，默认是无权限访问，每个接口必须重写该方法，按实际情况处理权限检查
     *
     * 权限检查失败时，
     * 如果需要返回自定义错误消息，则抛出 PermissionDeniedException
     * 否则直接返回 false 即可
     */
    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        // TODO 权限改完后，改为默认 false
        return true;
    }

    /*
     * 控制器业务逻辑
     */
    abstract public function main();

    public function c9IbQHXVFFWu($name)
    {
        if (method_exists($this, 'beforeMain')) {
            $this->beforeMain($name);
        }
    }

    /*
     * 引入providers
     */
    public function registerProviders()
    {
        if (!empty($this->providers)) {
            foreach ($this->providers as $val) {
                $this->app->register($val);
            }
        }
    }


    /*
     * 接口入参
     */
    public function inPut($name, $checkValid = true)
    {
        $p = '';
        if (isset($this->queryParams[$name])) {
            $p = $this->queryParams[$name];
        }
        if (isset($this->parseBody[$name])) {
            $p = $this->parseBody[$name];
        }
        return $p;
    }

    /*
     * 接口出参
     */
    public function outPut($code, $msg = '', $data = [])
    {
        Utils::outPut($code, $msg, $data, $this->requestId, $this->requestTime);
    }

    /*
     * 入参判断
     */
    public function dzqValidate($inputArray, array $rules, array $messages = [], array $customAttributes = [])
    {
        try {
            $validate = app('validator');
            $validate->validate($inputArray, $rules);
        } catch (\Exception $e) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, $e->getMessage());
        }
    }

    /*
     * 分页
     */
    public function pagination($currentPage, $perPage, \Illuminate\Database\Eloquent\Builder $builder, $toArray = true)
    {
        $currentPage = $currentPage >= 1 ? intval($currentPage) : 1;
        $perPageMax = 50;
        $perPage = $perPage >= 1 ? intval($perPage) : 20;
        $perPage > $perPageMax && $perPage = $perPageMax;
        $count = $builder->count();
        $builder = $builder->offset(($currentPage - 1) * $perPage)->limit($perPage)->get();
        $builder = $toArray ? $builder->toArray() : $builder;
        $url = $this->request->getUri();
        $port = $url->getPort();
        $port = $port == null ? '' : ':' . $port;
        parse_str($url->getQuery(), $query);
        $queryFirst = $queryNext = $queryPre = $query;
        $queryFirst['page'] = 1;
        $queryNext['page'] = $currentPage + 1;
        $queryPre['page'] = $currentPage <= 1 ? 1 : $currentPage - 1;

        $path = $url->getScheme() . '://' . $url->getHost() . $port . $url->getPath() . '?';
        return [
            'pageData' => $builder,
            'currentPage' => $currentPage,
            'perPage' => $perPage,
            'firstPageUrl' => urldecode($path . http_build_query($queryFirst)),
            'nextPageUrl' => urldecode($path . http_build_query($queryNext)),
            'prePageUrl' => urldecode($path . http_build_query($queryPre)),
            'pageLength' => count($builder),
            'totalCount' => $count,
            'totalPage' => $count % $perPage == 0 ? $count / $perPage : intval($count / $perPage) + 1
        ];
    }

    public function preloadPaginiation($pageCount, $perPage, \Illuminate\Database\Eloquent\Builder $builder, $toArray = true)
    {

        $perPage = $perPage >= 1 ? intval($perPage) : 20;
        $perPageMax = 50;
        $perPage > $perPageMax && $perPage = $perPageMax;
        $count = $builder->count();
        $builder = $builder->offset(0)->limit($pageCount * $perPage)->get();
        $builder = $toArray ? $builder->toArray() : $builder;
        $url = $this->request->getUri();
        $port = $url->getPort();
        $port = $port == null ? '' : ':' . $port;
        parse_str($url->getQuery(), $query);
        $ret = new \Illuminate\Database\Eloquent\Collection();
        $currentPage = 1;
        $totalCount = $count;
        $totalPage = $count % $perPage == 0 ? $count / $perPage : intval($count / $perPage) + 1;
        while ($currentPage <= $pageCount) {
            $queryFirst = $queryNext = $queryPre = $query;
            $queryFirst['page'] = 1;
            $queryNext['page'] = $currentPage + 1;
            $queryPre['page'] = $currentPage <= 1 ? 1 : $currentPage - 1;
            $path = $url->getScheme() . '://' . $url->getHost() . $port . $url->getPath() . '?';
            $pageData = $builder->slice(($currentPage - 1) * $perPage, $perPage);
            if ($pageData->isEmpty()) {
                break;
            }
            $item = new \Illuminate\Database\Eloquent\Collection([
                'pageData' => $pageData,
                'currentPage' => $currentPage,
                'perPage' => $perPage,
                'firstPageUrl' => urldecode($path . http_build_query($queryFirst)),
                'nextPageUrl' => urldecode($path . http_build_query($queryNext)),
                'prePageUrl' => urldecode($path . http_build_query($queryPre)),
                'pageLength' => count($pageData),
                'totalCount' => $totalCount,
                'totalPage' => $totalPage
            ]);
            $ret->add($item);
            $currentPage++;
        }
        return $ret;
    }

    /**
     * @param DateTime|null $date
     * @return string|null
     */
    protected function formatDate(DateTime $date = null)
    {
        if ($date) {
            return $date->format(DateTime::RFC3339);
        }
    }

    /**
     * camelData
     * @param array|string $arr 原数组
     * @param bool|null $ucfirst 首字母大小写，false 小写，true 大写
     */
    public function camelData($arr, $ucfirst = false)
    {
        if (is_object($arr) && is_callable([$arr, 'toArray'])) $arr = $arr->toArray();
        if (!is_array($arr)) {
            //如果非数组原样返回
            return $arr;
        }
        $temp = [];
        foreach ($arr as $key => $value) {
            $key1 = Str::camel($key);           // foo_bar  --->  fooBar
            if ($ucfirst) $key1 = Str::ucfirst($key1);
            $value1 = self::camelData($value);
            $temp[$key1] = $value1;
        }
        return $temp;

    }

    public function info($tag, $params = [])
    {
        if (is_array($params)) {
            app('log')->info($tag . '::' . json_encode($params, 256));
        } else {
            app('log')->info($tag . '::' . $params);
        }
    }

    private $connection = null;

    public function openQueryLog()
    {
        $connection = app(ConnectionInterface::class);
        $this->connection = $connection;
        $connection->enableQueryLog();
    }

    public function closeQueryLog()
    {
        if (!empty($this->connection)) {
            dd(json_encode($this->connection->getQueryLog(), 256));
        }
    }

    /**
     * @desc 获取数据库实例
     * @param $array
     * @return ConnectionInterface
     */
    public function getDB()
    {
        return app(ConnectionInterface::class);
    }

    public function getIpPort()
    {
        $serverParams = $this->request->getServerParams();
        $ip = ip($serverParams);
        $port = !empty($serverParams['REMOTE_PORT']) ? $serverParams['REMOTE_PORT'] : 0;
        return [$ip, $port];
    }

    public function cacheInstance()
    {
        return app('cache');
    }
}
