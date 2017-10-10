<?php
/**
 * @copyright Copyright (c) 2017
 */

namespace lib\webmagic\pipeline;


use GuzzleHttp\Psr7\Response;
use MongoDB\Client;
use MongoDB\Collection;
use webmagic\pipeline\Pipeline;

/**
 * Class MongoPipeline
 * @package lib\webmagic\pipeline
 * @author Lujie Zhou <gao_lujie@live.cn>
 */
class MongoPipeline implements Pipeline
{

    /** @var Client */
    public $client;
    /** @var Collection */
    public $collection;

    public $uri = 'mongodb://10.100.12.243:27017/';
    public $uriOptions = [];
    public $driverOptions = [];

    public $databaseName = 'spider';
    public $collectionName = 'spider_data';

    /**
     * @var \Closure
     */
    public $extractCallback;

    public function getCollection()
    {
        if (!$this->collection) {
            if (!$this->client) {
                $this->client = new Client($this->uri, $this->uriOptions, $this->driverOptions);
            }
            $this->collection = $this->client->selectCollection($this->databaseName, $this->collectionName);
        }

        return $this->collection;
    }

    /**
     * @param Response $response
     * @param string $index
     * @param string $url
     * @param array $extra
     * @return int
     * @inheritdoc
     */
    public function process($response, $index, $url, $extra)
    {
        $html = $response->getBody()->getContents();
        if ($this->extractCallback && is_callable($this->extractCallback)) {
            $data = call_user_func($this->extractCallback, $html, $url, $extra);
        } else {
//            $html = base64_encode(gzdeflate($html));
            $data = array_merge($extra, ['url' => $url, 'html' => $html]);
        }

        return $this->getCollection()->insertOne($data);
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