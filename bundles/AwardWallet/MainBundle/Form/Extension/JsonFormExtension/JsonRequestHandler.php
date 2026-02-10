<?php

namespace AwardWallet\MainBundle\Form\Extension\JsonFormExtension;

use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Request;

class JsonRequestHandler implements RequestHandlerInterface
{
    /**
     * Submits a form if it was submitted.
     *
     * @param FormInterface $form the form to submit
     * @param mixed $request the current request
     */
    public function handleRequest(FormInterface $form, $request = null)
    {
        if (!$request instanceof Request) {
            throw new UnexpectedTypeException($request, 'Symfony\Component\HttpFoundation\Request');
        }

        $data = self::parse($request);

        if (isset($data)) {
            $form->submit($data);
        }
    }

    /**
     * Parses json request data.
     *
     * @return array|null
     */
    public static function parse(Request $request)
    {
        $requestContent = $request->getContent();

        if (!empty($requestContent)) {
            $requestData = @json_decode($requestContent, true);

            if (JSON_ERROR_NONE === json_last_error()) {
                return $requestData;
            }
        }

        return null;
    }

    public function isFileUpload($data)
    {
        return false;
    }
}
