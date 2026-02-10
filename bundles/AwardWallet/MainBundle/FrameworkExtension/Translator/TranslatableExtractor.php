<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Translator;

use Doctrine\Common\Annotations\DocParser;
use JMS\TranslationBundle\Annotation\Desc;
use JMS\TranslationBundle\Annotation\Ignore;
use JMS\TranslationBundle\Annotation\Meaning;
use JMS\TranslationBundle\Exception\RuntimeException;
use JMS\TranslationBundle\Logger\LoggerAwareInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\Extractor\FileVisitorInterface;
use JMS\TranslationBundle\Translation\FileSourceFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use Psr\Log\LoggerInterface;

class TranslatableExtractor implements LoggerAwareInterface, FileVisitorInterface, NodeVisitor
{
    /**
     * Methods and "domain" parameter offset to extract from PHP code.
     *
     * @var array method => position of the "domain" parameter
     */
    protected $classesToExtractFrom = [
        'trans' => 2,
        'transchoice' => 3,
    ];
    /**
     * @var FileSourceFactory
     */
    private $fileSourceFactory;

    /**
     * @var NodeTraverser
     */
    private $traverser;

    /**
     * @var MessageCatalogue
     */
    private $catalogue;

    /**
     * @var \SplFileInfo
     */
    private $file;

    /**
     * @var DocParser
     */
    private $docParser;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Node
     */
    private $previousNode;

    /**
     * DefaultPhpFileExtractor constructor.
     */
    public function __construct(DocParser $docParser, FileSourceFactory $fileSourceFactory)
    {
        $this->docParser = $docParser;
        $this->fileSourceFactory = $fileSourceFactory;
        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor($this);
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return void
     */
    public function enterNode(Node $node)
    {
        if (
            !$node instanceof Node\Expr\New_
            || !$node->class instanceof Node\Name
            || !in_array(strtolower($node->class->getLast()), array_map('strtolower', array_keys($this->classesToExtractFrom)))
        ) {
            $this->previousNode = $node;

            return;
        }

        $ignore = false;
        $desc = $meaning = null;

        if (null !== $docComment = $this->getDocCommentForNode($node)) {
            if ($docComment instanceof Doc) {
                $docComment = $docComment->getText();
            }

            foreach ($this->docParser->parse($docComment, 'file ' . $this->file . ' near line ' . $node->getLine()) as $annot) {
                if ($annot instanceof Ignore) {
                    $ignore = true;
                } elseif ($annot instanceof Desc) {
                    $desc = $annot->text;
                } elseif ($annot instanceof Meaning) {
                    $meaning = $annot->text;
                }
            }
        }

        if (!$node->args[0]->value instanceof String_) {
            $message = sprintf('Can only extract the translation id from a scalar string, but got "%s". Please refactor your code to make it extractable, or add the doc comment /** @Ignore */ to this code element (in %s on line %d).', get_class($node->args[0]->value), $this->file, $node->args[0]->value->getLine());

            if ($this->logger) {
                $this->logger->error($message);

                return;
            }

            throw new RuntimeException($message);
        }

        $id = $node->args[0]->value->value;

        $index = $this->classesToExtractFrom[strtolower($node->class)];

        if (isset($node->args[$index])) {
            if (!$node->args[$index]->value instanceof String_) {
                $message = sprintf('Can only extract the translation domain from a scalar string, but got "%s". Please refactor your code to make it extractable, or add the doc comment /** @Ignore */ to this code element (in %s on line %d).', get_class($node->args[0]->value), $this->file, $node->args[0]->value->getLine());

                if ($this->logger) {
                    $this->logger->error($message);

                    return;
                }

                throw new RuntimeException($message);
            }

            $domain = $node->args[$index]->value->value;
        } else {
            $domain = 'messages';
        }

        if ($ignore) {
            return;
        }

        $message = new Message($id, $domain);
        $message->setDesc($desc);
        $message->setMeaning($meaning);
        $message->addSource($this->fileSourceFactory->create($this->file, $node->getLine()));
        $this->catalogue->add($message);
    }

    public function visitPhpFile(\SplFileInfo $file, MessageCatalogue $catalogue, array $ast)
    {
        $this->file = $file;
        $this->catalogue = $catalogue;
        $this->traverser->traverse($ast);
    }

    /**
     * @return void
     */
    public function beforeTraverse(array $nodes)
    {
    }

    /**
     * @return void
     */
    public function leaveNode(Node $node)
    {
    }

    /**
     * @return void
     */
    public function afterTraverse(array $nodes)
    {
    }

    public function visitFile(\SplFileInfo $file, MessageCatalogue $catalogue)
    {
    }

    public function visitTwigFile(\SplFileInfo $file, MessageCatalogue $catalogue, \Twig\Node\Node $ast)
    {
    }

    /**
     * @return string|null
     */
    private function getDocCommentForNode(Node $node)
    {
        // check if there is a doc comment for the ID argument
        // new Trans(/** @Desc("FOO") */ 'my.id')
        if (null !== $comment = $node->args[0]->getDocComment()) {
            return $comment->getText();
        }

        // this may be placed somewhere up in the hierarchy,
        // /** @Desc("FOO") */ new Trans('my.id')
        // /** @Desc("FOO") */ someFoo(new Trans('my.id'))
        if (null !== $comment = $node->getDocComment()) {
            return $comment->getText();
        } elseif (null !== $this->previousNode && $this->previousNode->getDocComment() !== null) {
            $comment = $this->previousNode->getDocComment();

            return is_object($comment) ? $comment->getText() : $comment;
        }

        return null;
    }
}
