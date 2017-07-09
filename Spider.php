<?php
/**
 * @copyright Copyright (c) 2017
 */

namespace webmagic;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;
use webmagic\pipeline\Pipeline;
use webmagic\scheduler\MonitorableScheduler;
use webmagic\scheduler\Scheduler;

/**
 * Class Spider
 * @package app\modules\webmagic
 */
class Spider
{
    public $downloader;

    /** @var Scheduler|MonitorableScheduler */
    public $scheduler;

    /** @var Pipeline[] */
    public $pipelines = [];

    public $failCallback;

    public $startUrls = [];

    public $concurrency = 1;

    public function __construct()
    {
        $this->failCallback = function ($reason, $index, $url, $extra) {
            /** @var RequestException $reason */
            $this->scheduler->push($url);
            $this->log('Push to scheduler, Url: ' . $url);
        };
    }

    public function run()
    {
        $client = new Client(['verify' => false]);
        $pool = new Pool($client, $this->scheduler->poll(), [
            'concurrency' => $this->concurrency,
            'fulfilled' => function ($response, $index) {
                $url = $this->scheduler->getUrl($index);
                $extra = $this->scheduler->getExtra($index);

                $this->log('Download Success, Url: ' . $url
                    . ', Queue Left: ' . $this->scheduler->getLeftUrlsCount()
                    . ', Memory Usage: ' . round(memory_get_usage() / 1024 / 1024, 2) . 'MB');
                /** @var Response $response */
                foreach ($this->pipelines as $pipeline) {
                    try {
                        $pipeline->process($response, $index, $url, $extra);
                    } catch (\Exception $e) {
                        $this->log('Pipeline Error: ' . $e->getMessage());
                    }
                }
            },
            'rejected' => function ($reason, $index) {
                /** @var RequestException $reason */
                $url = $this->scheduler->getUrl($index);
                $extra = $this->scheduler->getExtra($index);

                $this->log('Download Failed, Url: ' . $url . ', Reason: ' . $reason->getMessage());
                $this->retry($reason, $index, $url, $extra);
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();
    }

    /**
     * @param RequestException $reason
     * @param $index
     * @param $url
     * @param $extra
     */
    public function retry($reason, $index, $url, $extra)
    {
        $this->scheduler->push($url, $extra);
    }

    public function log($msg)
    {
        echo date('Y-m-d H:i:s') . ' ' . $msg, "\n";
    }
}