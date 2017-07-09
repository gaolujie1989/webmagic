<?php
/**
 * @copyright Copyright (c) 2017
 */

namespace webmagic\scheduler;


use GuzzleHttp\Psr7\Request;

/**
 * Class ArrayScheduler
 * @package lib\webmagic\scheduler
 * @author Lujie.Zhou(gao_lujie@live.cn)
 */
class ArrayScheduler implements Scheduler, MonitorableScheduler
{
    public $currentIndex = 0;

    /**
     * @var array, the url list
     */
    public $queue = [];

    /**
     * @var array, the url index, you can get url by index number
     */
    public $queueIndex = [];

    /**
     * @var array, url extra data
     * format: [
     *      'http://www.baidu.com' => [
     *          'aa' => 'bb'
     *      ]
     * ]
     */
    public $extras = [];

    /**
     * @param int $index
     * @return bool|mixed
     * @inheritdoc
     */
    public function getUrl($index)
    {
        return isset($this->queueIndex[$index]) ? $this->queueIndex[$index] : false;
    }

    /**
     * @param string $url
     * @return bool|mixed
     * @inheritdoc
     */
    public function getExtra($url)
    {
        return isset($this->extras[$url]) ? $this->extras[$url] : [];
    }

    /**
     * @param string $url
     * @param array $extra
     * @inheritdoc
     */
    public function push($url, $extra = [])
    {
        if ($extra) {
            $this->extras[$url] = $extra;
        }
        $this->queue[$this->currentIndex] = $url;
        $this->queueIndex[$this->currentIndex] = $url;
        $this->currentIndex++;
    }

    /**
     * @return \Generator
     * @inheritdoc
     */
    public function poll()
    {
        $this->queueIndex = array_values($this->queue);
        $this->queue = array_values($this->queue);

        while ($this->queue) {
            $url = array_shift($this->queue);
            $extra = $this->getExtra($url);
            yield ['url' => $url, 'extra' => $extra];
        }
    }

    public function __invoke()
    {
        $this->poll();
    }

    /**
     * @return int
     */
    public function getLeftUrlsCount()
    {
        return count($this->queue);
    }

    /**
     * @return int
     */
    public function getTotalUrlsCount()
    {
        return count($this->queueIndex);
    }
}