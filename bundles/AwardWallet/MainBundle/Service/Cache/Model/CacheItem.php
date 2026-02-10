<?php

namespace AwardWallet\MainBundle\Service\Cache\Model;

/**
 * Basic cache item.
 */
class CacheItem
{
    public const FLAG_GZIP = 1;
    public const FLAG_EMPTY = 2;

    protected $data;
    /**
     * @var array
     */
    protected $tags;
    /**
     * @var int|float
     */
    protected $casToken;
    /**
     * @var string
     */
    protected $key;
    /**
     * @var int
     */
    protected $expiration;
    /**
     * @var int
     */
    private $flags;
    /**
     * size info.
     *
     * @var array
     */
    private $info;
    /**
     * microtime of item stored in cache.
     *
     * @var ?float
     */
    private $ctime;

    /**
     * @param string $key
     * @param int $expiration
     * @throws \RuntimeException
     */
    public function __construct($key, $internalValue, $casToken, $expiration = null)
    {
        $this->key = $key;
        $this->casToken = $casToken;
        $this->expiration = $expiration;

        $this->tags = $internalValue['tags'] ?? [];
        $this->data = $internalValue['data'] ?? null;
        $this->flags = $internalValue['flags'] ?? 0;
        $this->ctime = $internalValue['ctime'] ?? null;

        $this->info = [];

        if (null === $casToken) {
            throw new \UnexpectedValueException(sprintf('Empty CAS token with "%s" key', $key));
        }
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return array
     */
    public function getInternalValue()
    {
        return [
            'data' => $this->data,
            'tags' => $this->tags,
            'flags' => $this->flags,
            'ctime' => $this->ctime,
        ];
    }

    /**
     * @return int|float
     */
    public function getCasToken()
    {
        return $this->casToken;
    }

    /**
     * @return int
     */
    public function getExpiration()
    {
        return $this->expiration;
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @return CacheItem
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return CacheItem
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @param $flag int
     * @return int
     */
    public function hasFlag($flag)
    {
        return $flag & $this->flags;
    }

    /**
     * @param $flag int
     * @return $this
     */
    public function setFlag($flag)
    {
        $this->flags |= $flag;

        return $this;
    }

    /**
     * @return $this
     */
    public function unsetFlag($flag)
    {
        $this->flags &= ~$flag;

        return $this;
    }

    /**
     * @return int
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * @param $flags int
     * @return $this
     */
    public function setFlags($flags)
    {
        $this->flags = $flags;

        return $this;
    }

    public function getCtime(): ?float
    {
        return $this->ctime;
    }

    /**
     * @param string
     * @return $this
     */
    public static function createEmptyItem(string $key, array $internalValue = [], ?int $expiration = null)
    {
        return (new self($key, $internalValue, -1, $expiration))->setFlag(self::FLAG_EMPTY);
    }
}
