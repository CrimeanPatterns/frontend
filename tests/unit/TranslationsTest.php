<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\StringUtils;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function PHPUnit\Framework\assertEquals;

/**
 * @group frontend-unit
 * @group unstable
 * @group translations
 */
class TranslationsTest extends BaseContainerTest
{
    public const EXPECTED_MATCHES = [
        //        // file
        //        'faq.en.xliff' => [
        //            // key
        //            'answer.41' => [
        //                // exclusion
        //                '> <li>Click File -&gt; New Calendar Subscr',
        //                ' <li> Click File -&gt; New Calendar Subscr',
        //            ],
        //            'answer.42' => [
        //                'pen Calendar&quot; -&gt; &quot;From Internet',
        //                'pen Calendar&quot; -&gt; &quot;From Internet',
        //
        //            ]
        //        ]
    ];

    public const LIST_PATTERN = 'file: "%s", key: "%s", error: "%s"';

    public const MISSING_TRANSLATION_EXCLUSIONS = [];

    public const BROKEN_PLACEHOLDERS_EXCLUSIONS = [];

    public function testInvalidTranslations()
    {
        $expected = [];

        foreach (self::EXPECTED_MATCHES as $fileName => $keys) {
            foreach ($keys as $key => $matches) {
                foreach ($matches as $match) {
                    $expected[] = sprintf(self::LIST_PATTERN, $fileName, $key, preg_replace('/[\s\r\n]+/', ' ', $match));
                }
            }
        }

        $actual = [];
        $finder = (new Finder())
            ->files()
            ->in($this->container->getParameter('kernel.root_dir') . '/../translations');

        /** @var SplFileInfo $splFile */
        foreach ($finder as $splFile) {
            $fileName = $splFile->getFilename();

            if (stripos($fileName, "faq") !== false) {
                continue;
            }

            if (preg_match_all('/.{20}(?>(&(?>lt;|gt;))|(CDATA\[\])).{20}/ims', $content = $splFile->getContents(), $matches, PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    [$text, $offset] = $match;
                    // real offset without context
                    $realOffset = $offset + 20;
                    $lastTransUnitPos = strrpos(substr($content, 0, $realOffset), '<trans-unit ');

                    if (preg_match('/<trans-unit[^>]+resname="([^"]+)"/ims', substr($content, $lastTransUnitPos, $realOffset - $lastTransUnitPos), $tagMatches)) {
                        $actual[] = sprintf(self::LIST_PATTERN, $fileName, $tagMatches[1], preg_replace('/[\s\r\n]+/', ' ', $text));
                    }
                }
            }
        }

        assertEquals(var_export($expected, true), var_export($actual, true));
    }

    public function testLostEnglishTranslations()
    {
        $this->assertBrokenTranslations(
            function (Finder $finder) { return $finder->name("*.en.xliff"); },
            function ($key, $_, \SimpleXMLElement $element) {
                foreach (['source', 'target'] as $propertyName) {
                    if (
                        isset($element->$propertyName)
                        && ($element->$propertyName != "")
                        && ((string) $element->$propertyName === $key)
                    ) {
                        return true;
                    }
                }

                return false;
            },
            self::MISSING_TRANSLATION_EXCLUSIONS
        );
    }

    public function testLostNonEnglishTranslations()
    {
        $missExclusion = self::MISSING_TRANSLATION_EXCLUSIONS;
        $englishOnlyId = $this->container->get('doctrine')->getManager()->getConnection()->executeQuery('SELECT FaqID FROM Faq WHERE EnglishOnly = 1')->fetchAll();

        if (!empty($englishOnlyId)) {
            $englishOnlyId = array_column($englishOnlyId, 'FaqID');

            foreach (glob($this->container->getParameter('kernel.root_dir') . '/../translations/faq.*.xliff') as $file) {
                $fileName = pathinfo($file, PATHINFO_BASENAME);

                if ('faq.en.xliff' === $fileName) {
                    continue;
                }

                for ($i = -1, $iCount = count($englishOnlyId); ++$i < $iCount;) {
                    $missExclusion[] = $fileName . ': "answer.' . $englishOnlyId[$i] . '"';
                    $missExclusion[] = $fileName . ': "question.' . $englishOnlyId[$i] . '"';
                }
            }
        }

        $this->assertBrokenTranslations(
            function (Finder $finder) { return $finder->notName("*.en.xliff"); },
            function ($key, $_, \SimpleXMLElement $element) {
                $source = isset($element->source) ? (string) $element->source : '';
                $target = isset($element->target) ? (string) $element->target : '';

                if (
                    !StringHandler::isEmpty($source)
                    && !StringHandler::isEmpty($target)
                    && (count(explode(' ', $target)) > 4)
                    && ($source === $target)
                    && !preg_match('#without translation|leave it as it is|do not translate#ims', $element->note)
                ) {
                    return true;
                }

                return false;
            },
            $missExclusion
        );
    }

