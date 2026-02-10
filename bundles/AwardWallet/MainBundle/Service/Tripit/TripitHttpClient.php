<?php

namespace AwardWallet\MainBundle\Service\Tripit;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Класс, отвечающий за формирование и отправку запросов к API.
 */
class TripitHttpClient
{
    private string $apiUrl = 'https://api.tripit.com';
    private string $apiVersion = 'v1';

    private OAuthConsumerCredentialFactory $credentialFactory;
    private \HttpDriverInterface $httpDriver;
    private RouterInterface $router;

    public function __construct(
        OAuthConsumerCredentialFactory $credentialFactory,
        \HttpDriverInterface $httpDriver,
        RouterInterface $router
    ) {
        $this->credentialFactory = $credentialFactory;
        $this->httpDriver = $httpDriver;
        $this->router = $router;
    }

    /**
     * Выполняет отправку HTTP-запроса к API.
     *
     * @param TripitUser $user экземпляр класса, в котором хранятся токены доступа
     * @param string $verb выполняемое действие
     * @param string|null $entity тип объекта
     * @param array|null $queryParams параметры, передающиеся в строке запроса
     * @param array|null $postParams параметры, передающиеся в теле запроса
     * @throws UnauthorizedHttpException генерируется, если токен доступа пользователя больше недействителен
     * @throws HttpException генерируется, если от API пришёл HTTP код "500"
     */
    public function request(TripitUser $user, string $verb, ?string $entity = null, ?array $queryParams = null, ?array $postParams = null): ?string
    {
        $baseUrl = $this->getBaseUrl($verb, $entity);
        $params = [];

        if ($queryParams) {
            $params = $queryParams;
            $pairs = [];

            foreach ($queryParams as $key => $value) {
                $pairs[] = urlencode($key) . '=' . urlencode($value);
            }
            $url = $baseUrl . '?' . implode('&', $pairs);
        } else {
            $url = $baseUrl;
        }

        if ($postParams === null) {
            $method = 'GET';
        } else {
            $params = $postParams;
            $method = 'POST';
        }

        $headers = ['Content-Type' => 'application/json'];
        $credential = $this->credentialFactory->create($user);
        $credential->setParams($params);
        $credential->authorize($headers, $method, $this->apiUrl, $baseUrl);
        $result = $this->httpDriver->request(new \HttpDriverRequest($url, $method, $postParams, $headers, 60));

        if ($result->httpCode == 401) {
            throw new UnauthorizedHttpException('Unauthorized');
        } elseif ($result->httpCode == 500) {
            throw new HttpException($result->httpCode, 'Internal Server Error');
        }

        return $result->body;
    }

    /**
     * Получить url, на который будет отправлен запрос.
     *
     * @param string $verb выполняемое действие: get, create, replace, delete
     * @param string|null $entity тип объекта: trip, air, car, rail, transport, etc
     */
    private function getBaseUrl(string $verb, ?string $entity): string
    {
        if (substr($verb, 0, 5) === 'oauth') {
            $baseUrl = $this->apiUrl . '/' . $verb;
        } else {
            $baseUrl = $entity !== null ?
                implode('/', [$this->apiUrl, $this->apiVersion, $verb, $entity]) :
                implode('/', [$this->apiUrl, $this->apiVersion, $verb]);
        }

        return $baseUrl;
    }
}
