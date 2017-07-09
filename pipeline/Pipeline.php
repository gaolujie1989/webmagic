<?php
/**
 * @copyright Copyright (c) 2017
 */

namespace webmagic\pipeline;


use GuzzleHttp\Psr7\Response;

/**
 * Interface Pipeline
 * @package webmagic\pipelines
 * @author Lujie.Zhou(gao_lujie@live.cn)
 */
interface Pipeline
{
    /**
     * @param Response $response
     * @param string $index
     * @param string $url
     * @param array $extra
     */
    public function process($response, $index, $url, $extra);
}