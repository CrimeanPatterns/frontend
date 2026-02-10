<?php

namespace AwardWallet\MainBundle\Service\RA\Flight\DTO;

class ApiSearchResult
{
    /**
     * @var ApiSearchRequest[]
     */
    private array $requests;

    public function __construct(array $requests)
    {
        $this->requests = $requests;
    }

    /**
     * @return ApiSearchRequest[]
     */
    public function getRequests(): array
    {
        return $this->requests;
    }

    /**
     * @return ApiSearchRequest[]
     */
    public function getSuccessRequests(): array
    {
        return array_filter($this->requests, function (ApiSearchRequest $request) {
            return !$request->hasError();
        });
    }

    /**
     * @return ApiSearchRequest[]
     */
    public function getRequestsWithError(): array
    {
        return array_filter($this->requests, function (ApiSearchRequest $request) {
            return $request->hasError();
        });
    }
}
