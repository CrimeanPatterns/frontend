<?php

namespace AwardWallet\MainBundle\Service\Blog\Model;

use AwardWallet\MainBundle\Service\Blog\Constants;

class PostItem
{
    public int $id;
    private string $title;
    private string $description;
    private string $thumbnail;
    private \DateTime $pubDate;
    private string $link;
    private int $commentsCount;
    private string $authorName;
    private string $authorLink;
    private array $authors = [];
    private array $categories = [];
    private array $tags = [];
    private array $reviewed = [];
    private array $meta = [];

    public function __construct(
        int $id,
        string $title,
        string $description,
        string $thumbnail,
        \DateTime $pubDate,
        string $link,
        int $commentsCount,
        string $authorName,
        string $authorLink,
        array $authors = [],
        array $categories = [],
        array $tags = [],
        array $reviewed = []
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->thumbnail = $thumbnail;
        $this->pubDate = $pubDate;
        $this->link = $link;
        $this->commentsCount = $commentsCount;
        $this->authorName = $authorName;
        $this->authorLink = $authorLink;
        $this->categories = $categories;
        $this->authors = $authors;
        $this->tags = $tags;
        $this->reviewed = $reviewed;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getThumbnail(): string
    {
        return $this->thumbnail;
    }

    public function setThumbnail(string $thumbnail): self
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    public function getPubDate(): \DateTime
    {
        return $this->pubDate;
    }

    public function setPubDate(\DateTime $pubDate): self
    {
        $this->pubDate = $pubDate;

        return $this;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function setLink(string $link): self
    {
        $this->link = $link;

        return $this;
    }

    public function getCommentsCount(): int
    {
        return $this->commentsCount;
    }

    public function setCommentsCount(int $commentsCount): self
    {
        $this->commentsCount = $commentsCount;

        return $this;
    }

    public function getAuthorName(): string
    {
        return $this->authorName;
    }

    public function setAuthorName(string $authorName): self
    {
        $this->authorName = $authorName;

        return $this;
    }

    public function getAuthorLink(): string
    {
        return $this->authorLink;
    }

    public function setAuthorLink(string $authorLink): self
    {
        $this->authorLink = $authorLink;

        return $this;
    }

    public function setCategories(array $categories): self
    {
        $this->categories = $categories;

        return $this;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function setAuthors(array $authors): self
    {
        $this->authors = $authors;

        return $this;
    }

    public function getAuthors(): array
    {
        return $this->authors;
    }

    public function getCategory(): array
    {
        $categories = Constants::CATEGORIES_ORDER;
        $categoryId = $this->categories[0]->catId ?? null;

        if (!array_key_exists($categoryId, $categories)) {
            return $categories[Constants::CATEGORY_AW_TIPS_AND_TRICKS_ID];
        }

        return $categories[$categoryId];
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    public function setReviewed(array $reviewed): self
    {
        $this->reviewed = $reviewed;

        return $this;
    }

    public function getReviewed(): array
    {
        return $this->reviewed;
    }

    public function setMeta(string $metaKey, $data): self
    {
        $this->meta[$metaKey] = $data;

        return $this;
    }

    public function getMeta(string $metaKey)
    {
        return $this->meta[$metaKey] ?? null;
    }
}
