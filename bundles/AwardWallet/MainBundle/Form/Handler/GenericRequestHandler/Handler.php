<?php

namespace AwardWallet\MainBundle\Form\Handler\GenericRequestHandler;

use AwardWallet\MainBundle\Form\Handler\FormHandlerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class Handler
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var string[]
     */
    private $channels = [];
    /**
     * @var FormHandlerHelper
     */
    private $formHandlerHelper;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var HandlerContext
     */
    private $context;

    public function __construct(EventDispatcherInterface $eventDispatcher, FormHandlerHelper $formHandlerHelper, EntityManagerInterface $entityManager)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->formHandlerHelper = $formHandlerHelper;
        $this->entityManager = $entityManager;
        $this->context = new HandlerContext();
    }

    /**
     * @return Handler
     */
    public function addHandlerSubscriber(EventSubscriberInterface $platformSubscriber)
    {
        $this->eventDispatcher->addSubscriber($platformSubscriber);

        return $this;
    }

    public function setChannel($channels)
    {
        $this->channels = [$channels];
    }

    public function setChannels(...$channels)
    {
        if (is_array($channels[0])) {
            $this->channels = $channels[0];
        } else {
            $this->channels = $channels;
        }
    }

    public function handleRequest(FormInterface $form, Request $request)
    {
        if (!$this->channels) {
            throw new \UnexpectedValueException('Channels must be set.');
        }

        $this->fanoutDispatch('pre_handle', new HandlerEvent($this->context, $form, $request));

        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return false;
        }

        if (
            !($form->isSubmitted() && $form->isValid())
            || !$this->formHandlerHelper->isSynchronizedDeep($form)
        ) {
            $this->fanoutDispatch('on_invalid', new HandlerEvent($this->context, $form, $request));

            return false;
        }

        $this->fanoutDispatch('on_valid', new HandlerEvent($this->context, $form, $request));

        return true;
    }

    public function handleRequestTransactionally(FormInterface $form, Request $request)
    {
        $dbConnection = $this->entityManager->getConnection();
        $dbConnection->beginTransaction();

        try {
            if ($this->handleRequest($form, $request)) {
                $this->entityManager->flush();
                $dbConnection->commit();
                $this->fanoutDispatch('on_commit', $event = new HandlerEvent($this->context, $form, $request));

                if ($event->hasResponse()) {
                    return $event->getResponse();
                }
            } else {
                if ($dbConnection->isTransactionActive()) {
                    $dbConnection->rollBack();
                }
            }
        } catch (\Throwable $exception) {
            if ($dbConnection->isTransactionActive()) {
                $dbConnection->rollBack();
            }

            $this->fanoutDispatch('on_exception', $event = new HandlerEvent($this->context, $form, $request, $exception));

            if ($event->hasResponse()) {
                return $event->getResponse();
            }

            throw $exception;
        }

        return null;
    }

    protected function fanoutDispatch($postfix, HandlerEvent $handlerEvent)
    {
        foreach ($this->channels as $channel) {
            $this->eventDispatcher->dispatch($handlerEvent, $channel . '.' . $postfix);
        }
    }
}
