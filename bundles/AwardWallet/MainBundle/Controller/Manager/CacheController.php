<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Service\Cache\Annotations\Tag;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Tags;
use Doctrine\Common\Annotations\DocParser;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @Route("/manager/cache")
 */
class CacheController extends AbstractController
{
    use JsonTrait;

    private \Memcached $memcached;

    public function __construct(\Memcached $memcached)
    {
        $this->memcached = $memcached;
    }

    /**
     * @Route("/", name="cache_control_inde", methods={"GET"}, options={"expose"=false})
     * @Security("is_granted('ROLE_MANAGE_CACHECONTROL')")
     * @Template("@AwardWalletMain/Manager/Cache/index.html.twig")
     */
    public function indexAction()
    {
        return [
            'initialState' => json_encode([
                'memcached' => $this->getMemcachedStats(),
                'tags' => $this->getTagsStats(),
            ]),
        ];
    }

    /**
     * @Route("/data/stats", name="cache_control_stats", methods={"GET"}, options={"expose"=false})
     * @Security("is_granted('ROLE_MANAGE_CACHECONTROL')")
     */
    public function statsAction()
    {
        return $this->jsonResponse([
            'memcached' => $this->getMemcachedStats(),
            'tags' => $this->getTagsStats(),
        ]);
    }

    /**
     * @Route("/data/invalidate", name="cache_control_invalidate", methods={"POST"}, options={"expose"=false})
     * @Security("is_granted('ROLE_MANAGE_CACHECONTROL') and is_granted('CSRF')")
     * @JsonDecode()
     */
    public function invalidateAction(Request $request, CacheManager $cacheManager)
    {
        $formTags = (array) $request->get('tags');

        $refl = new \ReflectionClass(Tags::class);
        $constants = $refl->getConstants();

        // filter only TAG_* constants
        $tags = array_values(array_intersect_key(
            $constants,
            array_flip(array_filter(
                array_keys($constants),
                function ($key) use (&$formTags) { return (strpos($key, 'TAG_') === 0) && in_array($key, $formTags, true); }
            ))
        ));

        $cacheManager->invalidateGlobalTags($tags);

        return $this->maybeJsonResponse(!empty($tags) ? true : "Invalid tags!");
    }

    private function getMemcachedStats()
    {
        $stats = $this->memcached->getStats();
        $serverName = key($stats);
        $mainMap = \array_merge(['server' => $serverName], $stats[$serverName]);
        $slabsMap =
            it($this->memcached->getStats('slabs')[$serverName])
            ->mapKeys(fn (string $key) => "[slabs] {$key}")
            ->toArrayWithKeys();

        return \array_merge($mainMap, $slabsMap);
    }

    private function getTagsStats()
    {
        $parser = new DocParser();
        $parser->setImports([
            'tag' => Tag::class,
            'nodi' => NoDI::class,
        ]);
        $refl = new \ReflectionClass(Tags::class);

        return
            it($parser->parse($refl->getDocComment()))
            ->filter(fn ($annotation) => $annotation instanceof Tag)
            ->toArray();
    }
}
