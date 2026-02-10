<?php

namespace AwardWallet\MainBundle\Command\Update;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * used.
 *
 * web/assets/awardwalletnewdesign/less/base/font.less
 * web/assets/awardwalletnewdesign/css/offer-cards.css
 * web/assets/awardwalletnewdesign/css/base/api-doc.css
 *
 * Change version on import(?v=) if changes are made
 */
class UpdateCssImport extends Command
{
    protected static $defaultName = 'aw:update-css-import';

    private LoggerInterface $logger;
    private \CurlDriver $curlDriver;
    private string $rootDir;
    private OutputInterface $output;

    public function __construct(
        LoggerInterface $logger,
        \CurlDriver $curlDriver,
        string $rootDir
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->curlDriver = $curlDriver;
        $this->rootDir = $rootDir;
    }

    protected function configure()
    {
        $this->setDescription('Fetch fonts import and write to css files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        $fontsDir = $this->rootDir . '/../web/assets/common/fonts';

        $list = [
            // 'https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,400;0,600;0,700;1,400;1,700&display=swap',
            // 'https://fonts.googleapis.com/css?family=Roboto:400,400i,500,700&display=swap',
            'https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,400;0,600;0,700;1,400;1,700&display=swap',
            'https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;1,400&display=swap',
        ];

        $output->writeln('');

        foreach ($list as $link) {
            $alternative = $this->parseCssUrls($link, $fontsDir, '/assets/common/fonts/');

            $output->writeln('');
            $output->writeln(str_repeat(' ', 12) . $alternative . PHP_EOL . ' instead of ' . $link);
            $output->writeln('');
        }

        $output->writeln('done');

        return 0;
    }

    private function parseCssUrls(string $url, string $fontsDir, string $relativePath)
    {
        $parseUrl = parse_url($url);
        $cssFileName = $parseUrl['host'];

        $arg = [];
        parse_str($parseUrl['query'], $arg);

        if (empty($arg['family'])) {
            throw new \Exception('Unknown arguments');
        }

        $cssFileName .= '-' . explode(':', $arg['family'])[0];
        $cssFileName = str_replace(['.', ' '], ['-', '_'], strtolower($cssFileName));
        $cssFileName .= '.css';

        $http = new \HttpBrowser("none", $this->curlDriver);

        $this->output->writeln('GET: ' . $url);
        $http->GetURL($url);
        $css = $http->Response['body'];

        if (empty($css)) {
            throw new \Exception('Empty contnet for url: ' . $url);
        }
        $css = trim($css);

        $matches = [];
        preg_match_all('/\((.*?)\)/s', $css, $matches);

        $fontsDir = rtrim($fontsDir, '/');

        if (!empty($matches[1])) {
            foreach ($matches[1] as $url) {
                if (false === strpos($url, 'http')) {
                    continue;
                }

                $parts = parse_url($url);
                $path = $parts['path'];

                if (empty($path) || file_exists($fontsDir . '/' . $path)) {
                    $css = $this->pathReplace($css, $url, $relativePath);

                    continue;
                }

                // if (!is_dir($path)) {
                @mkdir($fontsDir . '/' . dirname($path), 0750, true);
                // }

                $this->output->writeln(' - fetch: ' . $url);
                $http->GetURL($url);
                $fileContent = $http->Response['body'];

                if (empty($fileContent)) {
                    throw new \Exception('File get content error url: ' . $url);
                }

                if (file_put_contents($fontsDir . '/' . $path, $fileContent)) {
                    $css = $this->pathReplace($css, $url, $relativePath);
                }
            }

            if (file_put_contents($fontsDir . '/' . $cssFileName, $css)) {
                $this->output->writeln(' complete: ' . $cssFileName);
            } else {
                throw new \Exception('failed to write file: ' . $fontsDir . '/' . $cssFileName);
            }
        }

        return rtrim($relativePath, '/') . '/' . $cssFileName;
    }

    private function pathReplace(string $css, string $url, string $relativePath): string
    {
        $parts = parse_url($url);
        $replace = str_replace($parts['scheme'] . '://' . $parts['host'], rtrim($relativePath, '/'), $url);

        return str_replace($url, $replace, $css);
    }
}
