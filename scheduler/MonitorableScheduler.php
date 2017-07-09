<?php
/**
 * @copyright Copyright (c) 2017
 */

namespace webmagic\scheduler;

/**
 * Interface MonitorableScheduler
 * @package webmagic\scheduler
 * @author Lujie.Zhou(gao_lujie@live.cn)
 */
interface MonitorableScheduler
{
    /**
     * @return int
     */
    public function getLeftUrlsCount();

    /**
     * @return int
     */
    public function getTotalUrlsCount();
}