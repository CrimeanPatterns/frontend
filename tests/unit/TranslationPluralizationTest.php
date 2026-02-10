<?php

namespace AwardWallet\Tests\Unit;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @group frontend-unit
 */
class TranslationPluralizationTest extends BaseContainerTest
{
    private ?TranslatorInterface $translator;

    public function _before()
    {
        parent::_before();

        $this->translator = $this->container->get('translator');
    }

    public function _after()
    {
        $this->translator = null;

        parent::_after();
    }

    public function test()
    {
        $files = $this->getFinder()
            ->name('/\.en\.xliff$/i')
            ->files();

        foreach ($files as $file) {
            $this->testDomain($file->getBasename('.en.xliff'));
        }
    }

    private function testDomain(string $domain)
    {
        foreach ($this->container->getParameter('locales') as $locale) {
            $this->testKeys(
                $keys = $this->getPluralKeys($domain, $locale),
                $locale,
                $domain
            );

            if (count($keys) > 0) {
                codecept_debug(
                    sprintf(
                        'tested %d keys for locale %s in domain %s',
                        count($keys),
                        $locale,
                        $domain
                    )
                );
            }
        }
    }

    private function testKeys(array $keys, string $locale, string $domain)
    {
        foreach ($keys as $key => $value) {
            // parse placeholders
            $placeholders = [];
            preg_match_all('/%(\w+)%/', $value, $placeholders);
            $params = array_combine($placeholders[0], array_fill(0, count($placeholders[0]), 1));

            foreach ([1, 2, 5] as $count) {
                try {
                    $this->assertIsString(
                        $this->translator->trans(
                            $key,
                            $mergedParams = array_merge($params, ['%count%' => $count]),
                            $domain,
                            $locale
                        )
                    );
                } catch (InvalidArgumentException $e) {
                    codecept_debug(
                        sprintf(
                            'failed to translate key "%s" with params "%s" in locale "%s" in domain "%s"',
                            $key,
                            json_encode($mergedParams),
                            $locale,
                            $domain
                        )
                    );

                    throw $e;
                }
            }
        }
    }

    private function getPluralKeys(string $domain, string $locale): array
    {
        $files = $this->getFinder()
            ->name("/^{$domain}\.{$locale}\.xliff$/")
            ->files()
            ->getIterator();
        $files->rewind();
        $file = $files->current();

        if (!$file) {
            return [];
        }

        $keys = [];
        $xml = new \SimpleXMLElement($file->getContents());

        foreach ($xml->file->body->{"trans-unit"} as $item) {
            $key = (string) $item->attributes()->resname;
            $value = (string) $item->target;

            if (in_array($key, ['faq-awardwallet', 'page.aboutus.title', 'press-media-awardwallet'])) {
                continue;
            }

            if (strpos($value, '|') !== false) {
                $keys[$key] = $value;
            }
        }

        return $keys;
    }

    private function getFinder(): Finder
    {
        return (new Finder())
            ->files()
            ->in($this->container->getParameter('kernel.root_dir') . '/../translations');
    }
}
