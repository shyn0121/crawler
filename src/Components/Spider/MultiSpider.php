<?php

namespace Crawler\Components\Spider;

use Crawler\Components\Filter\FilterInterface;
use Crawler\Components\Downloader\DownloaderInterface;
use Crawler\Components\Queue\QueueInterface;
use Crawler\Components\MatchLink\MatchLinkInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Crawler\EventListener\EventTag;
use Crawler\Container\Container;

/**
 * MultiSpider抓取引擎
 *
 * @author LL
 */
class MultiSpider implements SpiderInterface
{
    /**
     * 下载器
     *
     * @var DownloaderInterface
     */
    private $downloader;

    /**
     * 过滤器
     *
     * @var FilterInterface
     */
    private $filter;

    /**
     * 队列
     *
     * @var QueueInterface
     */
    private $queue;

    /**
     * 链接匹配
     *
     * @var MatchLinkInterface
     */
    private $matchLink;

    /**
     * 事件派发
     *
     * @var EventDispatcher
     */
    private $event;

    /**
     * 容器实例
     *
     * @var Container
     */
    private $container;

    /**
     * 当前正在处理的链接
     *
     * @var string
     */
    private $currentLink = '';

    /**
     * 当前链接对应标识
     *
     * @var mixed
     */
    private $tag;

    public function __construct(
        DownloaderInterface $downloader,
        QueueInterface $queue,
        FilterInterface $filter,
        MatchLinkInterface $matchLink,
        EventDispatcher $event,
        Container $container
    ) {
        $this->downloader = $downloader;
        $this->queue = $queue;
        $this->filter = $filter;
        $this->matchLink = $matchLink;
        $this->event = $event;
        $this->container = $container;
    }

    /**
     * 获取抓取内容
     *
     * @param  array $link 包含['path' => '', 'method' => '']
     * @return mixed
     */
    public function getContent($link)
    {
        $this->currentLink = $link['path'];
        $response = $this->downloader->download($link['path'], $link['method']);

        $this->dispatch(EventTag::SPIDER_NEXT_LINK_AFTER, ["response" => $response]);

        return $response;
    }

    /**
     * 清洗数据
     *
     * @param  mixed $data
     * @return void
     */
    public function filterData($data): void
    {
        //设置tag
        $this->setTag();

        //过滤链接和数据
        $linkRes = $this->filter->filterLink($this->tag, $data);
        $dataRes = $this->filter->filterData($this->tag, $data);

        $this->dispatch(EventTag::SPIDER_FILTER_CONTENT_AFTER, [
            "linkRes" => $linkRes,
            "dataRes" => $dataRes
        ]);
    }

    /**
     * 准备下一次的抓取
     *
     * @return mixed
     */
    public function next()
    {
        $this->matchLink = '';
        $this->tag = '';

        //从队列中获取下一个链接
        $nextLink = $this->queue->pop();

        $this->dispatch(EventTag::SPIDER_NEXT_LINK_AFTER, ["nextLink" => $nextLink]);

        return $nextLink;
    }

    /**
     * 抓取结束
     *
     * @return mixed
     */
    public function end(): void
    {
        exit(0);
    }

    /**
     * 获取当前链接所对应的tag
     */
    private function setTag(): void
    {
        $this->tag = $this->matchLink->match($this->currentLink);
    }

    /**
     * 触发事件
     *
     * @param string $eventTag 事件名称
     * @param array  $params   事件参数
     */
    private function dispatch(string $eventTag, array $params): void
    {
        $this->event->dispatch($eventTag, $this->container->make('SpiderEvent', [
            "spider" => $this,
            "params" => $params
        ]));
    }
}