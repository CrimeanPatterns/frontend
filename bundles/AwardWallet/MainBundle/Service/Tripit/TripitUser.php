<?php

namespace AwardWallet\MainBundle\Service\Tripit;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Класс, предназначенный для работы с OAuth-токенами пользователя.
 *
 * @NoDI
 */
class TripitUser
{
    private const PARAM_OAUTH_TOKEN = 'oauth_token';
    private const PARAM_OAUTH_TOKEN_SECRET = 'oauth_token_secret';
    private const REQUEST_TOKEN = 'oauth_request_token';
    private const REQUEST_SECRET = 'oauth_request_secret';
    private const ACCESS_TOKEN = 'oauth_access_token';
    private const ACCESS_SECRET = 'oauth_access_secret';

    private Usr $user;
    private EntityManagerInterface $entityManager;

    public function __construct(Usr $user, EntityManagerInterface $entityManager)
    {
        $this->user = $user;
        $this->entityManager = $entityManager;
    }

    /**
     * Возвращает экземпляр текущего пользователя.
     */
    public function getCurrentUser(): Usr
    {
        return $this->user;
    }

    /**
     * Проверяет, есть ли у пользователя токен запроса, необходимый для авторизации.
     */
    public function hasRequestTokens(): bool
    {
        $data = $this->user->getTripitOauthToken();

        return is_array($data) && $data[self::REQUEST_TOKEN] !== null && $data[self::REQUEST_SECRET] !== null;
    }

    /**
     * Проверяет, есть ли у пользователя токен доступа, необходимый для осуществления запросов к API.
     */
    public function hasAccessTokens(): bool
    {
        $data = $this->user->getTripitOauthToken();

        return is_array($data) && $data[self::ACCESS_TOKEN] !== null && $data[self::ACCESS_SECRET] !== null;
    }

    public function getRequestToken(): string
    {
        return $this->user->getTripitOauthToken()[self::REQUEST_TOKEN];
    }

    public function getRequestSecret(): string
    {
        return $this->user->getTripitOauthToken()[self::REQUEST_SECRET];
    }

    /**
     * Записывает токены запроса для текущего пользователя.
     *
     * @param string $queryString url-кодированная строка, пришедшая в ответе от API
     * @return $this
     */
    public function setRequestToken(string $queryString): self
    {
        parse_str($queryString, $output);

        if ($this->isParamsExists($output)) {
            $this->user->setTripitOauthToken([
                self::REQUEST_TOKEN => $output[self::PARAM_OAUTH_TOKEN],
                self::REQUEST_SECRET => $output[self::PARAM_OAUTH_TOKEN_SECRET],
                self::ACCESS_TOKEN => null,
                self::ACCESS_SECRET => null,
            ]);
            $this->entityManager->flush();
        }

        return $this;
    }

    public function getAccessToken(): string
    {
        return $this->user->getTripitOauthToken()[self::ACCESS_TOKEN];
    }

    public function getAccessSecret(): string
    {
        return $this->user->getTripitOauthToken()[self::ACCESS_SECRET];
    }

    /**
     * Записывает токены доступа и удаляет токены запроса для текущего пользователя.
     *
     * @param string $queryString url-кодированная строка, пришедшая в ответе от API
     * @return $this
     */
    public function setAccessToken(string $queryString): self
    {
        parse_str($queryString, $output);

        if ($this->isParamsExists($output)) {
            $this->user->setTripitOauthToken([
                self::REQUEST_TOKEN => null,
                self::REQUEST_SECRET => null,
                self::ACCESS_TOKEN => $output[self::PARAM_OAUTH_TOKEN],
                self::ACCESS_SECRET => $output[self::PARAM_OAUTH_TOKEN_SECRET],
            ]);
            $this->entityManager->flush();
        }

        return $this;
    }

    /**
     * Удаляет токены доступа и токены запроса.
     *
     * @return $this
     */
    public function removeTokens(): self
    {
        $this->user->setTripitOauthToken([
            self::REQUEST_TOKEN => null,
            self::REQUEST_SECRET => null,
            self::ACCESS_TOKEN => null,
            self::ACCESS_SECRET => null,
        ]);
        $this->entityManager->flush();

        return $this;
    }

    /**
     * Проверяет, присутствуют ли в массиве параметры "token" и "token_secret".
     *
     * @param array $output массив с параметрами из строки запроса
     */
    private function isParamsExists(array $output): bool
    {
        return isset($output[self::PARAM_OAUTH_TOKEN]) && isset($output[self::PARAM_OAUTH_TOKEN_SECRET]);
    }
}
