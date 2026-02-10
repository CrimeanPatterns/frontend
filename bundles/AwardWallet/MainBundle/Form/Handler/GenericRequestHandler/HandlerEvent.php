<?php

namespace AwardWallet\MainBundle\Form\Handler\GenericRequestHandler;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class HandlerEvent extends Event
{
    /**
     * @var FormInterface
     */
    private $form;
    /**
     * @var Request
     */
    private $request;

    private $data;

    private $response;
    /**
     * @var bool
     */
    private $responseIsSet = false;
    /**
     * @var \Throwable
     */
    private $exception;
    /**
     * @var HandlerContext
     */
    private $context;

    public function __construct(HandlerContext $context, FormInterface $form, Request $request, ?\Throwable $exception = null)
    {
        $this->form = $form;
        $this->request = $request;
        $this->exception = $exception;
        $this->context = $context;
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * @return HandlerEvent
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    public function getResponse()
    {
        if (!$this->responseIsSet) {
            throw new \LogicException('No response!');
        }

        return $this->response;
    }

    public function hasResponse(): bool
    {
        return $this->responseIsSet;
    }

    public function setResponse($response): void
    {
        $this->response = $response;
        $this->responseIsSet = true;
    }

    public function getException(): ?\Throwable
    {
        return $this->exception;
    }

    public function getContext(): HandlerContext
    {
        return $this->context;
    }
}
