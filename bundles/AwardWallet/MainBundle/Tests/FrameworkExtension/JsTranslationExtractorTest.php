<?php

namespace AwardWallet\MainBundle\Tests\FrameworkExtension;

use AwardWallet\MainBundle\FrameworkExtension\JsTranslationExtractor;
use JMS\TranslationBundle\Model\FileSource;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\MessageCollection;
use JMS\TranslationBundle\Translation\Extractor\FileExtractor;
use JMS\TranslationBundle\Twig\TranslationExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension as SymfonyTranslationExtension;
use Symfony\Component\HttpKernel\Log\NullLogger;
use Symfony\Component\Translation\IdentityTranslator;

class JsTranslationExtractorTest extends \PHPUnit_Framework_TestCase
{
    public function testExtract()
    {
        $expected = [];
        $basePath = __DIR__ . '/Fixture/';

        $collection = new MessageCollection();
        $message = new Message('hello.world', 'booking');
        $message->addSource(new FileSource($basePath . 'extractTransFromMe.js'));
        $collection->add($message);
        $message = new Message('example.test2', 'booking');
        $message->setDesc('this is a test');
        $message->setMeaning('Bla bla bla');
        $message->addSource(new FileSource($basePath . 'extractTransFromMe.js'));
        $collection->add($message);
        $expected['booking'] = $collection;

        $collection = new MessageCollection();
        $message = new Message('example.number.apples');
        $message->setDesc('{1} apple|]1,Inf] apples');
        $message->addSource(new FileSource($basePath . 'extractTransFromMe.js'));
        $collection->add($message);
        $message = new Message('example.test');
        $message->setLocaleString('fr');
        $message->setMeaning('Bla bla bla');
        $message->addSource(new FileSource($basePath . 'extractTransFromMe.js'));
        $collection->add($message);
        $message = new Message('example.test3');
        $message->addSource(new FileSource($basePath . 'test.js'));
        $collection->add($message);
        $message = new Message('hello.world2');
        $message->addSource(new FileSource($basePath . 'extractTransFromMe.js'));
        $collection->add($message);
        $message = new Message('hello.world3');
        $message->addSource(new FileSource($basePath . 'extractTransFromMe.js'));
        $collection->add($message);
        $expected['messages'] = $collection;

        $collection = new MessageCollection();
        $message = new Message('hello.world4', 'my');
        $message->setLocaleString('fr');
        $message->setMeaning('Bla bla bla');
        $message->addSource(new FileSource($basePath . 'extractTransFromMe.js'));
        $collection->add($message);
        $expected['my'] = $collection;

        $actual = $this->extract($basePath)->getDomains();
        asort($expected);
        asort($actual);

        $this->assertEquals($expected, $actual);
    }

    private function extract($directory)
    {
        $twig = new \Twig_Environment();
        $twig->addExtension(new SymfonyTranslationExtension($translator = new IdentityTranslator()));
        $twig->addExtension(new TranslationExtension($translator));
        $loader = new \Twig_Loader_Filesystem(realpath($directory));
        $twig->setLoader($loader);

        $extractor = new FileExtractor($twig, new NullLogger(), [
            new JsTranslationExtractor(),
        ]);
        $extractor->setDirectory($directory);

        return $extractor->extract();
    }
}
