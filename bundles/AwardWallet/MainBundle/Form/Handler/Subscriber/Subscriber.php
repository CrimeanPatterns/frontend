<?php

namespace AwardWallet\MainBundle\Form\Handler\Subscriber;

use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Subscriber implements EventSubscriberInterface
{
    private static $count = 0;

    /**
     * @var callable
     */
    private $preHandle;
    /**
     * @var callable
     */
    private $onInvalid;
    /**
     * @var callable
     */
    private $onValid;
    /**
     * @var callable
     */
    private $onCommit;
    /**
     * @var callable
     */
    private $onException;

    public static function getSubscribedEvents()
    {
        return [
            'form.generic.pre_handle' => ['preHandle'],
            'form.generic.on_valid' => ['onValid', -1],
            'form.generic.on_invalid' => ['onInvalid', -1],
            'form.generic.on_commit' => ['onCommit'],
            'form.generic.on_exception' => ['onException'],
        ];
    }

    public function onCommit(HandlerEvent $event)
    {
        if ($this->onCommit) {
            return ($this->onCommit)($event);
        }

        return null;
    }

    public function onException(HandlerEvent $event)
    {
        if ($this->onException) {
            return ($this->onException)($event);
        }

        return null;
    }

    public function onInvalid(HandlerEvent $event)
    {
        if ($this->onInvalid) {
            return ($this->onInvalid)($event);
        }

        return null;
    }

    public function onValid(HandlerEvent $event)
    {
        if ($this->onValid) {
            return ($this->onValid)($event);
        }

        return null;
    }

    public function preHandle(HandlerEvent $event)
    {
        if ($this->preHandle) {
            return ($this->preHandle)($event);
        }

        return null;
    }

    public function getPreHandle(): ?callable
    {
        return $this->preHandle;
    }

    public function setPreHandle(callable $preHandle): self
    {
        $this->preHandle = $preHandle;

        return $this;
    }

    public function getOnInvalid(): ?callable
    {
        return $this->onInvalid;
    }

    public function setOnInvalid(callable $onInvalid): self
    {
        $this->onInvalid = $onInvalid;

        return $this;
    }

    public function getOnValid(): ?callable
    {
        return $this->onValid;
    }

    public function setOnValid(callable $onValid, $pri): self
    {
        $this->onValid = $onValid;

        return $this;
    }

    public function getOnCommit(): ?callable
    {
        return $this->onCommit;
    }

    public function setOnCommit(callable $onCommit): self
    {
        $this->onCommit = $onCommit;

        return $this;
    }

    public function getOnException(): ?callable
    {
        return $this->onException;
    }

    public function setOnException(callable $onException): self
    {
        $this->onException = $onException;

        return $this;
    }
}
