<?php

namespace AwardWallet\MainBundle\Entity\Query\UserConnectionsQuery;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\DateUtils;

/**
 * @NoDI
 */
class Connection implements \ArrayAccess
{
    /**
     * @var Useragent
     */
    private $useragent;
    /**
     * @var ?Useragent
     */
    private $reverseUseragent;
    /**
     * @var Usr
     */
    private $user;
    /**
     * @var array
     */
    private $methodMap;

    public function __construct(Useragent $useragent, ?Useragent $reverseUseragent = null)
    {
        $this->useragent = $useragent;
        $this->reverseUseragent = $reverseUseragent;
        $this->user = $useragent->getAgentid();
    }

    public static function new(Useragent $useragent, ?Useragent $reverseUseragent = null): self
    {
        return new self($useragent, $reverseUseragent);
    }

    public function getUseragent(): Useragent
    {
        return $this->useragent;
    }

    public function getReverseUseragent(): ?Useragent
    {
        return $this->reverseUseragent;
    }

    public function getUser(): ?Usr
    {
        return $this->user;
    }

    public function getAgentId(): ?int
    {
        return ($agent = $this->useragent->getAgentid()) ? $agent->getUserid() : null;
    }

    public function getUseragentId(): int
    {
        return $this->useragent->getUseragentid();
    }

    public function getShareDate(): ?string
    {
        return ($date = $this->useragent->getSharedate()) ? DateUtils::toSQLDateTime($date) : null;
    }

    public function getFullName(): ?string
    {
        if (!$this->useragent->getClientid()) {
            // family member
            return $this->useragent->getFullName();
        } else {
            return $this->useragent->getAgentid()->getFullName();
        }
    }

    public function isApproved(): ?bool
    {
        return $this->reverseUseragent ? $this->reverseUseragent->isApproved() : null;
    }

    public function getClientId(): ?int
    {
        return ($client = $this->useragent->getClientid()) ? $client->getUserid() : null;
    }

    public function getEmail(): ?string
    {
        return $this->useragent->getEmail();
    }

    public function getUserEmail(): ?string
    {
        if (
            ($isBusiness = (
                $this->user
                && (ACCOUNT_LEVEL_BUSINESS == $this->user->getAccountlevel())
            ))
            || \is_null($this->useragent->getClientid())
        ) {
            if (
                $isBusiness
                && ($bookerInfo = $this->user->getBookerInfo())
            ) {
                return $bookerInfo->getFromEmail();
            } else {
                return '';
            }
        } elseif ($this->user) {
            return $this->user->getEmail();
        } else {
            return null;
        }
    }

    public function getAccountLevel(): ?string
    {
        return $this->user ? $this->user->getAccountlevel() : null;
    }

    public function getPictureExt(): ?string
    {
        if (\is_null($this->useragent->getClientid())) {
            return $this->useragent->getPictureext();
        } else {
            return null;
        }
    }

    public function getPictureVer(): ?string
    {
        if (\is_null($this->useragent->getClientid())) {
            return $this->useragent->getPicturever();
        } else {
            return null;
        }
    }

    public function getUserPictureExt(): ?string
    {
        return $this->user ? $this->user->getPictureext() : null;
    }

    public function getUserPictureVer(): ?string
    {
        return $this->user ? $this->user->getPicturever() : null;
    }

    public function offsetExists($offset)
    {
        return
            (null !== ($method = $this->getMethodByOffset($offset)))
            && (null !== $this->$method());
    }

    public function offsetGet($offset)
    {
        if (null !== ($method = $this->getMethodByOffset($offset))) {
            return $this->$method();
        }

        throw new \LogicException('Undefined offset');
    }

    public function offsetSet($offset, $value)
    {
        $this->throwUnimplemented();
    }

    public function offsetUnset($offset)
    {
        $this->throwUnimplemented();
    }

    private function throwUnimplemented()
    {
        throw new \LogicException('Unimplemented!');
    }

    private function getMethodByOffset(string $offset): ?string
    {
        $offset = \strtolower($offset);

        if (isset($this->methodMap[$offset])) {
            return $this->methodMap[$offset];
        }

        foreach (
            [
                $offset,
                'get' . $offset,
                'is' . $offset,
            ] as $method
        ) {
            if (\method_exists($this, $method)) {
                return $this->methodMap[$offset] = $method;
            }
        }

        return null;
    }
}
