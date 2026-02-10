<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use Base64Url\Base64Url;

/**
 * @NoDI()
 */
class NextPageToken
{
    public const DELIMITER = "||";
    /** @var \DateTime */
    private $postingDate;
    private $uuid;

    public function __construct(\DateTime $postingDate, $uuid)
    {
        $this->postingDate = $postingDate;
        $this->uuid = $uuid;
    }

    public function __toString()
    {
        return Base64Url::encode($this->postingDate->format('Y-m-d') . self::DELIMITER . $this->uuid);
    }

    public static function createFromString($data)
    {
        [$postingDate, $uuid] = explode(self::DELIMITER, Base64Url::decode($data));

        if (empty($postingDate) || empty($uuid)) {
            return null;
        }

        return new self(new \DateTime($postingDate), $uuid);
    }

    /**
     * @return \DateTime
     */
    public function getPostingDate()
    {
        return $this->postingDate;
    }

    public function getUuid()
    {
        return $this->uuid;
    }
}
