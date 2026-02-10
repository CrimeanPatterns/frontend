<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

use Symfony\Component\HttpFoundation\JsonResponse;

trait JsonTrait
{
    protected function jsonResponse($data, int $code = 200, int $options = 0): JsonResponse
    {
        $response = new JsonResponse(null, $code);

        if ($options) {
            $response->setEncodingOptions($options);
        }
        $response->setData($data);

        return $response;
    }

    protected function successJsonResponse(array $data = [], int $code = 200): JsonResponse
    {
        $successData = ['success' => true];

        if (count($data) !== 0) {
            $successData = array_merge($data, $successData);
        }

        return $this->jsonResponse($successData, $code);
    }

    protected function errorJsonResponse(string $error, array $data = [], int $code = 200): JsonResponse
    {
        $errorData = ['error' => $error];

        if (count($data) !== 0) {
            $errorData = array_merge($data, $errorData);
        }

        return $this->jsonResponse($errorData, $code);
    }

    /**
     * @param bool|string $result
     */
    protected function maybeJsonResponse($result): JsonResponse
    {
        return $result === true ? $this->successJsonResponse() : $this->errorJsonResponse($result);
    }
}
