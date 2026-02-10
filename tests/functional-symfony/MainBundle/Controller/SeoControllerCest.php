<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use Codeception\Example;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 */
class SeoControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    private ?RouterInterface $router;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->router = $I->grabService('router');
    }

    /**
     * @dataProvider dataRoutes
     */
    public function sitemapXml(\TestSymfonyGuy $I, Example $example)
    {
        $I->sendGET($this->router->generate($example['route']));
        $I->seeResponseCodeIs(200);
        $I->seeHttpHeader('Content-type', 'application/xml; charset=utf-8');
    }

    private function dataRoutes(): array
    {
        return [
            ['route' => 'aw_sitemap_site'],
            ['route' => 'aw_sitemap_blog_news'],
            ['route' => 'aw_sitemap_blog_posts'],
            ['route' => 'aw_sitemap_blog_authors'],
            ['route' => 'aw_sitemap_blog_tags'],
            ['route' => 'aw_sitemap_blog_categories'],
            ['route' => 'aw_sitemap_blog_pages'],
        ];
    }
}
