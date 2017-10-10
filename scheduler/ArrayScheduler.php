<?php
/**
 * @copyright Copyright (c) 2017
 */

namespace webmagic\scheduler;


/**
 * Class ArrayScheduler
 * @package lib\webmagic\scheduler
 * @author Lujie.Zhou(gao_lujie@live.cn)
 */
class ArrayScheduler implements Scheduler, MonitorableScheduler
{
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
        $this->queue[] = $url;
    }

    /**
     * @return \Generator
     * @inheritdoc
     */
    public function poll()
    {
        while ($this->queue) {
            $url = array_shift($this->queue);
            $this->queueIndex[] = $url;
            $extra = $this->getExtra($url);
            yield ['url' => $url, 'extra' => $extra];
        }
    }

    public function reset()
    {
        $this->queue = [];
        $this->extras = [];
        $this->queueIndex = [];
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
        return count($this->queueIndex) + count($this->queue);
    }
}