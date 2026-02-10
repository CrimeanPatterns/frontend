<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\DocParser;
use JMS\TranslationBundle\Annotation\Desc;
use JMS\TranslationBundle\Annotation\Ignore;
use JMS\TranslationBundle\Logger\LoggerAwareInterface;
use JMS\TranslationBundle\Model\FileSource;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\Extractor\FileVisitorInterface;
use PSR\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use Twig\Node\Node;

class JsTranslationExtractor implements FileVisitorInterface, LoggerAwareInterface
{
    /**
     * @var resource
     */
    private $stream;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DocParser
     */
    private $docParser;

    /**
     * @var Process
     */
    private $process;

    public function __construct(DocParser $docParser)
    {
        $this->docParser = $docParser;
    }

    public function __destruct()
    {
        if (isset($this->process) && $this->process->isRunning()) {
            $this->process->signal(9);
        }
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function visitFile(\SplFileInfo $file, MessageCatalogue $catalogue)
    {
        if ($file->getExtension() != 'js') {
            return;
        }

        $content = file_get_contents($file);

        if (false === $content) {
            throw new \RuntimeException('Can not read file ' . $file);
        }

        $ch = curl_init('127.0.0.1:31337');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($content),
        ]);

        $response = false;

        foreach (range(1, 3) as $try) {
            $this->checkNodeServer();
            $response = curl_exec($ch);

            if ('' === $response || false === $response) {
                $this->logger->warning("Retrying {$try}...");

                continue;
            } else {
                break;
            }
        }

        if ('' === $response || false === $response) {
            throw new \RuntimeException('Empty response from node. File: ' . $file);
        }

        $jsonResponse = @json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Malformed parser response. File: ' . $file);
        }

        if (isset($jsonResponse['babelerror'])) {
            throw new \RuntimeException('Babel parser returns error: "' . $jsonResponse['babelerror'] . '". File: ' . $file);
        }

        if (isset($jsonResponse['jserror'])) {
            throw new \RuntimeException('Javascript parser returns error: "' . $jsonResponse['jserror'] . '". File: ' . $file);
        }

        if (isset($jsonResponse['xmlerror'])) {
            throw new \RuntimeException('XML converter returns error: "' . $jsonResponse['xmlerror'] . '". File: ' . $file);
        }

        try {
            $xml = new \SimpleXMLElement($jsonResponse['xml']);
        } catch (\Exception $e) {
            throw new \RuntimeException('Malformed xml. File: ' . $file . '. "' . $e->getMessage() . '"');
        }

        $callees = $xml->xpath("
            //*[type = 'CallExpression']
                [
                    callee[
                        property[type = 'Identifier' and
                        (name = 'trans' or name = 'transChoice')
                    ] and
                    following-sibling::arguments]
                ]");

        foreach ($callees as $calleeNode) {
            $docBlockNode = $calleeNode->xpath('(./arguments[1]/leadingComments[type = "Block"][last()]/value | ./leadingComments[type = "Block"][last()]/value)[1]'); // closest doc block wins;
            // parse annotations
            $description = null;

            if ($docBlockNode) {
                $docBlock = (string) $docBlockNode[0];

                if ('' !== $docBlock) {
                    try {
                        $parsedAnnotations = $this->docParser->parse('/*' . $docBlock . '*/');
                    } catch (AnnotationException $e) {
                        $this->logger->error(sprintf('Annotation error "%s". File %s, %s', $e->getMessage(), $file, $this->getNodeLocation($docBlockNode[0]->xpath('./parent::*[1]')[0])));

                        continue;
                    }

                    foreach ($parsedAnnotations as $parsedAnnotation) {
                        if ($parsedAnnotation instanceof Ignore) {
                            continue 2;
                        } elseif ($parsedAnnotation instanceof Desc) {
                            $description = $parsedAnnotation->text;
                        }
                    }
                }
            }
            $argumentsNodes = $calleeNode->xpath('./arguments');

            if (!$argumentsNodes) {
                continue;
            }

            $transMap = ['id' => 0, 'domain' => 2, 'locale' => 3];

            if ('transChoice' === (string) $calleeNode->xpath('./callee/property/name')[0]) {
                $transMap = ['id' => 0, 'domain' => 3, 'locale' => 4];
            }

            foreach ($transMap as $argumentName => $argumentNumber) {
                if (!isset($argumentsNodes[$argumentNumber])) {
                    unset($transMap[$argumentName]);

                    continue;
                }

                $arugmentNode = $argumentsNodes[$argumentNumber];

                if (!$arugmentNode->xpath('./type[. = "Literal"]')) {
                    $this->logger->error(sprintf(
                        'Can only extract the translation %s from a scalar string but got "%s". Please refactor your code to make it extractable, or add the doc comment /** @Ignore */ to this code element. File %s, %s',
                        $argumentName, $arugmentNode->xpath('./type')[0], $file, $this->getNodeLocation($arugmentNode)));

                    continue 2;
                }
                $transMap[$argumentName] = (string) $arugmentNode->xpath('./value')[0];
            }

            $message = new Message($transMap['id'], $transMap['domain'] ?? 'messages');

            if (isset($transMap['locale'])) {
                $message->setLocaleString($transMap['locale']);
            }

            if (isset($description)) {
                $message->setDesc($description);
            }
            $message->addSource(new FileSource((string) $file));
            $catalogue->add($message);
        }
    }

    public function visitPhpFile(\SplFileInfo $file, MessageCatalogue $catalogue, array $ast)
    {
    }

    public function visitTwigFile(\SplFileInfo $file, MessageCatalogue $catalogue, Node $node)
    {
    }

    protected function checkNodeServer()
    {
        if (!isset($this->process)) {
            $this->logger->error("Starting node process...");
            $this->process = new Process('node app/Resources/js/translations-extractor.js');
            $this->process->setTimeout(1800);
            $this->process->start();
            sleep(3);
        } elseif (!$this->process->isRunning()) {
            $this->logger->warning("Node is not running. Process output:" . $this->process->getErrorOutput() . "\n\nRestarting...");
            $this->process = $this->process->restart();
            sleep(3);
        }
    }

    protected function getNodeLocation(\SimpleXMLElement $node)
    {
        return "line " . $node->xpath('./loc/start/line')[0] . ', column ' . $node->xpath('./loc/start/column')[0];
    }
}
