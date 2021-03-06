<?php
/**
 * @copyright Copyright (c) 2017
 */

namespace webmagic;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
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

    /** @var LoggerInterface */
    public $logger;

    public $clientOptions = [
        'verify' => false,
    ];

    public $requestOptions = [
        'cookie' => false,
    ];

    public function run()
    {
        $client = new Client($this->clientOptions);
        $pool = new Pool($client, $this->pollRequest(), [
            'concurrency' => $this->concurrency,
            'fulfilled' => function ($response, $index) {
                $url = $this->scheduler->getUrl($index);
                $extra = $this->scheduler->getExtra($url);

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
                $extra = $this->scheduler->getExtra($url);

                $this->log('Download Failed, Url: ' . $url . ', Reason: ' . $reason->getMessage());
                $this->retry($reason, $index, $url, $extra);
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();
    }

    public function pollRequest()
    {
        foreach ($this->scheduler->poll() as $item) {
            yield new Request('GET', $item['url'], $this->requestOptions);
        }
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
        if ($this->logger) {
            $this->logger->info($msg);
        } else {
            echo date('Y-m-d H:i:s') . ' ' . $msg, "\n";
        }
    }
}