<?php

namespace AwardWallet\Tests\Unit\MainBundle\FrameworkExtension\Twig;

use AwardWallet\MainBundle\FrameworkExtension\Twig\AwTwigExtension;
use AwardWallet\MainBundle\Service\SecureLink;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class TwigExtensionTest extends BaseContainerTest
{
    /**
     * @see \Symfony\Component\Routing\Generator\UrlGenerator::QUERY_FRAGMENT_DECODED
     */
    private const QUERY_FRAGMENT_DECODED = [
        // RFC 3986 explicitly allows those in the query/fragment to reference other URIs unencoded
        '%2F' => '/',
        '%3F' => '?',
        // reserved chars that have no special meaning for HTTP URIs in a query or fragment
        // this excludes esp. "&", "=" and also "+" because PHP would treat it as a space (form-encoded)
        '%40' => '@',
        '%3A' => ':',
        '%21' => '!',
        '%3B' => ';',
        '%2C' => ',',
        '%2A' => '*',
    ];
    private $scheme = 'http';
    private $host = 'test.com';
    private $imgKey = 'abcdef';
    private $redirectKey = 'zxcvb';

    /**
     * @var AwTwigExtension
     */
    private $twigExt;

    public function _before()
    {
        parent::_before();
        $this->twigExt = $this->container->get('aw.extension.twig.aw_extension');
        $proxy = $this->container->get(SecureLink::class);
        $proxy->setImgKey($this->imgKey);
        $proxy->setRedirectKey($this->redirectKey);
    }

    public function _after()
    {
        $this->twigExt = null;
        parent::_after();
    }

    /**
     * @dataProvider getFilterMobileCSPData
     */
    public function testFilterMobileCSP($text, $result)
    {
        $output = $this->twigExt->filterMobileCSP($text, "{$this->scheme}://{$this->host}");
        $this->assertEquals($result, $output);
    }

    /**
     * @dataProvider getFilterAutoLinkData
     */
    public function testAutoLink($text, $outPolicy, $result, array $attrs = [])
    {
        $output = $this->twigExt->auto_link($text, "{$this->scheme}://{$this->host}", $outPolicy, null, $attrs);
        $this->assertEquals($result, $output);
    }

    /**
     * @dataProvider getFilterAutoMailtoData
     */
    public function testAutoMailto($text, $result, array $attrs = [])
    {
        $output = $this->twigExt->auto_mailto($text, $attrs);
        $this->assertEquals($result, $output);
    }

    /**
     * @dataProvider getProxyLinksData
     */
    public function testProxyLinks($text, $url, $varName, $result)
    {
        $output = $this->twigExt->proxyLinks($text, $url, $varName);
        $this->assertEquals($result, $output);
    }

    public function getFilterMobileCSPData()
    {
        return [
            [
                'Hello <a   href="/test"> Link</a>',
                'Hello <a   href="' . $this->getSchemeAndHost() . '/test"> Link</a>',
            ],
            [
                'Hello <a   href="//test">Link</a>',
                'Hello <a   href="http://test">Link</a>',
            ],
            [
                '<div> Hello <a target="_blank"  href="/test" class="abc"> Link</a></div> <a href="index.php?a=5&b=6"> <span>Link2</span></a> ',
                '<div> Hello <a target="_blank"  href="' . $this->getSchemeAndHost() . '/test" class="abc"> Link</a></div> <a href="' . $this->getSchemeAndHost() . '/index.php?a=5&b=6"> <span>Link2</span></a> ',
            ],
            [
                'Hello <a   href="#testId"> Link</a> <a href="mailto:abc@mail.com"></a> ',
                'Hello <a   href="#testId"> Link</a> <a href="mailto:abc@mail.com"></a> ',
            ],
            [
                '<br><br><br><br><a target="_blank" href="https://site.com/page?q=' . urlencode('Раз Два Три Четыре Пять') . '">Link</a><br> <a href="/dir/page">abs</a>',
                '<br><br><br><br><a target="_blank" href="https://site.com/page?q=' . urlencode('Раз Два Три Четыре Пять') . '">Link</a><br> <a href="' . $this->getSchemeAndHost() . '/dir/page">abs</a>',
            ],
            [
                'Hello <a   href="site.com/page"> Link</a><br><a href="business.awardwallet.com/page"> Link</a>',
                'Hello <a   href="http://site.com/page"> Link</a><br><a href="http://business.awardwallet.com/page"> Link</a>',
            ],
            [
                '<img alt="" src=" /images/test.gif"> ssfadsfsadf',
                '<img alt="" src="' . $this->getSchemeAndHost() . '/images/test.gif"> ssfadsfsadf',
            ],
            [
                '<img alt="" src="' . $this->getSchemeAndHost() . '/images/test.gif"> ssfadsfsadf <img alt="" src="' . $this->getSchemeAndHost('business') . '/images/test.gif">',
                '<img alt="" src="' . $this->getSchemeAndHost() . '/images/test.gif"> ssfadsfsadf <img alt="" src="' . $this->getSchemeAndHost('business') . '/images/test.gif">',
            ],
            [
                '<img alt="" src="http://site.com/images/test.gif"> ssfadsfsadf <script src="sub.site.com/dir/file.js"></script>',
                '<img alt="" src="' . $this->getProxyImgLink('http://site.com/images/test.gif') . '">' .
                    ' ssfadsfsadf <script src="sub.site.com/dir/file.js"></script>',
            ],
            [
                '<img alt="" src="site.com/images/test.gif"> ssfadsfsadf ',
                '<img alt="" src="' . $this->getProxyImgLink('http://site.com/images/test.gif') . '"> ssfadsfsadf ',
            ],
            [
                '<img alt="" src="' . $this->host . '/images/test.gif"> ssfadsfsadf ',
                '<img alt="" src="http://' . $this->host . '/images/test.gif"> ssfadsfsadf ',
            ],
            [
                '<img alt="" src="#"> ssfadsfsadf ',
                '<img alt="" src="#"> ssfadsfsadf ',
            ],
            [
                '<img alt="" src="img.png?a=5&"> ssfadsfsadf <img alt="" src="/img.png?a=5&"> ssfadsfsadf <img alt="" src="//site.com/img?a=5&">',
                '<img alt="" src="' . $this->scheme . '://' . $this->host . '/img.png?a=5&"> ssfadsfsadf <img alt="" src="' . $this->scheme . '://' . $this->host . '/img.png?a=5&"> ssfadsfsadf <img alt="" src="' . $this->getProxyImgLink('http://site.com/img?a=5&') . '">',
            ],
        ];
    }

    public function getFilterAutoLinkData()
    {
        return [
            [
                'This is a link site.com',
                null,
                'This is a link <a target="_blank" href="' . $this->getProxyLink('http://site.com') . '">site.com</a>',
            ],
            [
                'This is a link site.com.',
                false,
                'This is a link <a target="_blank" href="http://site.com" class="link">site.com</a>.',
                ['class' => 'link'],
            ],
            [
                'http://awardwallet.dev/m/',
                null,
                '<a target="_blank" href="' . $this->getProxyLink('http://awardwallet.dev/m/') . '">http://awardwallet.dev/m/</a>',
            ],
            [
                'http://test.com/m/',
                null,
                '<a target="_blank" href="http://test.com/m/">http://test.com/m/</a>',
            ],
            [
                'test.com/m/',
                null,
                '<a target="_blank" href="http://test.com/m/">test.com/m/</a>',
            ],
            [
                'google.com/m/#/',
                null,
                '<a target="_blank" href="' . $this->getProxyLink('http://google.com/m/#/') . '">google.com/m/#/</a>',
            ],
            [
                'Test text test.com and google.com/page?a=5',
                null,
                'Test text <a target="_blank" href="http://test.com">test.com</a> and <a target="_blank" href="' . $this->getProxyLink('http://google.com/page?a=5') . '">google.com/page?a=5</a>',
            ],
            [
                'text http://dev-mobile.test.com/about',
                null,
                'text <a target="_blank" href="http://dev-mobile.test.com/about">http://dev-mobile.test.com/about</a>',
            ],
            [
                "hacking test.comhackersite.com/",
                null,
                'hacking <a target="_blank" href="' . $this->getProxyLink('http://test.comhackersite.com/') . "\">test.comhackersite.com/</a>",
            ],
            [
                "hacking hackersitetest.com/",
                null,
                'hacking <a target="_blank" href="' . $this->getProxyLink('http://hackersitetest.com/') . "\">hackersitetest.com/</a>",
            ],
        ];
    }

    public function getFilterAutoMailtoData()
    {
        return [
            [
                'This is a test@mail.com',
                'This is a <a href="mailto:test@mail.com" style="color:#4684c4">test@mail.com</a>',
            ],
            [
                'This is a test@mail.com.',
                'This is a <a href="mailto:test@mail.com" style="font-size:14px">test@mail.com</a>.',
                [
                    'style' => [
                        'font-size' => '14px',
                    ],
                ],
            ],
            [
                'This is a test@mail.com<span> </span>',
                'This is a <a href="mailto:test@mail.com" style="color:#4684c4">test@mail.com</a><span> </span>',
            ],
        ];
    }

    public function getProxyLinksData()
    {
        return [
            ["text http://site.com text", "https://proxy.io", "url", "text http://site.com text"],
            ["text <a href='http://site.com'>link</a>", "https://proxy.io", "redirect", "text <a href='https://proxy.io?redirect=" . urlencode("http://site.com") . "'>link</a>"],
            ["text <a href='http://site.com' > text </a> 12345", "https://proxy.io", "url", "text <a href='https://proxy.io?url=" . urlencode("http://site.com") . "' > text </a> 12345"],
            [
                "text <a href='http://site.com' > text </a> 12345 <a href='http://site2.com/page.php?var=1&var2' > http://site2.com/page.php?var=1&var2 </a>",
                "https://proxy.io?a=1&b=2", "url",
                "text <a href='https://proxy.io?a=1&b=2&url=" . urlencode("http://site.com") . "' > text </a> 12345 <a href='https://proxy.io?a=1&b=2&url=" . urlencode("http://site2.com/page.php?var=1&var2") . "' > http://site2.com/page.php?var=1&var2 </a>",
            ],
        ];
    }

    private function getSchemeAndHost($subdomain = null)
    {
        return $this->scheme . "://" . (isset($subdomain) ? "$subdomain." : "") . $this->host;
    }

    private function getProxyImgLink($url)
    {
        $ctx = hash_init("sha256", HASH_HMAC, $this->imgKey);
        hash_update($ctx, $url);
        $hash = hash_final($ctx);

        return $this->getSchemeAndHost() . "/imageProxy.php?url=" . urlencode($url) . "&hash=" . urlencode($hash);
    }

    private function getProxyLink($url)
    {
        $hash = \hash_hmac('sha256', $url, $this->redirectKey);

        return
            $this->getSchemeAndHost() . "/out?url=" .
            str_replace(
                \array_keys(self::QUERY_FRAGMENT_DECODED),
                \array_values(self::QUERY_FRAGMENT_DECODED),
                urlencode($url)
            ) .
            "&hash=" . urlencode($hash);
    }
}
