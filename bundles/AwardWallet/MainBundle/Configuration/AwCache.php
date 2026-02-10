<?php

namespace AwardWallet\MainBundle\Configuration;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

/**
 * @Annotation
 */
class AwCache extends Cache
{
    /**
     * @var string
     */
    protected $etagContentHash;

    /**
     * @var bool
     */
    protected $noCache;

    /**
     * @var bool
     */
    protected $noStore;

    public function getAliasName()
    {
        return 'awcache';
    }

    /**
     * @return string
     */
    public function getEtagContentHash()
    {
        return $this->etagContentHash;
    }

    /**
     * @param string $etagContentHash
     */
    public function setEtagContentHash($etagContentHash)
    {
        $this->etagContentHash = $etagContentHash;
    }

    /**
     * @return bool
     */
    public function isNoCache()
    {
        return $this->noCache;
    }

    /**
     * @param bool $noCache
     */
    public function setNoCache($noCache)
    {
        $this->noCache = $noCache;
    }

    /**
     * @return bool
     */
    public function isNoStore()
    {
        return $this->noStore;
    }

    /**
     * @param bool $noStore
     */
    public function setNoStore($noStore)
    {
        $this->noStore = $noStore;
    }
}
