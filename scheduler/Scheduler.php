<?php
/**
 * @copyright Copyright (c) 2017
 */

namespace webmagic\scheduler;

/**
 * Interface Scheduler
 * @package webmagic\schedulers
 * @author Lujie.Zhou(gao_lujie@live.cn)
 */
interface Scheduler
{
    /**
     * @return \Generator
     */
    public function poll();

    /**
     * @param string $url
     * @param array $extra
     */
    public function push($url, $extra = []);

    /**
     * @param $index
     * @return bool|string
     */
    public function getUrl($index);

    /**
     * @param $index
     * @return bool|array
     */
    public function getExtra($index);
}