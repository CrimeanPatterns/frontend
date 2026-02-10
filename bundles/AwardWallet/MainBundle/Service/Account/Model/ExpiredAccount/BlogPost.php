<?php

namespace AwardWallet\MainBundle\Service\Account\Model\ExpiredAccount;

class BlogPost
{
    private string $title;

    private string $url;

    private string $previewUrl;

    public function __construct(string $title, string $url, string $previewUrl)
    {
        $this->title = $title;
        $this->url = $url;
        $this->previewUrl = $previewUrl;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getPreviewUrl(): string
    {
        return $this->previewUrl;
    }
}
