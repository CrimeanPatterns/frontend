<?php

namespace AwardWallet\MainBundle\Service\AppBot\Adapter;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Slack implements AdapterInterface
{
    public const CHANNEL_AW_ALL = '#aw_all';
    public const CHANNEL_AW_JENKINS = '#aw_jenkins';
    public const CHANNEL_AW_SOCIAL_MEDIA_EN = '#aw_social_media_en';
    public const CHANNEL_AW_STATS = '#aw_stats';
    public const CHANNEL_AW_SYSADMIN = '#aw_sysadmin';
    public const CHANNEL_AW_REQUEST = '#aw_request';
    public const CHANNEL_AW_AT101_MODS = '#aw_at101_mods';
    public const CHANNEL_AW_FAKE_IDS = '#fake_ids';
    public const CHANNEL_AW_BLOG = '#aw_blog';
    public const CHANNEL_LOG_DEV = '#log_dev';
    public const CHANNEL_AW_AWARD_ALERTS = '#aw_award_alerts';
    public const CHANNEL_LAUNCH_CRAWLERS = '#launch-crawlers'; // id = 'C034PHWLNCR'
    public const CHANNEL_AW_REWARD_AVAILABILITY = '#aw_reward_availability'; // id = 'C01P2ARKNLW
    public const CHANNEL_AW_RA_ALERTS = '#aw_ra_alerts';

    public const CHANNELS_ID = [
        self::CHANNEL_AW_ALL => 'C0S41PXJ4',
        self::CHANNEL_AW_JENKINS => 'C6JCBM5PF',
        self::CHANNEL_AW_SOCIAL_MEDIA_EN => 'C6AA4LQNQ',
        self::CHANNEL_AW_STATS => 'G6HRRHB8E',
        self::CHANNEL_AW_REQUEST => 'CM994JMBM',
        self::CHANNEL_AW_SYSADMIN => 'C6B4Z2TL6',
        self::CHANNEL_AW_AT101_MODS => 'G8JJA8DRA',
    ];

    private const API_FILES_UPLOAD = 'https://slack.com/api/files.upload';
    private const API_FILES_SHARED_PUBLIC_URL = 'https://slack.com/api/files.sharedPublicURL';

    private const ALLOW_MESSAGE_KEYS = ['text', 'attachments', 'blocks'];

    private \HttpDriverInterface $driver;
    private array $options;
    private LoggerInterface $logger;
    private bool $isDevEnv;

    public function __construct(
        \HttpDriverInterface $driver,
        LoggerInterface $logger,
        array $slackOptions,
        $env
    ) {
        $this->driver = $driver;
        $this->logger = $logger;
        $this->options = $slackOptions;
        $this->isDevEnv = in_array($env, ['dev', 'staging']);
    }

    /**
     * @param string|array $message message formatted as described in: https://api.slack.com/reference/surfaces/formatting
     * @throws \Exception
     */
    public function send(
        string $channelName,
        $message,
        ?string $botname = null,
        ?string $icon = null
    ): bool {
        if ($this->isDevEnv) {
            $channelName = self::CHANNEL_LOG_DEV;
        }

        $channelUrl = $this->getChannelHookUrl($channelName);
        $postData = [
            'username' => $botname,
        ];

        if (is_array($message)) {
            foreach ($message as $key => $value) {
                if (in_array($key, self::ALLOW_MESSAGE_KEYS)) {
                    $postData[$key] = $value;
                }
            }
        } else {
            $postData['text'] = $message;
        }

        if (null !== $icon) {
            $postData['icon'] = $icon;
        }

        $postData = json_encode($postData);
        $request = new \HttpDriverRequest($channelUrl, Request::METHOD_POST, $postData);
        $response = $this->driver->request($request);

        return $this->isResponse($response);
    }

    /**
     * @return array|false|null
     */
    public function uploadFile(string $filename, array $options = []): ?array
    {
        $upload = $this->apiUploadFile($filename);

        if (null === $upload || !$upload['success']) {
            return null;
        }

        $shared = $this->apiSharedFile($upload['file']->id);

        if (!$upload['success']) {
            return null;
        }

        return $shared;
    }

    private function apiUploadFile(string $filename, array $postData = []): ?array
    {
        if (!file_exists($filename)) {
            throw new \RuntimeException('File not found: ' . $filename);
        }

        $postData['token'] = $this->options['tokens']['oauth'];
        $postData['file'] = new \CURLFile($filename, \mime_content_type($filename));

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_URL, self::API_FILES_UPLOAD);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);

        $response = curl_exec($curl);
        $result = json_decode($response);

        if (empty($result)) {
            $this->logger->debug('Slack: Failed to upload file.');
            $this->logger->debug('Slack: ' . curl_error($curl));

            return null;
        }

        if (true !== $result->ok) {
            $this->logger->debug('Slack: Error loading file', json_decode($response, true));

            return null;
        }

        return [
            'success' => true,
            'file' => $result->file,
        ];
    }

    private function apiSharedFile(string $fileId): ?array
    {
        $postData = [
            'token' => $this->options['tokens']['oauth'],
            'file' => $fileId,
        ];

        $request = new \HttpDriverRequest(self::API_FILES_SHARED_PUBLIC_URL, Request::METHOD_POST, $postData);
        $response = $this->driver->request($request);
        $result = json_decode($response->body);

        if (true !== $result->ok) {
            $this->logger->debug('Error slack adapter file sharing', json_decode($response->body, true));

            return null;
        }

        return [
            'success' => true,
            'publicUrl' => $result->file->permalink_public,
            'file' => $result->file,
        ];
    }

    /**
     * @throws \Exception
     */
    private function getChannelHookUrl(string $channel): string
    {
        if (0 === strpos($channel, '#')) {
            $channel = substr($channel, 1);
        }

        if (!array_key_exists($channel, $this->options['webhook'])) {
            throw new \Exception('Missing webhook url for channel "' . $channel . '"');
        }

        return $this->options['webhook'][$channel];
    }

    private function isResponse(\HttpDriverResponse $response): bool
    {
        $isSucess = Response::HTTP_OK === $response->httpCode;

        if (false === $isSucess) {
            $this->logger->warning(
                'Slack bot http error',
                ['httpCode' => $response->httpCode, 'body' => $response->body]
            );

            throw new \RuntimeException(strip_tags($response->body));
        }

        return $isSucess;
    }
}
