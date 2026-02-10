<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Service\Blog\BlogApi;
use AwardWallet\MainBundle\Service\Blog\Constants;
use AwardWallet\MainBundle\Service\PopularityHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SeoController extends AbstractController
{
    private const XSL_SITEMAP = PHP_EOL . '<?xml-stylesheet type="text/xsl" href="https://awardwallet.com/blog/wp-content/themes/awardwallet/public/xml/sitemap.xsl"?>';
    private const XML_HEAD = [
        '<?xml version="1.0" encoding="UTF-8"?>' . self::XSL_SITEMAP,
        '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd http://www.google.com/schemas/sitemap-image/1.1 http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL,
    ];
    private const HEADERS_RESPONSE_XML = [
        'content-type' => 'application/xml; charset=utf-8',
    ];
    private const BLOG_POSTS_COUNT_LINKS_XML = 200;

    private $imageSchema = 'http://www.google.com/schemas/sitemap-image/1.1';
    private $videoSchema = 'http://www.google.com/schemas/sitemap-video/1.1';
    private $newsSchema = 'http://www.google.com/schemas/sitemap-news/0.9';

    private \Memcached $memcached;
    private string $requiresChannel;
    private string $host;
    private array $locales;
    private PopularityHandler $popularityHandler;
    private BlogApi $blogApi;
    private string $cdnHost;

    public function __construct(
        \Memcached $memcached,
        string $requiresChannel,
        string $host,
        array $locales,
        PopularityHandler $popularityHandler,
        BlogApi $blogApi,
        string $cdnHost
    ) {
        $this->memcached = $memcached;
        $this->requiresChannel = $requiresChannel;
        $this->host = $host;
        $this->locales = $locales;
        $this->popularityHandler = $popularityHandler;
        $this->blogApi = $blogApi;
        $this->cdnHost = $cdnHost;
    }

    /**
     * @Route("/sitemap.xml", name="aw_sitemap", defaults={"_format"="xml"})
     */
    public function sitemapAction(): Response
    {
        $cacheKey = 'sitemap_xml_v4';
        $xml = $this->memcached->get($cacheKey);

        if (!$xml) {
            $routes = [
                'aw_sitemap_site',
                'aw_sitemap_blog_pages',
                'aw_sitemap_blog_authors',
                'aw_sitemap_blog_tags',
                'aw_sitemap_blog_categories',
                // 'aw_sitemap_blog_news',
            ];

            $xml = '<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/xsl" href="https://awardwallet.com/blog/wp-content/themes/awardwallet/public/xml/sitemap-index.xsl" ?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
';

            $posts = $this->blogApi->getRestApiData('posts/get-all-published/?withRedirected=1&removeRedirected=1');
            $pages = round(count($posts) / self::BLOG_POSTS_COUNT_LINKS_XML);

            for ($i = 0; $i <= $pages; $i++) {
                $xml .= '    <sitemap>
		<loc>' . $this->generateUrl(
                    'aw_sitemap_blog_posts_pages',
                    ['page' => $i],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ) . '</loc>
		<lastmod>' . date('c') . '</lastmod>
	</sitemap>
';
            }

            foreach ($routes as $route) {
                $xml .= '    <sitemap>
		<loc>' . $this->generateUrl($route, [], UrlGeneratorInterface::ABSOLUTE_URL) . '</loc>
		<lastmod>' . date('c') . '</lastmod>
	</sitemap>
';
            }
            $xml .= '</sitemapindex>';

            $this->memcached->set($cacheKey, $xml, \AwardWallet\Common\DateTimeUtils::SECONDS_PER_DAY);
        }

        return new Response(trim($xml), Response::HTTP_OK, self::HEADERS_RESPONSE_XML);
    }

    /**
     * @Route("/sitemap-site.xml", name="aw_sitemap_site")
     */
    public function sitemapSiteAction(): Response
    {
        $cacheKey = 'sitemap_site_v2';
        $sitemap = $this->memcached->get($cacheKey);

        if (!$sitemap) {
            $sitemap = $this->getSiteXmlMap()->asXML();
            $this->memcached->set($cacheKey, trim($sitemap), \AwardWallet\Common\DateTimeUtils::SECONDS_PER_DAY);
        }

        return new Response($sitemap, Response::HTTP_OK, self::HEADERS_RESPONSE_XML);
    }

    /**
     * @Route("/sitemap-blog-news.xml", name="aw_sitemap_blog_news")
     */
    public function sitemapNewsAction(): Response
    {
        $cacheKey = 'sitemap_blog_news_v2';
        $sitemap = $this->memcached->get($cacheKey);

        if (!$sitemap) {
            $sitemap = $this->getSiteXmlMapNews()->asXML();
            // see "aw_blog_new_post" to delete cache by key
            $this->memcached->set(
                $cacheKey,
                trim($sitemap),
                \AwardWallet\Common\DateTimeUtils::SECONDS_PER_DAY);
        }

        return new Response($sitemap, Response::HTTP_OK, self::HEADERS_RESPONSE_XML);
    }

    /**
     * @Route("/sitemap-blog-posts.xml", name="aw_sitemap_blog_posts")
     * @Route("/sitemap-blog-posts-{page}.xml", name="aw_sitemap_blog_posts_pages", defaults={"page"="1"})
     */
    public function sitemapBlogPosts($page = null): Response
    {
        $posts = $this->blogApi->getRestApiData('posts/get-all-published/?withRedirected=1&removeRedirected=1');

        if (null === $posts || array_key_exists(Constants::BUSY, $posts)) {
            throw $this->createNotFoundException();
        }

        if (null !== $page) {
            $chunks = array_chunk($posts, self::BLOG_POSTS_COUNT_LINKS_XML);
            $posts = $chunks[$page] ?? [];
        }

        $xml = implode(PHP_EOL, self::XML_HEAD);

        foreach ($posts as $post) {
            $absLink = property_exists($post, 'full_url')
                ? Constants::URL_CUSTOM . ltrim($post->full_url, '/')
                : Constants::URL . ltrim($post->post_name, '/');

            $xml .= '    <url>
		<loc>' . $absLink . '</loc>
		<lastmod>' . date('c', strtotime($post->post_modified)) . '</lastmod>
	</url>
';
        }
        $xml .= '</urlset>';

        return new Response($xml, Response::HTTP_OK, self::HEADERS_RESPONSE_XML);
    }

    /**
     * @Route("/sitemap-blog-authors.xml", name="aw_sitemap_blog_authors")
     */
    public function sitemapBlogAuthors(): Response
    {
        $authors = $this->blogApi->getRestApiData('user/get-all-authors');

        if (null === $authors || array_key_exists(Constants::BUSY, $authors)) {
            throw $this->createNotFoundException();
        }

        $xml = implode(PHP_EOL, self::XML_HEAD);

        foreach ($authors as $author) {
            if (false !== strpos($author->user_login, ' ')) {
                continue;
            }

            $slug = strtolower($author->user_login);
            $slug = str_replace('.', '-', $slug);

            $xml .= '    <url>
		<loc>' . Constants::URL . 'author/' . $slug . '/</loc>
		<lastmod>' . date('c') . '</lastmod>
	</url>
';
        }
        $xml .= '</urlset>';

        return new Response($xml, Response::HTTP_OK, self::HEADERS_RESPONSE_XML);
    }

    /**
     * @Route("/sitemap-blog-tags.xml", name="aw_sitemap_blog_tags")
     */
    public function sitemapBlogTags(): Response
    {
        $tags = $this->blogApi->getRestApiData('tag/get-all-tags');

        if (null === $tags || array_key_exists(Constants::BUSY, $tags)) {
            throw $this->createNotFoundException();
        }

        $xml = implode(PHP_EOL, self::XML_HEAD);
        $exclude = [
            'aeromexico-club-premier',
            'alitalia-millemiglia',
            'alaskacompanionfare',
            'amexdigitalcredit',
            'hyattcat14award',
            'ihgfreenightcert',
            'hiltonresortcredit',
            'hiltonfreenightreward',
            'ihgfoodandbeverage',
            'csrtravelcredit',
            'hyattclubaccess',
            'amexplatinumhotelcredit',
            'amexplatinumsakscredit',
            'amexdellcredit',
            'hyattsuiteaward',
            'amexplatinumclearcredit',
            'amexresycredit',
        ];

        foreach ($tags as $tag) {
            if (0 === (int) $tag->count
                || !empty($tag->isRedirected)
                || false !== in_array($tag->slug, $exclude, true)
            ) {
                continue;
            }

            $xml .= '    <url>
		<loc>' . Constants::URL . 'tag/' . $tag->slug . '/</loc>
		<lastmod>' . date('c') . '</lastmod>
	</url>
';
        }
        $xml .= '</urlset>';

        return new Response($xml, Response::HTTP_OK, self::HEADERS_RESPONSE_XML);
    }

    /**
     * @Route("/sitemap-blog-categories.xml", name="aw_sitemap_blog_categories")
     */
    public function sitemapBlogCategory(): Response
    {
        $categories = $this->blogApi->getRestApiData('api/get-all-categories');

        if (null === $categories || array_key_exists(Constants::BUSY, $categories)) {
            throw $this->createNotFoundException();
        }

        $xml = implode(PHP_EOL, self::XML_HEAD);

        foreach ($categories as $category) {
            if (!empty($category->isRedirected)) {
                continue;
            }

            $xml .= '    <url>
		<loc>' . Constants::URL . 'category/' . $category->slug . '/</loc>
		<lastmod>' . date('c') . '</lastmod>
	</url>
';
        }
        $xml .= '</urlset>';

        return new Response($xml, Response::HTTP_OK, self::HEADERS_RESPONSE_XML);
    }

    /**
     * @Route("/sitemap-blog-pages.xml", name="aw_sitemap_blog_pages")
     */
    public function sitemapBlogPages(): Response
    {
        $pages = $this->blogApi->getRestApiData('api/get-all-pages');

        if (null === $pages || array_key_exists(Constants::BUSY, $pages)) {
            throw $this->createNotFoundException();
        }

        $xml = implode(PHP_EOL, self::XML_HEAD);

        foreach ($pages as $page) {
            if (!empty($page->isRedirected)) {
                continue;
            }

            $absLink = property_exists($page, 'full_url')
                ? Constants::URL_CUSTOM . ltrim($page->full_url, '/')
                : Constants::URL . ltrim($page->post_name, '/');

            $xml .= '    <url>
		<loc>' . $absLink . '</loc>
		<lastmod>' . date('c', strtotime($page->post_date)) . '</lastmod>
	</url>
';
        }
        $xml .= '</urlset>';

        return new Response($xml, Response::HTTP_OK, self::HEADERS_RESPONSE_XML);
    }

    private function getSiteXmlMapNews(): \SimpleXMLElement
    {
        $blogposts = $this->blogApi->getRestApiData('api/get-homepage-post?limit=16', ['method' => Request::METHOD_POST]);
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . self::XSL_SITEMAP . PHP_EOL
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="' . $this->newsSchema . '"></urlset>'
        );

        if (empty($blogposts)) {
            return $xml;
        }

        $dateExpired = strtotime('-2 day midnight');

        foreach ($blogposts as $item) {
            $postDate = strtotime($item->postDate);

            if ($postDate > $dateExpired) {
                $postUrl = trim(str_replace('awid=homepage', '', $item->postURL), '/?& ');

                if (false === strpos($postUrl, '?') && false === strpos($postUrl, '&')) {
                    $postUrl .= '/';
                }

                $url = $xml->addChild('url');
                $url->addChild('loc', $postUrl);
                $news = $url->addChild('news:news', null, $this->newsSchema);

                $publication = $news->addChild('news:publication', null, $this->newsSchema);
                $publication->addChild('news:name', 'AwardWallet Blog');
                $publication->addChild('news:language', 'en');

                $news->addChild('news:publication_date', date('c', $postDate));
                $news->addChild('news:title', htmlspecialchars($item->title, ENT_QUOTES));
            }
        }

        return $xml;
    }

    private function getSiteXmlMap(): \SimpleXMLElement
    {
        $addr = $this->requiresChannel . '://' . $this->host . '/';
        $locales = $this->locales;

        // index page; /{locale}/
        $data = [
            [
                'loc' => $addr . 'blog/',
                'priority' => '1.0',
                'lastmod' => date('c'),
                'changefreq' => 'daily',
            ],
        ];

        foreach ($locales as $locale) {
            $data = array_merge($data, [
                [
                    'loc' => $addr . $locale . '/',
                    'priority' => '1.0',
                    'lastmod' => date('c'),
                    'changefreq' => 'weekly',
                ],
            ]);
        }

        // providers
        /*
        $providers = $this->getProviders();
        $data = array_merge($data, [['loc' => $this->generateUrl('aw_supported_seo', [], UrlGeneratorInterface::ABSOLUTE_URL), 'changefreq' => 'weekly']]);
        foreach ($providers as $provider) {
            $data = array_merge($data, [
                ['loc' => $addr . 'r/' . $provider['href'] . '/', 'changefreq' => 'monthly'],
            ]);
        }
        */

        // pages
        $pages = ['about', 'privacy', 'terms'];
        $onlyEnglish = ['privacy', 'terms'];

        foreach ($locales as $locale) {
            foreach ($pages as $page) {
                if ('en' !== $locale && in_array($page, $onlyEnglish)) {
                    continue;
                }
                $data = array_merge($data, [
                    [
                        'loc' => $this->generateUrl('aw_page_index_locale',
                            ['_locale' => $locale, 'page' => $page],
                            UrlGeneratorInterface::ABSOLUTE_URL),
                        'changefreq' => 'monthly',
                    ],
                ]);
            }
        }

        // api
        $data = array_merge($data, [
            [
                'loc' => $this->generateUrl('aw_api_doc', ['item' => 'main'], UrlGeneratorInterface::ABSOLUTE_URL),
                'changefreq' => 'monthly',
            ],
            [
                'loc' => $this->generateUrl('aw_api_doc',
                    ['item' => 'account'],
                    UrlGeneratorInterface::ABSOLUTE_URL),
                'changefreq' => 'monthly',
            ],
            [
                'loc' => $this->generateUrl('aw_api_doc',
                    ['item' => 'loyalty'],
                    UrlGeneratorInterface::ABSOLUTE_URL),
                'changefreq' => 'monthly',
            ],
            [
                'loc' => $this->generateUrl('aw_api_doc', ['item' => 'email'], UrlGeneratorInterface::ABSOLUTE_URL),
                'changefreq' => 'monthly',
            ],
            [
                'loc' => $this->generateUrl('aw_api_email_parsing', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'changefreq' => 'monthly',
            ],
            [
                'loc' => $this->generateUrl('aw_api_flight_delay_compensation',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL),
                'changefreq' => 'monthly',
            ],
            [
                'loc' => $this->generateUrl('aw_api_itinerary_management', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'changefreq' => 'monthly',
            ],
            [
                'loc' => $this->generateUrl('aw_api_green_startups', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'changefreq' => 'monthly',
            ],
            [
                'loc' => $this->generateUrl('aw_api_expense_management', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'changefreq' => 'monthly',
            ],
        ]);

        // faq; contactus; other
        foreach ($locales as $locale) {
            $data = array_merge($data, [
                [
                    'loc' => $this->generateUrl('aw_faq_index_locale',
                        ['_locale' => $locale],
                        UrlGeneratorInterface::ABSOLUTE_URL),
                    'changefreq' => 'monthly',
                ],
            ]);
            $data = array_merge($data, [
                [
                    'loc' => $this->generateUrl('aw_contactus_index_locale',
                        ['_locale' => $locale],
                        UrlGeneratorInterface::ABSOLUTE_URL),
                    'changefreq' => 'monthly',
                ],
            ]);
            // $data = array_merge($data, [['loc' => $addr . $locale . '/pr', 'changefreq' => 'yearly']]);
        }
        // $data = array_merge($data, [['loc' => $addr . 'en/pr', 'changefreq' => 'yearly']]);
        $data = array_merge($data, $this->getVideoData());
        $data = array_merge($data, $this->getImageData($addr));

        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>' . self::XSL_SITEMAP . PHP_EOL . '<urlset
            xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
            xmlns:image="' . $this->imageSchema . '"
            xmlns:video="' . $this->videoSchema . '"></urlset>'
        );

        foreach ($data as $url) {
            $urlChild = $xml->addChild('url');
            $this->addChilds($urlChild, $url);
        }

        return $xml;
    }

    private function addChilds($child, $data)
    {
        $addValueWithAttr = function ($child, $key, $value, ?array $attr) {
            $depChild = $child->addChild($key, $value);

            if (!empty($attr)) {
                foreach ($attr as $attrKey => $attrValue) {
                    $depChild->addAttribute($attrKey, $attrValue);
                }
            }
        };

        foreach ($data as $key => $item) {
            if (is_array($item)) {
                if (isset($item['value'])) {
                    $addValueWithAttr($child, $key, $item['value'], $item['attr'] ?? null);

                    continue;
                } elseif (isset($item['_schema']) && is_array($item['_items'])) {
                    foreach ($item['_items'] as $parentKey => $parentItems) {
                        $depChild = $child->addChild($key, null, $item['_schema']);

                        foreach ($parentItems as $itemKey => $itemValue) {
                            if (isset($itemValue['value'])) {
                                $addValueWithAttr($depChild, $itemKey, $itemValue['value'], $itemValue['attr'] ?? null);

                                continue;
                            } elseif (isset($itemValue['duplicates'])) {
                                foreach ($itemValue['duplicates'] as $duplicate) {
                                    $addValueWithAttr($depChild, $itemKey, $duplicate, null);
                                }

                                continue;
                            }

                            $depChild->addChild($itemKey, $itemValue, $item['_schema']);
                        }
                    }

                    continue;
                }

                $depChild = $child->addChild($key);

                return $this->addChilds($depChild, $item);
            }

            $child->addChild($key, $item);
        }
    }

    private function getProviders(): array
    {
        return $this->popularityHandler->getPopularPrograms(null,
            " AND p.ProviderID NOT IN ({$this->popularityHandler->unsupportedProviders})");
    }

    private function getVideoData(): array
    {
        return [
            [
                'loc' => $this->generateUrl('aw_home', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'changefreq' => 'monthly',
                'video:video' => [
                    '_schema' => $this->videoSchema,
                    '_items' => [
                        [
                            'video:thumbnail_loc' => 'https:' . $this->cdnHost . '/assets/awardwalletnewdesign/img/video-stub.jpg',
                            'video:title' => 'AwardWallet Intro',
                            'video:description' => '<![CDATA[Best Way to Track Points &amp; Miles | AwardWallet Saves You Time, Effort &amp; Money. Great AwardWallet review by Ernest from TripAstute (tripastute.com). AwardWallet is not affiliated with TripAstute.]]>',
                            // maximum 2048 symbols
                            // 'video:content_loc'           => '',
                            'video:player_loc' => 'https://player.vimeo.com/video/319469220?color=4684c4',
                            'video:duration' => '494',
                            // 'video:expiration_date'       => '',
                            'video:rating' => '5.0',
                            // 'video:view_count'            => '',
                            'video:publication_date' => '2019-02-25T08:46:00+00:00',
                            'video:family_friendly' => 'yes',
                            // 'video:platform' => '',
                            'video:price' => ['value' => '0', 'attr' => ['currency' => 'USD']],
                            // 'video:requires_subscription' => '',
                            'video:uploader' => ['value' => 'Ernest Shahbazian'],
                            'video:live' => 'no',
                            'video:tag' => [
                                'duplicates' => [
                                    'AwardWallet',
                                    'award wallet',
                                    'how to track points',
                                    'best way to track points',
                                    'how to track airline miles',
                                    'how to track hotel miles',
                                    'best way to track hotel points',
                                    'best way to track airline points',
                                    'best way to track credit card points',
                                    'how to track credit card points',
                                    'how to use awardwallet',
                                    'awardwallet plus',
                                    'is awardwallet worth it',
                                    'how to use award wallet',
                                    'best travel tools',
                                    'keep track of points',
                                    'tracking credit card points',
                                    'tracking hotel points',
                                    'tracking airline miles',
                                ],
                            ],
                            // maximum 32
                            'video:category' => 'travel app, points and miles, credit cards, reward programs, loyalty programs',
                            // maximum 256 symbols
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getImageData(string $addr): array
    {
        return [
            [
                'loc' => $this->generateUrl('aw_api_doc', ['item' => 'main'], UrlGeneratorInterface::ABSOLUTE_URL),
                'changefreq' => 'monthly',
                'image:image' => [
                    '_schema' => $this->imageSchema,
                    '_items' => [
                        [
                            'image:loc' => $addr . 'assets/awardwalletnewdesign/img/email-parsing.png',
                            'image:title' => 'AwardWallet Email Parsing API',
                            'image:caption' => 'The AwardWallet Email Parsing API retrieves travel reservations out of emails',
                        ],
                        [
                            'image:loc' => $addr . 'assets/awardwalletnewdesign/img/web-parsing.png',
                            'image:title' => 'AwardWallet Web Parsing API',
                            'image:caption' => 'The AwardWallet Web Parsing API supports the information from online loyalty accounts',
                        ],
                        [
                            'image:loc' => $addr . 'assets/awardwalletnewdesign/img/account-access.png',
                            'image:title' => 'AwardWallet Account Access API',
                            'image:caption' => 'The AwardWallet Account Access API provides access to an AwardWallet account',
                        ],
                    ],
                ],
            ],
        ];
    }
}
