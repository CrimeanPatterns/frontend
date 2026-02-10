<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

use JMS\TranslationBundle\Model\FileSource;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\Extractor\FileVisitorInterface;
use Twig\Node\Node;

class AngularSymfonyTranslationExtractor implements FileVisitorInterface
{
    public function visitFile(\SplFileInfo $file, MessageCatalogue $catalogue)
    {
        if (!(strpos($file->getPathname(), 'mobile/templates/') === 0 && 'html' === $file->getExtension())) {
            return;
        }

        $translations = $this->parsePlaceholders(file_get_contents($file));

        foreach ($translations as $translation) {
            $message = new Message($translation['key'], $translation['domain']);

            if (isset($translation['desc'])) {
                $message->setDesc($translation['desc']);
            }
            $message->addSource(new FileSource(
                (string) $file,
                $translation['line'] ?? null,
                $translation['column'] ?? null
            )
            );
            $catalogue->add($message);
        }
    }

    /**
     * @param string $text
     * @return array[array]
     * @throws \RuntimeException
     */
    public function parsePlaceholders($text)
    {
        if (!(preg_match_all(self::getPlaceholderPattern(), $text, $placeholderMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) && !empty($placeholderMatches))) {
            return [];
        }

        $textLines = explode("\n", $text);

        $lineOffsets = self::computeLineOffsets($textLines);

        $translations = [];

        foreach ($placeholderMatches as $placeholderMatch) {
            if (empty($placeholderMatch[0])) {
                continue;
            }
            $placeholderOffset = $placeholderMatch[0][1];
            $placeholder = trim(mb_substr($placeholderMatch[0][0], 2, mb_strlen($placeholderMatch[0][0]) - 4));

            if (!preg_match(self::getPlaceholderContentPattern(), $placeholder, $filterMatch)) {
                continue;
            }

            $argumentsCount = 0;

            for ($i = 3; $i >= 1; $i--) {
                if (isset($filterMatch["trans_filter_arg_{$i}"]) && ('' !== $filterMatch["trans_filter_arg_{$i}"])) {
                    $argumentsCount = $i;

                    break;
                }
            }

            $transfilter = $filterMatch['trans_filter'];
            $domain = 'messages';

            switch (true) {
                case 3 === $argumentsCount:
                    if ('trans' === $transfilter) {
                        throw new \RuntimeException(sprintf('Unexpected argument %s for "trans" filter, placeholder: %s', $argumentsCount, $placeholder));
                    }
                    $domain = self::parseString($filterMatch['trans_filter_arg_3']);

                    break;

                case 2 === $argumentsCount:
                    if ('trans' === $transfilter) {
                        $domain = self::parseString($filterMatch['trans_filter_arg_2']);
                    }

                    break;

                case $argumentsCount <= 1:
                    if ('transChoice' === $transfilter) {
                        throw new \RuntimeException(sprintf('Missing arguemnt for "%s" filter, placeholder: %s', $transfilter, $placeholder));
                    }
            }
            $translation = [];
            $translation['key'] = self::parseString($filterMatch['key']);
            $translation['domain'] = $domain;

            if (isset($lineOffsets[$placeholderOffset])) {
                $line = $lineOffsets[$placeholderOffset];
                $translation['line'] = $lineOffsets[$placeholderOffset];
                $line--; // machine-like numeration

                if (isset($textLines[$line]) && false !== ($column = strpos($textLines[$line], $placeholderMatch[0][0]))) {
                    $translation['column'] = $column + 1;
                }
            }

            if (isset($filterMatch['desc_filter_arg_1'])) {
                $translation['desc'] = self::parseString($filterMatch['desc_filter_arg_1']);
            }

            $translations[] = $translation;
            unset($filterMatch);
        }
        unset($placeholderMatches);

        return $translations;
    }

    public function visitPhpFile(\SplFileInfo $file, MessageCatalogue $catalogue, array $ast)
    {
    }

    public function visitTwigFile(\SplFileInfo $file, MessageCatalogue $catalogue, Node $ast)
    {
    }

    /**
     * @return array
     */
    private static function computeLineOffsets(array &$lines)
    {
        $lastOffset = 0;
        $lineOffsets = [];
        $linesCount = count($lines);

        for ($i = 0; $i < $linesCount; $i++) {
            $lineEnd = $lastOffset + mb_strlen($lines[$i]);
            $lineNumber = $i + 1;

            for ($j = $lastOffset; $j < $lineEnd; $j++) {
                $lineOffsets[$j] = $lineNumber;
            }
            $lastOffset = $lineEnd + 1;
        }

        return $lineOffsets;
    }

    /**
     * @param string $value
     * @return string
     * @throws \RuntimeException
     */
    private static function parseString($value)
    {
        if (!preg_match("/^(['\"]).*\\1$/sm", $value)) {
            throw new \RuntimeException(sprintf('Invalid string literal %s', $value));
        }

        return stripslashes(mb_substr($value, 1, mb_strlen($value) - 2));
    }

    private static function getPlaceholderPattern()
    {
        return '/# capture {{ content }}
            \{
                (\{
                    (
                          (?>[^{}]+)
                        |
                          (?1) # recursion
                    )*
                \})
            \}
        /x';
    }

    private static function getPlaceholderContentPattern()
    {
        $simpleExpressionParameterPattern = self::getSimpleExpressionParameterPattern();
        $filterPattern = self::getFilterPattern();
        $stringPattern = self::getStringPattern();

        return "@^(?:::)?
            (?P<key>{$stringPattern})  # translation key
            \s*\|\s*
            (?P<trans_filter>{$filterPattern}) # angular-translate filter
            (?:
                \s*:\s*
                (?P<trans_filter_arg_1>{$simpleExpressionParameterPattern}) # first filter argument
            )?
            (?:
                \s*:\s*
                (?P<trans_filter_arg_2>{$simpleExpressionParameterPattern}) # second argument
            )?
            (?:
                \s*:\s*
                (?P<trans_filter_arg_3>{$simpleExpressionParameterPattern}) # third argument
            )?
            (?:
                \s*\|\s*
                (?P<desc_filter>{$filterPattern}) # description filter
                (
                    \s*:\s*
                    (?P<desc_filter_arg_1>{$simpleExpressionParameterPattern}) # first argument
                )?
            )?
            $@xm";
    }

    private static function getStringPattern()
    {
        return ' # capture string literals with escaping
            (?:
                  "
                  (?:
                        [^"\\\\]
                      |
                        \\\\.
                  )*
                  "
                |
                  \'
                  (?:
                        [^\'\\\\]
                      |
                        \\\\.
                  )*
                 \'
            )
            ';
    }

    private static function getFilterPattern()
    {
        return 'trans|transChoice|desc';
    }

    private static function getSimpleExpressionParameterPattern()
    {
        $stringPattern = self::getStringPattern();

        return "
              \{[^}]*\} # plain object with substitutions. Empty {} may be passed
            |
              {$stringPattern} # string literals
            |
              [^:\|]+ # js bitwise operations are fucked up
        ";
    }
}
