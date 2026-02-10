<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners\AwSecureTokenListener\MobileTokenChecker;

use AwardWallet\MainBundle\FrameworkExtension\Listeners\AwSecureTokenListener\SecureTokenHandle;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\AwSecureTokenListener\TokenCheckerInterface;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class MobileTokenChecker implements TokenCheckerInterface
{
    /**
     * @var ApiVersioningService
     */
    private $apiVersioning;
    /**
     * @var string
     */
    private $saltKey;
    /**
     * @var MobileTokenFeatureProvider
     */
    private $keyProvider;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        ApiVersioningService $apiVersioning,
        TranslatorInterface $translator,
        string $saltKey
    ) {
        $this->saltKey = $saltKey;
        $this->apiVersioning = $apiVersioning;
        $this->keyProvider = new MobileTokenFeatureProvider();
        $this->translator = $translator;
    }

    public function check(SecureTokenHandle $secureTokenHandle)
    {
        $request = $secureTokenHandle->getRequest();
        $configuration = $secureTokenHandle->getConfiguration();

        $clientToken = $request->headers->get(MobileHeaders::MOBILE_SECURE_TOKEN);
        $serverValue = $request->headers->get(MobileHeaders::MOBILE_SECURE_VALUE);
        $keyFound = false;

        foreach (array_reverse($this->keyProvider->getKeys()) as [$feature, $clientKey]) {
            if ($this->apiVersioning->supports($feature)) {
                $keyFound = true;

                break;
            }
        }

        if (!$keyFound) {
            return $this->prepareOutdatedClientResponse();
        }

        if (true === $clientKey) {
            // soft mode, no key check
            return null;
        }

        if (isset($clientToken, $serverValue)) {
            $checkResult = $this->checkSalt($request, $serverValue, $clientKey, $configuration->getLifetime());
            $checkResult = $this->checkHash($clientToken, $serverValue, $clientKey) && $checkResult;

            if (!$checkResult) {
                return $this->prepareInvalidTokenResponse($request, $clientKey);
            }
        } else {
            return $this->prepareInvalidTokenResponse($request, $clientKey);
        }

        return null;
    }

    protected function prepareOutdatedClientResponse(): Response
    {
        $msg = $this->translator->trans(/** @Ignore */ 'outdated.text', [], 'mobile');
        $response = new JsonResponse(['error' => $msg]);
        $response->headers->set(MobileHeaders::MOBILE_VERSION, $msg);

        return $response;
    }

    protected function prepareInvalidTokenResponse(Request $request, string $clientKey): Response
    {
        return new JsonResponse(
            ['error' => 'Invalid token'],
            403,
            [
                MobileHeaders::MOBILE_SECURE_TOKEN => $this->generateSalt($request, $clientKey),
            ]
        );
    }

    protected function checkHash(string $clientToken, string $serverValue, string $clientKey): bool
    {
        return hash_equals($clientToken, hash('sha256', $serverValue . $clientKey));
    }

    protected function generateSalt(Request $request, string $clientKey): string
    {
        $random = bin2hex(random_bytes(32));
        $time = (string) time();

        return
            $random . '=' .
            $time . '=' .
            hash_hmac(
                'sha256',
                $random . $time,
                $this->generateHMACKey($request, $clientKey)
            );
    }

    protected function generateHMACKey(Request $request, string $clientKey): string
    {
        return
            $clientKey . '|' .
            $this->saltKey . '|' .
            $request->getClientIp() . '|' .
            $request->headers->get('user-agent');
    }

    protected function checkSalt(Request $request, string $serverValue, string $clientKey, int $lifetime): bool
    {
        $safeServerValue = substr($serverValue, 0, (32 * 2) + 1 + 15 + 1 + 64);
        $safeServerValueParts = explode('=', $safeServerValue);

        if (count($safeServerValueParts) !== 3) {
            return false;
        }

        [$random, $time, $hashHMAC] = $safeServerValueParts;

        if (!hash_equals(
            $hashHMAC,
            hash_hmac(
                'sha256',
                $random . $time,
                $this->generateHMACKey($request, $clientKey)
            )
        )) {
            return false;
        }

        if (time() - $time > $lifetime) {
            return false;
        }

        return true;
    }
}
