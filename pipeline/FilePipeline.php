<?php
/**
 * @copyright Copyright (c) 2017
 */

namespace webmagic\pipeline;


use GuzzleHttp\Psr7\Response;

/**
 * Class HtmlDbPipeline
 * @package lib\webmagic\pipelines
 * @author Lujie.Zhou(gao_lujie@live.cn)
 */
class FilePipeline implements Pipeline
{
    public $path = '@runtime';

    /**
     * @var \Closure
     */
    public $fileNameCallback;

    /**
     * @param Response $response
     * @param string $index
     * @param string $url
     * @param array $extra
     * @inheritdoc
     */
    public function process($response, $index, $url, $extra)
    {
        $html = $response->getBody()->getContents();
        if ($this->fileNameCallback && is_callable($this->fileNameCallback)) {
            $fileName = call_user_func($this->fileNameCallback, $html, $url, $extra);
        } else {
            $fileName = $index . '.html';
        }
        file_put_contents($this->path . $fileName, $html);
    }

    /**
     * @param Response $response
     * @param $index
     * @param $url
     * @param $extra
     * @inheritdoc
     */
    public function __invoke($response, $index, $url, $extra)
    {
        $this->process($response, $index, $url, $extra);
    }
}