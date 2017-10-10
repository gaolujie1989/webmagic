<?php
/**
 * @copyright Copyright (c) 2017
 */

namespace lib\webmagic\scheduler;


use webmagic\scheduler\MonitorableScheduler;
use webmagic\scheduler\Scheduler;

/**
 * Class RedisScheduler
 * @package lib\webmagic\scheduler
 * @author Lujie Zhou <gao_lujie@live.cn>
 */
class RedisScheduler implements Scheduler, MonitorableScheduler
{
    /**
     * @var array, the url index, you can get url by index number
     */
    public $queueIndex = [];

    /** @var \Redis */
    public $redis;

    public $host = '127.0.0.1';
    public $port = '6379';

    public $queueKey = 'spider_queue';
    public $extraKey = 'spider_extra';
    public $setKey = 'spider_set';

    public function getClient()
    {
        if (!$this->redis) {
            $this->redis = new \Redis();
            if (!$this->redis->pconnect($this->host, $this->port)) {
                throw new \Exception('Can not connect to Redis');
            }
        }
        return $this->redis;
    }

    public function getUrl($index)
    {
        return isset($this->queueIndex[$index]) ? $this->queueIndex[$index] : false;
    }

    public function getExtra($url)
    {
        $extra = $this->getClient()->hGet($this->extraKey, $url);
        return $extra ? unserialize($extra) : [];
    }

    public function push($url, $extra = [])
    {
        if ($extra) {
            $extra = serialize($extra);
            $this->getClient()->hSet($this->extraKey, $url, $extra);
        }

        $this->getClient()->rPush($this->queueKey, $url);
    }

    public function poll()
    {
        while ($url = $this->getClient()->lPop($this->queueKey)) {
            $this->queueIndex[] = $url;
            $extra = $this->getExtra($url);
            yield ['url' => $url, 'extra' => $extra];
        }
    }

    public function reset()
    {
        $this->getClient()->delete($this->queueKey);
        $this->getClient()->delete($this->extraKey);
        $this->getClient()->delete($this->setKey);
        $this->queueIndex = [];
    }

    public function getLeftUrlsCount()
    {
        return $this->getClient()->lLen($this->queueKey);
    }

    public function getTotalUrlsCount()
    {
        return count($this->queueIndex) + $this->getLeftUrlsCount();
    }
}