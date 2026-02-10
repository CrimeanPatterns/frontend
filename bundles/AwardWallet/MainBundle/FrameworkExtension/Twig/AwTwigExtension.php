<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Twig;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Form\Transformer\HTMLPurifierTransformer;
use AwardWallet\MainBundle\FrameworkExtension\Translator\EntityTranslator;
use AwardWallet\MainBundle\FrameworkExtension\Twig\Trim\TrimTokenParser;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\BalanceFormatter;
use AwardWallet\MainBundle\Service\SecureLink;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Error\RuntimeError;

class AwTwigExtension extends \Twig_Extension
{
    public const DOMAINS = "aero|asia|biz|cat|com|coop|edu|gov|info|int|jobs|mil|mobi|museum|name|net|org|pro|tel|travel|
        xxx|ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|bj|bm|bn|bo|br|bs|bt|bv|
        bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cs|cu|cv|cx|cy|cz|dd|de|dev|dj|dk|dm|do|dz|ec|ee|eg|er|es|et|
        eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|
        im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|
        md|me|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nu|nz|om|pa|pe|
        pf|pg|ph|pk|pl|pm|pn|pr|ps|pt|pw|py|qa|re|ro|rs|ru|рф|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|
        su|sv|sy|sz|tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|
        ws|ye|yt|za|zm|zw";

    /**
     * @var EntityTranslator
     */
    protected $entranslator;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var LocalizeService
     */
    protected $localizer;

    protected $srcPath;

    /**
     * @var SecureLink
     */
    protected $secureLink;
    /**
     * @var HTMLPurifierTransformer
     */
    private $purifier;

    /**
     * @var BalanceFormatter
     */
    private $balanceFormatter;