    /**
     * @group unstable
     */
    public function testBrokenPlaceholders()
    {
        $this->assertBrokenTranslations(
            function (Finder $finder) { return $finder->notName("*.en.xliff"); },
            function ($key, $domain, \SimpleXMLElement $element) {
                static $englishTargetCache = [];
                static $domainXmlCache = [];

                if (StringUtils::isEmpty($testTarget = isset($element->target) ? (string) $element->target : '')) {
                    return false;
                }

                if (
                    !isset($domainXmlCache[$domain])
                    && file_exists($domainXliffFile = $this->container->getParameter('kernel.root_dir') . "/../translations/{$domain}.en.xliff")
                ) {
                    $domainXmlCache[$domain] = new \SimpleXMLElement(file_get_contents($domainXliffFile));
                    $domainXmlCache[$domain]->registerXPathNamespace('', 'urn:oasis:names:tc:xliff:document:1.2');

                    /** @var \SimpleXMLElement $transUnit */
                    foreach ($domainXmlCache[$domain]->xpath('//*[name() = "trans-unit"]') as $transUnit) {
                        if (
                            !StringUtils::isEmpty($enTarget = isset($transUnit->target) ? (string) $transUnit->target : '')
                            && (strpos($enTarget, '%') !== false)
                            && preg_match_all('/%[a-z0-9_-]+%?/ims', $enTarget, $matches)
                        ) {
                            $englishTargetCache[$domain][(string) $transUnit->attributes()->resname] = $matches[0];
                        }
                    }
                }

                if (!isset($englishTargetCache[$domain][$key])) {
                    return false;
                }

                $missing = [];

                foreach ($englishTargetCache[$domain][$key] as $placeholder) {
                    if (strpos($testTarget, $placeholder) === false) {
                        $missing[] = $placeholder;
                    }
                }

                $new = array_values(array_diff(
                    preg_match_all('/(*UTF8)%[\wa-z0-9_-]+%?/iums', $testTarget, $matches) ? $matches[0] : [],
                    $englishTargetCache[$domain][$key]
                ));
                $result = [];

                if ($missing) {
                    $result['missing'] = $missing;
                }

                if ($new) {
                    $result['new'] = $new;
                }

                if ($result) {
                    $result = "{$key}: " . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                }

                return $result;
            },
            self::BROKEN_PLACEHOLDERS_EXCLUSIONS
        );
    }

    /**
     * @group unstable
     */
    public function testBrokenFaqTranslations()
    {
        $this->assertBrokenTranslations(
            function (Finder $finder) {
                return $finder
                    ->notName("faq.en.xliff")
                    ->name("faq.*.xliff");
            },
            function ($key, $_, \SimpleXMLElement $element) {
                if (
                    isset($element->target)
                    && ($element->target != "")
                    && mb_strpos($element->target, '<![CDATA[') !== false
                ) {
                    return true;
                }

                return false;
            },
            self::MISSING_TRANSLATION_EXCLUSIONS
        );
    }

    protected function assertBrokenTranslations(\Closure $finderTransformer, \Closure $detector, array $exclusions = [])
    {
        $finder = (new Finder())
            ->files()
            ->in($this->container->getParameter('kernel.root_dir') . '/../translations');

        $finder = $finderTransformer($finder);

        $matches = [];

        foreach ($finder as $splFileInfo) {
            $fileName = $splFileInfo->getFilename();

            $xliffDom = new \SimpleXMLElement($splFileInfo->getContents());
            $xliffDom->registerXPathNamespace('', 'urn:oasis:names:tc:xliff:document:1.2');

            foreach ($xliffDom->xpath('//*') as $element) {
                if ($element->getName() !== 'trans-unit') {
                    continue;
                }

                $key = (string) $element->attributes()->resname;

                if (in_array($key, ['and', 'or', 'Register'])) {
                    continue;
                }
                $children = $element->children();
                $domain = substr($fileName, 0, strpos($fileName, '.'));

                $result = $detector($key, $domain, $children);

                if (true === $result) {
                    $matches[] = "{$fileName}: \"$key\"";
                } elseif (is_string($result)) {
                    $matches[] = "{$fileName}: {$result}";
                }
            }
        }

        if ($diff = array_diff($matches, $exclusions)) {
            self::fail(implode("\n", $diff));
        }
    }
}
