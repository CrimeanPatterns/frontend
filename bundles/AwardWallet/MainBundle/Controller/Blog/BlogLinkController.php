<?php

namespace AwardWallet\MainBundle\Controller\Blog;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BlogLinkController extends AbstractController
{
    /**
     * @Route("/{link}", host="{host}", name="aw_blog_link", requirements={"link" = ".*", "host" = "awrd\.co|award\.travel|awrd\.docker"})
     */
    public function linkAction($link, Request $request, LoggerInterface $logger)
    {
        $browser = new Client(['allow_redirects' => false]);
        $headers = $request->headers->all();
        unset($headers['content-length']);
        unset($headers['content-type']);
        unset($headers['x-php-ob-level']);
        unset($headers['port']);
        unset($headers['host']);
        $proxyRequest = new GuzzleRequest($request->getMethod(), 'https://awardwallet.com/blog/link/' . $link, $headers);

        try {
            /** @var GuzzleResponse $proxyResponse */
            $proxyResponse = $browser->send($proxyRequest);
        } catch (GuzzleException $e) {
            $logger->warning("missing blog link", ["link" => $link, "errorMessage" => $e->getMessage(), 'headersJson' => json_encode($headers)]);

            return new RedirectResponse('https://awardwallet.com/');
        }

        return new Response((string) $proxyResponse->getBody(), $proxyResponse->getStatusCode(), $proxyResponse->getHeaders());
    }
}
