<?php

/**
 * Discuz & Tencent Cloud
 * This is NOT a freeware, use is subject to license terms
 */

namespace Discuz\Qcloud\Services;

use TencentCloud\Mps\V20190612\Models\DescribeTranscodeTemplatesRequest;
use TencentCloud\Vod\V20180717\Models\DeleteMediaRequest;
use TencentCloud\Vod\V20180717\Models\DescribeSnapshotByTimeOffsetTemplatesRequest;
use TencentCloud\Vod\V20180717\Models\DescribeStorageDataRequest;
use TencentCloud\Vod\V20180717\Models\DescribeTaskDetailRequest;
use TencentCloud\Vod\V20180717\Models\ModifyMediaInfoRequest;
use TencentCloud\Vod\V20180717\Models\ProcessMediaRequest;
use TencentCloud\Vod\V20180717\VodClient;

class VodService extends AbstractService
{
    const ENDPOINT = 'vod.tencentcloudapi.com';

    const REGION = '';

    protected $qcloudAppId;

    protected $qcloudSecretId;

    protected $qcloudSecretKey;

    protected $qcloudVodTranscode;

    protected $qcloudVodSubAppId;

    protected $qcloudVodCoverTemplate;

    public function __construct($config)
    {
        parent::__construct($config);

        $this->qcloudAppId  = $config->get('qcloud_app_id');
        $this->qcloudSecretId  = $config->get('qcloud_secret_id');
        $this->qcloudSecretKey = $config->get('qcloud_secret_key');
        $this->qcloudVodTranscode = (int) $config->get('qcloud_vod_transcode');
        $this->qcloudVodSubAppId = (int) $config->get('qcloud_vod_sub_app_id');
        $this->qcloudVodCoverTemplate = (int) $config->get('qcloud_vod_cover_template') ?: 10;
    }

    /**
     * @param $FileId
     * @return mixed
     */
    public function deleteMedia($FileId)
    {
        $clientRequest = new DeleteMediaRequest();

        $params = [
            'FileId' => $FileId,
            'SubAppId' => $this->qcloudVodSubAppId,
        ];

        $clientRequest->fromJsonString(json_encode($params));

        return $this->client->DeleteMedia($clientRequest);
    }

    /**
     * @param $FileId
     * @return mixed
     */
    public function transcodeVideo($FileId)
    {
        $clientRequest = new ProcessMediaRequest();

        $params = [
            'MediaProcessTask' => [
                'TranscodeTaskSet' => [
                    ['Definition'=>$this->qcloudVodTranscode]
                ],
                'CoverBySnapshotTaskSet' => [
                    ['Definition'=>$this->qcloudVodCoverTemplate,'PositionType'=>'Time','PositionValue'=>0]
                ],
            ],
            'FileId' => $FileId,
            'SubAppId' => $this->qcloudVodSubAppId,
        ];
        $clientRequest->fromJsonString(json_encode($params));

        return $this->client->ProcessMedia($clientRequest);
    }

    /**
     * 修改视频过期时间（默认不过期）
     * @param $FileId
     * @param string $ExpireTime
     * @return mixed
     */
    public function modifyMedia($FileId, $ExpireTime = '9999-12-31T23:59:59Z')
    {
        $clientRequest = new ModifyMediaInfoRequest();

        $params = [
            'FileId' => $FileId,
            'ExpireTime' => $ExpireTime,
            'SubAppId' => $this->qcloudVodSubAppId,
        ];

        $clientRequest->fromJsonString(json_encode($params));

        return $this->client->ModifyMediaInfo($clientRequest);
    }

    /**
     * 获取云点播存储情况
     * @param $sub_app_id
     * @return mixed
     */
    public function describeStorageData($sub_app_id)
    {
        $clientRequest = new DescribeStorageDataRequest();

        $params = [
            'SubAppId' => (int) $sub_app_id?:$this->qcloudVodSubAppId,
        ];
        $clientRequest->fromJsonString(json_encode($params));

        return $this->client->DescribeStorageData($clientRequest);
    }

    /**
     * 查询通知的任务
     * @param $task_id
     * @return mixed
     */
    public function describeTaskDetail($task_id)
    {
        $clientRequest = new DescribeTaskDetailRequest();

        $params = [
            'TaskId' => $task_id,
            'SubAppId' => $this->qcloudVodSubAppId,
        ];
        $clientRequest->fromJsonString(json_encode($params));

        return $this->client->DescribeTaskDetail($clientRequest);
    }

    /**
     * 获取自定义时间截图模板数据
     * @param $template_id
     * @return mixed
     */
    public function DescribeSnapshotByTimeOffsetTemplates($template_id)
    {

        $clientRequest = new DescribeSnapshotByTimeOffsetTemplatesRequest();

        $params = [
            'Definitions' => [(int)$template_id],
            'SubAppId' => $this->qcloudVodSubAppId,
        ];
        $clientRequest->fromJsonString(json_encode($params));

        return $this->client->DescribeSnapshotByTimeOffsetTemplates($clientRequest);
    }

    /**
     * 获取转码模板数据
     * @param $template_id
     * @return mixed
     */
    public function DescribeTranscodeTemplates($template_id)
    {

        $clientRequest = new DescribeTranscodeTemplatesRequest();

        $params = [
            'Definitions' => [(int)$template_id],
            'SubAppId' => $this->qcloudVodSubAppId,
        ];
        $clientRequest->fromJsonString(json_encode($params));

        return $this->client->DescribeTranscodeTemplates($clientRequest);
    }

    protected function getClient()
    {
        return new VodClient($this->cred, self::REGION, $this->clientProfile);
    }

    protected function setEndpoint()
    {
        return self::ENDPOINT;
    }
}
