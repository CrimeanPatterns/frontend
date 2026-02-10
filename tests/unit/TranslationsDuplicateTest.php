<?php

namespace AwardWallet\Tests\Unit;

use PHPUnit\Framework\Assert;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @group translations
 */
class TranslationsDuplicateTest extends BaseContainerTest
{
    public function testDuplicateTranslations()
    {
        $finder = (new Finder())
            ->files()
            ->in($this->container->getParameter('kernel.root_dir') . '/../translations');

        $translations = [];
        $unionTranslations = [];
        $duplicateTranslates = [];
        $enFiles = [];

        /** @var SplFileInfo $splFile */
        foreach ($finder as $splFile) {
            $fileName = $splFile->getFilename();
            $locale = preg_replace('#.*\.([^\.\s]{2,5})\.xliff#', '$1', $fileName);
            $locales[] = $locale;

            if ($locale === 'en') {
                $enFiles[] = $splFile;

                continue;
            }
            $fileXml = new \SimpleXMLElement($splFile->getContents());

            foreach ($fileXml->file->body->{"trans-unit"} as $items) {
                $name = (string) $items->attributes()->resname;
                $value = (string) $items->target;
                $translations[$locale][$name] = $value;
            }
        }
        $locales = array_unique($locales);

        foreach ($enFiles as $file) {
            $fileXml = new \SimpleXMLElement($file->getContents());

            foreach ($fileXml->file->body->{"trans-unit"} as $items) {
                $enItemName = (string) $items->attributes()->resname;
                $enItemValue = (string) $items->target;

                // skip db keys like 'somekey.1'
                if (preg_match('/^[a-z0-9]+\.\d+$/', $enItemName)) {
                    continue;
                }

                $index = mb_substr($enItemValue, 0, 5) . mb_strlen($enItemValue);
                $foundDuplicate = false;

                if (isset($unionTranslations[$index])) {
                    foreach ($unionTranslations[$index] as $uName => $uValue) {
                        if ($enItemValue === $uValue) {
                            $foundDuplicate = true;
                            $foundDuplicateRecord = false;

                            // skip db keys like 'somekey.1'
                            if (preg_match('/^[a-z0-9]+\.\d+$/', $uName)) {
                                continue;
                            }

                            foreach ($duplicateTranslates as $key => $duplicate) {
                                if ($duplicate['value']['en'] === $enItemValue) {
                                    $foundDuplicateRecord = true;

                                    foreach ($locales as $loc) {
                                        if (!isset($translations[$loc][$enItemName])) {
                                            continue;
                                        }

                                        if (isset($translations[$loc][$enItemName]) && isset($duplicate['value'][$loc])
                                                && $translations[$loc][$enItemName] !== $duplicate['value'][$loc]) {
                                            unset($duplicateTranslates[$key]);

                                            continue 4;
                                        } elseif (isset($translations[$loc][$enItemName])) {
                                            $duplicateTranslates[$key]['value'][$loc] = $translations[$loc][$enItemName];
                                        }
                                    }
                                    $duplicateTranslates[$key]['names'][] = $enItemName;

                                    break;
                                }
                            }

                            if ($foundDuplicateRecord === false) {
                                $dupValues = ['en' => $uValue];

                                foreach ($locales as $loc) {
                                    if (!isset($translations[$loc][$enItemName]) || !isset($translations[$loc][$uName])) {
                                        continue;
                                    }

                                    if ($translations[$loc][$enItemName] !== $translations[$loc][$uName]) {
                                        continue 3;
                                    } else {
                                        $dupValues[$loc] = $translations[$loc][$enItemName];
                                    }
                                }

                                $duplicateTranslates[] = [
                                    'names' => [$uName, $enItemName],
                                    'value' => $dupValues,
                                ];
                            }
                        }
                    }
                }

                if ($foundDuplicate === false) {
                    $unionTranslations[$index][$enItemName] = $enItemValue;
                }
            }
        }

        if (!empty($duplicateTranslates)) {
            Assert::fail(json_encode($duplicateTranslates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
}
