<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Tracker\ClickTracker;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Tracker\UrlSigner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmailClickController extends AbstractController
{
    private ClickTracker $clickTracker;

    public function __construct(ClickTracker $clickTracker)
    {
        $this->clickTracker = $clickTracker;
    }

    /**
     * @Route(
     *     "/t/{trackingId}/a{path}",
     *     name="aw_email_track_aw_link",
     *     requirements={
     *          "trackingId" = "[a-z\d]{32}",
     *          "path"=".*"
     *     }
     * )
     */
    public function awardWalletLinkAction(Request $request, string $trackingId, string $path)
    {
        if ($path === '') {
            $path = '/';
        }

        $url = $this->buildUrl($path, $request->query->all());

        $this->trackUrl($url, $trackingId, $request);

        return new RedirectResponse($url);
    }

    /**
     * @Route(
     *     "/t/{trackingId}/e/{sha}/{hostAndPath}",
     *     name="aw_email_track_external_link",
     *     requirements={
     *          "trackingId" = "[a-z\d]{32}",
     *          "sha" = "[a-z\d]{40}",
     *          "hostAndPath"=".*"
     *     }
     * )
     */
    public function externalLinkAction(
        Request $request,
        string $trackingId,
        string $hostAndPath,
        string $sha,
        UrlSigner $urlSigner
    ) {
        // TODO: remove after 2025-03-01
        if (false !== strpos($hostAndPath, '9 international lay-flat business class awards costing 50')) {
            return new RedirectResponse('https://awardwallet.com/blog/business-class-flights-under-50k-miles/');
        }

        $url = $this->buildUrl($hostAndPath, $request->query->all());

        // there is an error(?) in SendTracker, which calculates hash over 'some.host/path?q1=a&q2=b&fragment=xxx'
        // #fragment converted to query param. use same logic.
        if ($urlSigner->getSign($this->buildUrlForSignature($hostAndPath, $request->query->all())) !== $sha) {
            return new Response('Bad request', 400);
        }

        $this->trackUrl($url, $trackingId, $request);

        return new RedirectResponse('https://' . $url);
    }

    private function buildUrl(string $path, array $query): string
    {
        $fragment = null;

        if (isset($query['fragment'])) {
            $fragment = $query['fragment'];
            unset($query['fragment']);
        }

        $url = $path;

        if (count($query) > 0) {
            $url .= "?" . http_build_query($query);
        }

        if ($fragment !== null) {
            $url .= '#' . $fragment;
        }

        return $url;
    }

    private function buildUrlForSignature(string $path, array $query): string
    {
        $url = $path;

        if (count($query) > 0) {
            $url .= "?" . http_build_query($query);
        }

        return $url;
    }

    private function trackUrl(string $url, string $trackingId, Request $request): void
    {
        $binaryTrackingId = @hex2bin($trackingId);

        if ($binaryTrackingId !== false) {
            $this->clickTracker->trackClick($binaryTrackingId, $url, $request);
        }
    }
}
