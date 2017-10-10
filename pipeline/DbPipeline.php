<?php
/**
 * @copyright Copyright (c) 2017
 */

namespace webmagic\pipeline;


use GuzzleHttp\Psr7\Response;
use Medoo\Medoo;

/**
 * Class HtmlDbPipeline
 * @package lib\webmagic\pipelines
 * @author Lujie.Zhou(gao_lujie@live.cn)
 */
class DbPipeline implements Pipeline
{
    /** @var Medoo */
    public $db;

    /**
     * @var array Medoo options
     * [
     *      'database_type' => 'mysql',
     *      'database_name' => 'name',
     *      'server' => 'localhost',
     *      'username' => 'your_username',
     *      'password' => 'your_password',
     * ]
     */
    public $dbOptions;

    public $table = 'spider_data';

    /**
     * @var \Closure
     */
    public $extractCallback;

    public function getDb()
    {
        if (!$this->db) {
            $this->db = new Medoo($this->dbOptions);
        }
        return $this->db;
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
            $data = array_merge($extra, ['url' => $url, 'html' => $html]);
        }
        return $this->getDb()->insert($this->table, $data);
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