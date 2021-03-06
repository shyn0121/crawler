<?php

namespace Crawler\Components\Downloader;

use Symfony\Component\EventDispatcher\Event;

/**
 * 请求事件对象
 * 包含发起请求的下载器实例
 *
 * @author LL
 */
class RequestEvent extends Event
{
    /**
     * 下载器对象
     *
     * @var DownloaderInterface
     */
    public $downloader;

    /**
     * @param DownloaderInterface $downloader
     */
    public function __construct(DownloaderInterface $downloader)
    {
        $this->downloader = $downloader;
    }
}