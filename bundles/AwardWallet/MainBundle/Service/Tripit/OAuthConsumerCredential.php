<?php

namespace AwardWallet\MainBundle\Service\Tripit;

/**
 * Класс, предназначенный для осуществления запросов с аутентификацией OAuth.
 */
class OAuthConsumerCredential
{
    private const SIGNATURE_METHOD = 'HMAC-SHA1';
    private const VERSION = '1.0';

    private string $consumerKey;
    private string $consumerSecret;
    /**
     * @var string публичная часть авторизационного токена, которая нужна для доступа к защищённым данным
     * пользователя TripIt. Должен храниться бессрочно на стороне AwardWallet.
     */
    private string $oauthToken = '';
    /**
     * @var string закрытая часть авторизационного токена, которая нужна для доступа к защищённым данным
     * пользователя TripIt. Должен храниться бессрочно на стороне AwardWallet.
     */
    private string $oauthTokenSecret = '';
    /**
     * @var array параметры, передающиеся в запросе
     */
    private array $params = [];

    /**
     * Constructor.
     *
     * @param string $tripitConsumerKey API Key
     * @param string $tripitConsumerSecret API Secret
     */
    public function __construct(string $tripitConsumerKey, string $tripitConsumerSecret)
    {
        $this->consumerKey = $tripitConsumerKey;
        $this->consumerSecret = $tripitConsumerSecret;
    }

    public function setOauthToken(string $oauthToken)
    {
        $this->oauthToken = $oauthToken;
    }

    public function setOauthTokenSecret(string $oauthTokenSecret)
    {
        $this->oauthTokenSecret = $oauthTokenSecret;
    }

    public function setParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * Установка http-заголовка "Authorization".
     *
     * @param array $headers массив HTTP-заголовков
     * @param string $method HTTP-метод запроса
     * @param string $realm домен API
     * @param string $baseUrl полный url, на который отправляется запрос
     */
    public function authorize(array &$headers, string $method, string $realm, string $baseUrl)
    {
        $header = $this->generateAuthHeader($method, $realm, $baseUrl);
        $headers['Authorization'] = $header;
    }

    public static function urlencodeRFC3986(string $string)
    {
        return str_replace('+', ' ', str_replace('%7E', '~', rawurlencode($string)));
    }

    public static function generateNonce(): string
    {
        return md5(microtime() . mt_rand());
    }

    public static function generateTimestamp(): int
    {
        return time();
    }

    /**
     * Генерация строки "OAuth realm" для заголовка.
     *
     * @param string $method HTTP-метод запроса
     * @param string $realm домен API
     * @param string $baseUrl полный url, на который отправляется запрос
     */
    private function generateAuthHeader(string $method, string $realm, string $baseUrl): string
    {
        $authHeader = 'OAuth realm="' . $realm . '",';
        $params = [];

        foreach ($this->generateOauthParams($method, $baseUrl) as $key => $value) {
            if (substr($key, 0, 5) === 'oauth' || substr($key, 0, 6) === 'xoauth') {
                $params[] = self::urlencodeRFC3986($key) . '="' . self::urlencodeRFC3986($value) . '"';
            }
        }
        $authHeader .= implode(',', $params);

        return $authHeader;
    }

    /**
     * Генерация параметров, отправляемых в заголовке "Authorization".
     *
     * @param string $method HTTP-метод запроса
     * @param string $baseUrl полный url, на который отправляется запрос
     */
    private function generateOauthParams(string $method, string $baseUrl): array
    {
        $params = [
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_nonce' => self::generateNonce(),
            'oauth_timestamp' => self::generateTimestamp(),
            'oauth_signature_method' => self::SIGNATURE_METHOD,
            'oauth_version' => self::VERSION,
        ];

        if ($this->oauthToken !== null) {
            $params['oauth_token'] = $this->oauthToken;
        }

        if (!empty($this->params)) {
            $params = array_merge($params, $this->params);
        }

        $params['oauth_signature'] = $this->generateSignature($method, $baseUrl, $params);

        return $params;
    }

    /**
     * Генерация зашифрованной строки для параметра "signature".
     *
     * @param string $method HTTP-метод запроса
     * @param string $baseUrl полный url, на который отправляется запрос
     * @param array $params параметры, отправляемые в заголовке
     */
    private function generateSignature(string $method, string $baseUrl, array $params): string
    {
        $normalizedParams = self::urlencodeRFC3986($this->getSignableParameters($params));
        $normalizedUrl = self::urlencodeRFC3986($baseUrl);
        $baseString = $method . '&' . $normalizedUrl;

        if ($normalizedParams) {
            $baseString .= '&' . $normalizedParams;
        }

        $keyParts = [$this->consumerSecret, $this->oauthTokenSecret];
        $keyParts = array_map([get_called_class(), 'urlencodeRFC3986'], $keyParts);
        $key = implode('&', $keyParts);

        return base64_encode(hash_hmac('sha1', $baseString, $key, true));
    }

    private function getSignableParameters(array $params): string
    {
        $keys = array_map([get_called_class(), 'urlencodeRFC3986'], array_keys($params));
        $values = array_map([get_called_class(), 'urlencodeRFC3986'], array_values($params));
        $params = array_combine($keys, $values);
        uksort($params, 'strnatcmp');
        $pairs = [];

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                natsort($value);

                foreach ($value as $item) {
                    $pairs[] = $key . '=' . $item;
                }
            } else {
                $pairs[] = $key . '=' . $value;
            }
        }

        return implode('&', $pairs);
    }
}
