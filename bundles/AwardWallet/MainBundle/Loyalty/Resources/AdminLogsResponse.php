<?php

namespace AwardWallet\MainBundle\Loyalty\Resources;

use JMS\Serializer\Annotation\Type;

class AdminLogsResponse
{
    /**
     * @var LogItem[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\LogItem>")
     */
    private $files;

    /**
     * @var string
     * @Type("string")
     */
    private $bucket;

    /**
     * @return LogItem[]
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @param LogItem[] $files
     * @return $this
     */
    public function setFiles($files)
    {
        $this->files = $files;

        return $this;
    }

    /**
     * @return string
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * @param string $bucket
     * @return $this
     */
    public function setBucket($bucket)
    {
        $this->bucket = $bucket;

        return $this;
    }
}