    public function __construct(
        EntityTranslator $entranslator,
        RequestStack $request,
        \Twig_Loader_Filesystem $loader,
        EntityManager $em,
        LocalizeService $localizer,
        SecureLink $secureLink,
        HTMLPurifierTransformer $purifier,
        BalanceFormatter $balanceFormatter,
        $templatePath,
        $srcPath
    ) {
        $this->entranslator = $entranslator;
        $this->em = $em;
        $this->localizer = $localizer;
        $this->srcPath = $srcPath;
        $this->secureLink = $secureLink;
        $this->purifier = $purifier;
        $this->balanceFormatter = $balanceFormatter;
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('js_json_encode', [$this, 'js_json_encode']),
            new \Twig_SimpleFilter('preg_replace', [$this, 'preg_replace']),
            new \Twig_SimpleFilter('auto_link', [$this, 'auto_link'], ['is_safe' => ['html']]),
            new \Twig_SimpleFilter('auto_link_abs', [$this, 'auto_link_abs'], ['is_safe' => ['html']]),
            new \Twig_SimpleFilter('auto_mailto', [$this, 'auto_mailto'], ['is_safe' => ['html']]),
            new \Twig_SimpleFilter('entrans', [$this, 'entrans']),
            new \Twig_SimpleFilter('entransChoice', [$this, 'entransChoice']),
            new \Twig_SimpleFilter('htmlencode', [$this, 'htmlencode']),
            new \Twig_SimpleFilter('htmldecode', [$this, 'htmldecode']),
            new \Twig_SimpleFilter('base64encode', [$this, 'base64encode']),
            new \Twig_SimpleFilter('formatBalance', [$this, 'formatBalance']),
            new \Twig_SimpleFilter('joinAssociative', [$this, 'joinAssociative']),
            new \Twig_SimpleFilter('html_entity_decode', 'html_entity_decode'),
            new \Twig_SimpleFilter('add_down_arrow', [$this, 'add_down_arrow'], ['is_safe' => ['html']]),
            new \Twig_SimpleFilter('filterMobileCSP', [$this, 'filterMobileCSP'], ['is_safe' => ['html']]),
            new \Twig_SimpleFilter('disableEmailLink', [$this, 'disableEmailLink']),
            new \Twig_SimpleFilter('sortByProperty', [$this, 'sortByProperty']),
            new \Twig_SimpleFilter('proxy_links', [$this, 'proxyLinks']),
            new \Twig_SimpleFilter('addslashes', [$this, 'addslashes']),
            new \Twig_SimpleFilter('html_purifier', [$this, 'htmlPurifier']),
            new \Twig_SimpleFilter('var_dump', [$this, 'var_dump']),
        ];
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('randomCode', [$this, 'randomCode']),
            new \Twig_SimpleFunction('html_classes', [$this, 'htmlClasses']),
        ];
    }

    public function getTokenParsers()
    {
        return [
            new TrimTokenParser(),
        ];
    }

    public function getTests()
    {
        return [
            new \Twig_SimpleTest('instanceof', [$this, 'isInstanceof']),
            new \Twig_SimpleTest('typeof', [$this, 'isTypeof']),
        ];
    }

    public function js_json_encode($var)
    {
        $functionPattern = "%^\\s*function\\s*\\(%is";
        $callbackPattern = "%^\\s*f:%is";
        $jsonPattern = "%^\\s*\\{.*\\}\\s*$%is";
        $arrayPattern = "%^\\s*\\[.*\\]\\s*$%is";

        if (is_bool($var)) {
            return $var ? 'true' : 'false';
        }

        if (is_null($var)) {
            return 'null';
        }

        if ('undefined' === $var) {
            return 'undefined';
        }

        if (is_string($var) && preg_match($callbackPattern, $var)) {
            return preg_replace($callbackPattern, '', $var);
        }

        if (is_string($var) && !preg_match($functionPattern, $var) && !preg_match($jsonPattern, $var) && !preg_match($arrayPattern, $var)) {
            return '"' . str_replace('"', '&quot;', $var) . '"';
        }

        if (is_array($var)) {
            $is_assoc = function ($array) {
                return (bool) count(array_filter(array_keys($array), 'is_string'));
            };

            if ($is_assoc($var)) {
                $items = [];

                foreach ($var as $key => $val) {
                    $items[] = '"' . $key . '": ' . $this->js_json_encode($val);
                }

                return '{' . implode(',', $items) . '}';
            } else {
                $items = [];

                foreach ($var as $val) {
                    $items[] = $this->js_json_encode($val);
                }

                return '[' . implode(',', $items) . ']';
            }
        }

        return $var;
    }

    public function preg_replace($value, $pattern, $replacement = '', $limit = -1)
    {
        if (!isset($value)) {
            return null;
        }

        return preg_replace($pattern, $replacement, $value, $limit);
    }

    public function auto_link(
        $text,
        $schemeAndHttpHost = null,
        $outPolicy = null,
        $urlGeneratorReferenceType = null,
        array $linkAttrs = []
    ) {
        if (!isset($schemeAndHttpHost)) {
            $schemeAndHttpHost = $this->secureLink->getSchemeAndHttpHost();
        }

        if (!isset($urlGeneratorReferenceType)) {
            $urlGeneratorReferenceType = UrlGeneratorInterface::ABSOLUTE_PATH;
        }
        $linkAttrs = $this->htmlAttrs($linkAttrs);
        $_this = $this;
        $result = preg_replace_callback("/(?<=^|\s)(https?:\/\/)?([\w\-]+\.)+(" . self::DOMAINS . ")(?=\/|$|\s|<|\.)(\/[^\s\/<]*)*/ims", function ($matches) use ($_this, $schemeAndHttpHost, $urlGeneratorReferenceType, $outPolicy, $linkAttrs) {
            $originalUrl = $matches[0];
            $url = $matches[0];

            if (empty($matches[1])) {
                $url = sprintf('%s://%s', $_this->isAwHost($url, $schemeAndHttpHost) ? $this->secureLink->getScheme() : 'http', $url);
            }

            if (
                (isset($outPolicy) && !$outPolicy)
                || (!isset($outPolicy) && $_this->isAwHost($url, $schemeAndHttpHost))
            ) {
                $href = $url;
            } else {
                $href = $_this->secureLink->protectUrl($url, $schemeAndHttpHost, false, $urlGeneratorReferenceType);
            }

            return '<a target="_blank" href="' . $href . '"' . $linkAttrs . '>' . $originalUrl . '</a>';
        }, $text);

        return $result;
    }

    public function auto_link_abs(?string $text): string
    {
        if (null === $text) {
            return '';
        }

        return preg_replace(
            '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i',
            '<a href="$0" target="_blank">$0</a>',
            $text
        );
    }

    public function auto_mailto($content, array $linkAttrs = [])
    {
        $linkAttrs = $this->htmlAttrs(array_merge([
            'style' => [
                'color' => '#4684c4',
            ],
        ], $linkAttrs));

        $regex = '/((?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\])))/iD';

        return preg_replace(
            $regex,
            "<a href=\"mailto:$1\"{$linkAttrs}>$1</a>",
            $content
        );
    }

    public function entrans($entity, $property, array $parameters = [], $domain = null, $locale = null)
    {
        return $this->entranslator->trans(/** @Ignore */ $entity, $property, $parameters, $domain, $locale);
    }

    public function entransChoice($entity, $property, $number, array $parameters = [], $domain = null, $locale = null)
    {
        return $this->entranslator->transChoice(/** @Ignore */ $entity, $property, $number, $parameters, $domain, $locale);
    }

    public function isInstanceof($var, $class)
    {
        return $var instanceof $class;
    }

    public function isTypeof($var, string $type)
    {
        return gettype($var) === $type;
    }

    public function htmlencode($var)
    {
        return htmlspecialchars($var);
    }

    public function htmldecode($var)
    {
        return htmlspecialchars_decode($var);
    }

    public function addslashes($var)
    {
        return addslashes($var);
    }

    public function base64encode($var)
    {
        return base64_encode($var);
    }

    public function disableEmailLink($email)
    {
        return preg_replace("/@|\./", "&#8203;$0", $email);
    }

    /**
     * @param array|PersistentCollection $collection
     * @return array|PersistentCollection
     */
    public function sortByProperty($collection, $propertyPath)
    {
        $values = $collection instanceof PersistentCollection ? $collection->getValues() : $collection;
        $accessor = PropertyAccess::createPropertyAccessor();
        usort($values, function ($item1, $item2) use ($accessor, $propertyPath) {
            $value1 = $accessor->getValue($item1, $propertyPath);
            $value2 = $accessor->getValue($item2, $propertyPath);

            return $value2 - $value1;
        });

        return $values;
    }

    /**
     * @param Account|Subaccount|Providercoupon $account
     * @param string $locale
     * @param string $naValue
     * @return string
     */
    public function formatBalance($account, $locale, $naValue = '-')
    {
        if ($account instanceof Providercoupon) {
            $value = $account->getValue();

            if (is_null($value)) {
                return $naValue;
            }

            return html_entity_decode($value);
        } elseif ($account instanceof Account) {
            return $this->balanceFormatter->formatAccount($account, false, $naValue, $locale);
        } elseif ($account instanceof Subaccount) {
            return $this->balanceFormatter->formatSubAccount($account, false, $naValue, $locale);
        } else {
            throw new \LogicException('Account has the wrong type');
        }
    }

    public function joinAssociative($array, $glue1, $glue2)
    {
        $array2 = [];

        foreach ($array as $k => $v) {
            $array2[] = $k . $glue1 . $v;
        }

        return implode($glue2, $array2);
    }

    public function add_down_arrow($value, $options = [])
    {
        if (!isset($value)) {
            return null;
        }

        $escape = $options['escape'] ?? true;
        $icon = $options['icon'] ?? 'icon-silver-arrow-down';
        $addToLastWord = $options['addToLastWord'] ?? true;

        if ($escape) {
            $value = htmlspecialchars($value);
        }
        $result = $addToLastWord
            ? preg_replace('/(?<=\s|\A)(\S+)(<\/\w+>)?\z/', " <span>$1<i class='{$icon}'></i></span>$2", $value)
            : "<span>{$value}<i class='{$icon}'></i></span>";

        if ($addToLastWord && false === strpos($result, '<')) {
            $parts = explode(' ', $value, 2);

            if (2 === \count($parts)) {
                return $parts[0] . ' <span>' . $parts[1] . '<i class="' . $icon . '"></i></span>';
            }
        }

        return $result;
    }

    public function filterMobileCSP($text, $schemeAndHttpHost = null)
    {
        if (!isset($schemeAndHttpHost)) {
            $schemeAndHttpHost = $this->secureLink->getSchemeAndHttpHost();
        }

        $text = preg_replace_callback(
            "#(href\s*=\s*[\'\"])([^\'\"]+)([\'\"])#ims",
            function ($matches) use ($schemeAndHttpHost) {
                if (substr($matches[2], 0, 1) == '#') {
                    return $matches[1] . $matches[2] . $matches[3];
                }
                $parts = parse_url($matches[2]);

                if (isset($parts['scheme']) && $parts['scheme'] == 'mailto') {
                    return $matches[1] . $matches[2] . $matches[3];
                }

                if (isset($parts['scheme']) && isset($parts['host'])) {
                    return $matches[1] . $matches[2] . $matches[3];
                }

                if (!isset($parts['scheme']) && isset($parts['host'])) {
                    return $matches[1] . "http://" . ltrim($matches[2], "/") . $matches[3];
                }

                if (isset($parts['path'])) {
                    $matches[2] = ltrim($matches[2], "/");

                    if (preg_match("#^([[:word:]]+\.)+(" . self::DOMAINS . ")\/#", $parts['path'])) {
                        return $matches[1] . "http://" . $matches[2] . $matches[3];
                    } else {
                        return $matches[1] . $schemeAndHttpHost . "/" . $matches[2] . $matches[3];
                    }
                }

                return $matches[1] . $matches[2] . $matches[3];
            },
            $text
        );

        $_this = $this;
        $text = preg_replace_callback(
            "#(<\s*img\s+[^>]*src\s*=\s*[\'\"])([^\'\"]+)([\'\"])#ims",
            function ($matches) use ($_this, $schemeAndHttpHost) {
                $matches[2] = trim($matches[2]);
                $parts = parse_url($matches[2]);

                if (isset($parts['scheme']) && isset($parts['host'])) {
                    if (!$_this->isAwHost($parts['host'], $schemeAndHttpHost)) {
                        $matches[2] = $_this->secureLink->protectImgUrl($matches[2], $schemeAndHttpHost);
                    }
                } elseif (!isset($parts['scheme']) && isset($parts['host'])) {
                    $matches[2] = "http://" . ltrim($matches[2], "/");

                    if (!$_this->isAwHost($parts['host'], $schemeAndHttpHost)) {
                        $matches[2] = $_this->secureLink->protectImgUrl($matches[2], $schemeAndHttpHost);
                    }
                } elseif (!isset($parts['scheme']) && !isset($parts['host']) && isset($parts['path'])) {
                    if (preg_match("#^([[:word:]]+\.)+(" . self::DOMAINS . ")\/#", $parts['path'])) {
                        $matches[2] = "http://" . ltrim($matches[2], "/");

                        if (!$_this->isAwHost($parts['path'], $schemeAndHttpHost)) {
                            $matches[2] = $_this->secureLink->protectImgUrl($matches[2], $schemeAndHttpHost);
                        }
                    } else {
                        $matches[2] = $schemeAndHttpHost . "/" . ltrim($matches[2], "/");
                    }
                }

                return $matches[1] . $matches[2] . $matches[3];
            },
            $text
        );

        return $text;
    }

    public function proxyLinks($text, $url, $varName)
    {
        return preg_replace_callback(
            "#(href\s*=\s*[\'\"])([^\'\"]+)([\'\"])#ims",
            function ($matches) use ($url, $varName) {
                if (substr($matches[2], 0, 1) == '#') {
                    return $matches[1] . $matches[2] . $matches[3];
                }
                $parts = parse_url($matches[2]);

                if (isset($parts['scheme']) && $parts['scheme'] == 'mailto') {
                    return $matches[1] . $matches[2] . $matches[3];
                }

                return
                    $matches[1] . $url .
                    (strpos($url, "?") === false ? "?" : "&") . $varName . "=" . urlencode(html_entity_decode($matches[2])) .
                $matches[3];
            },
            $text
        );
    }

    public function randomCode($length)
    {
        return StringHandler::getRandomCode($length);
    }

    public function var_dump($var)
    {
        \ob_start();
        \var_dump($var);

        return \ob_get_clean();
    }

    public function htmlClasses(...$args): string
    {
        $classes = [];

        foreach ($args as $i => $arg) {
            if (\is_string($arg)) {
                $classes[] = $arg;
            } elseif (\is_array($arg)) {
                foreach ($arg as $class => $condition) {
                    if (!\is_string($class)) {
                        throw new RuntimeError(sprintf('The html_classes function argument %d (key %d) should be a string, got "%s".', $i, $class, \gettype($class)));
                    }

                    if (!$condition) {
                        continue;
                    }
                    $classes[] = $class;
                }
            } else {
                throw new RuntimeError(sprintf('The html_classes function argument %d should be either a string or an array, got "%s".', $i, \gettype($arg)));
            }
        }

        return implode(' ', array_unique($classes));
    }

    public function isAwHost($link, $awHost)
    {
        // If link does not contain scheme parse_url() will fail to get it's domain
        if (null === parse_url($link, PHP_URL_SCHEME)) {
            $awHostScheme = parse_url($awHost, PHP_URL_SCHEME);
            $link = "$awHostScheme://$link";
        }
        $getDomains = fn (string $link) => array_map('strtolower', array_reverse(explode('.', parse_url($link, PHP_URL_HOST))));
        $linkHostDomains = $getDomains($link);

        if (count($linkHostDomains) < 2) {
            return false;
        }
        $awHostDomains = $getDomains($awHost);

        // Check only the first 2 domains from the root so that we won't get "out policy" on our own subdomains
        return $linkHostDomains[0] === $awHostDomains[0] && $linkHostDomains[1] === $awHostDomains[1];
    }

    public function htmlPurifier($value)
    {
        return $this->purifier->reverseTransform($value);
    }

    public function getName()
    {
        return 'aw_extension';
    }

    private function htmlAttrs(array $attrs)
    {
        $formatted = '';

        foreach ($attrs as $attrName => $attrVal) {
            if (is_array($attrVal)) {
                $styles = [];

                foreach ($attrVal as $styleName => $styleVal) {
                    $styles[] = sprintf('%s:%s', $styleName, $styleVal);
                }
                $attrVal = implode(';', $styles);
            }
            $formatted .= sprintf(' %s="%s"', $attrName, $attrVal);
        }

        return $formatted;
    }
}
