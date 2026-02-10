<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate;

use AwardWallet\MainBundle\Entity\EmailTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\DataProviderAbstract as BaseDataProvider;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Offer\EmailOffer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Offer\EmailOfferBlogNewsletter;
use AwardWallet\MainBundle\Service\Blog\BlogPost;
use AwardWallet\MainBundle\Service\EmailTemplate\DataProvider\Group;
use AwardWallet\MainBundle\Service\EmailTemplate\Event\Events;
use AwardWallet\MainBundle\Service\EmailTemplate\Event\ProgressEvent;
use AwardWallet\MainBundle\Service\EmailTemplate\Event\SendEvent;
use AwardWallet\MainBundle\Service\EmailTemplate\Event\UsersFoundEvent;
use AwardWallet\MainBundle\Service\EmailTemplate\RenderEngine\EngineInterface;
use AwardWallet\MainBundle\Service\EmailTemplate\RenderEngine\ReplaceEngine;
use AwardWallet\MainBundle\Service\EmailTemplate\RenderEngine\TwigEngine;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @psalm-type Fields = array{
 *   UserID: string,
 *   FirstName: string,
 *   LastName: string,
 *   Email: string,
 *   Login: string,
 *   RegistrationIP: string,
 *   LastLogonIP: string,
 *   RefCode: string,
 *   Zip: string,
 *   ...
 * }
 */
abstract class AbstractDataProvider extends BaseDataProvider implements \IteratorAggregate
{
    protected ContainerInterface $container;

    protected EmailTemplate $emailTemplate;

    protected ?EngineInterface $renderEngine;

    protected EventDispatcher $dispatcher;

    protected QueryBuilder $queryBuilder;

    /**
     * @var Options[]
     */
    protected array $queryOptions;

    /**
     * @var bool
     */
    protected $forceTransactional;

    /**
     * @var Query
     */
    protected $query;

    /**
     * @var Fields
     */
    protected $fields = [];

    protected $current = 0;

    protected $blogposts = [];

    public function __construct(ContainerInterface $container, EmailTemplate $template)
    {
        $this->container = $container;
        $this->emailTemplate = $template;

        switch ($template->getRenderEngine()) {
            case EmailTemplate::RENDER_REPLACE:
                $this->renderEngine = $container->get(ReplaceEngine::class);

                break;

            case EmailTemplate::RENDER_TWIG:
                $this->renderEngine = $container->get(TwigEngine::class);

                break;
        }
        $this->queryBuilder = $container->get(QueryBuilder::class);
        $qOptions = new Options();
        $qOptions->messageId = $template->getEmailTemplateID();
        $qOptions->emailType = $template->getType();
        $this->queryOptions = [$qOptions];
        $this->dispatcher = new EventDispatcher();
        $this->options[Mailer::OPTION_FIX_BODY] = false;

        if (EmailTemplate::LAYOUT_BLOGNEWSLETTER_FILENAME === $template->getLayout() && !empty($template->getListBlogPostID())) {
            $this->blogposts = $container->get(BlogPost::class)->fetchPostById($template->getListBlogPostID(), true);

            if (!empty($this->blogposts)) {
                if (empty($template->getCID())) {
                    throw new \Exception('You must set the "CID" field value for links');
                }

                if (empty($template->getMID())) {
                    throw new \Exception('You must set the "MID" field value for links');
                }
            }
        }
    }

    public function __init(bool $handleCount = true)
    {
        $this->query = $this->getQuery();

        if ($handleCount) {
            $this->dispatcher->dispatch(new UsersFoundEvent($this->query->getCount()), Events::EVENT_USERS_FOUND);

            if ($this->query->getCount() == 0) {
                return;
            }
        }
    }

    /**
     * @return bool
     */
    public function isForceTransactional()
    {
        return $this->forceTransactional;
    }

    /**
     * @param bool $forceTransactional
     * @return AbstractDataProvider
     */
    public function setForceTransactional($forceTransactional)
    {
        $this->forceTransactional = $forceTransactional;

        return $this;
    }

    /**
     * @return Options[]
     */
    public function getQueryOptions()
    {
        return $this->queryOptions;
    }

    /**
     * @param Options[] $queryOptions
     */
    public function setQueryOptions(array $queryOptions)
    {
        $this->queryOptions = $queryOptions;

        return $this;
    }

    public function getDataReplacements()
    {
        $fields = $this->getQuery()->getFields();

        return $this->generateDataReplacements($fields);
    }

    public function getTestInfo()
    {
        return null;
    }

    /**
     * @return int[] array of new users ids
     */
    public function addFixtures()
    {
        return [];
    }

    public function deleteFixtures()
    {
    }

    /**
     * @return Query
     */
    public function getQuery()
    {
        return $this->queryBuilder->getQuery($this->getQueryOptions());
    }

    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @return EmailTemplate
     */
    public function getEmailTemplate()
    {
        return $this->emailTemplate;
    }

    public function next(/** bool $handleCount = true */)
    {
        $handleCount = true;

        if (\func_num_args() > 0) {
            $handleCount = \func_get_arg(0);
        }

        if (empty($this->query)) {
            $this->__init($handleCount);
        }

        set_time_limit(120);
        $this->fields = $this->query->getStatement()->fetch(\PDO::FETCH_ASSOC);
        $this->dispatcher->dispatch(new ProgressEvent($this->current++, $handleCount ? $this->query->getCount() : -1), Events::EVENT_PROGRESS);

        return $this->fields !== false;
    }

    public function canSendEmail()
    {
        return true;
    }

    public function getMessage(Mailer $mailer)
    {
        $isBusiness = $this->fields['isBusiness'] == 1;

        if (EmailTemplate::LAYOUT_BLOGNEWSLETTER_FILENAME === $this->emailTemplate->getLayout()) {
            $template = new EmailOfferBlogNewsletter($this->fields['Email'], $isBusiness);

            if (!empty($this->blogposts)) {
                $template->blogpostsNewsletter = $this->renderEngine->render($template->generateBlogPostsNewsletter(
                    $template->sortPosts($this->emailTemplate->getListBlogPostID(), $this->blogposts),
                    [
                        'cid' => $this->emailTemplate->getCID(),
                        'mid' => $this->emailTemplate->getMID(),
                    ]),
                    $this->fields
                );
            }
        } else {
            $template = new EmailOffer($this->fields['Email'], $isBusiness);
        }

        $this->renderTemplateParts($template);
        $template->body = $this->setDefaultStyles($template->body);

        $message = $mailer->getMessageByTemplate($template);
        $mailer->addKindHeader($this->emailTemplate->getCode() . '-' . DataProviderLoader::getCodeByClass(\get_class($this)), $message);

        return $message;
    }

    public function preSend(Mailer $mailer, \Swift_Message $message, &$options, bool $dryRun = false)
    {
        parent::preSend($mailer, $message, $options, $dryRun);
        $this->dispatcher->dispatch(new SendEvent($mailer, $message, false, $dryRun), Events::EVENT_PRE_SEND);
    }

    public function postSend(Mailer $mailer, \Swift_Message $message, $options, $sendResult, bool $dryRun = false)
    {
        parent::postSend($mailer, $message, $options, $sendResult, $dryRun);
        $this->dispatcher->dispatch(new SendEvent($mailer, $message, $sendResult, $dryRun), Events::EVENT_POST_SEND);
    }

    public function getDescription(): string
    {
        return DataProviderLoader::getCodeByClass(static::class) . ' !!! NO DESCRIPTION !!! DESCRIBE ME !!!';
    }

    public function getTitle(): string
    {
        return DataProviderLoader::getCodeByClass(static::class) . ' !!! NO TITLE !!! TITLE ME !!!';
    }

    public function getSortPriority(): int
    {
        return 0;
    }

    public function getGroup(): string
    {
        return Group::GENERAL;
    }

    public function canBeExcludedInAdminInterface(): bool
    {
        return true;
    }

    public function isDeprecated(): bool
    {
        return false;
    }

    /**
     * @return \Traversable<Fields>
     */
    public function getIterator()
    {
        return (function () {
            while ($this->next(false)) {
                yield $this->fields;
            }
        })();
    }

    protected function generateDataReplacements(array $fields): ?string
    {
        if (sizeof($fields) > 0) {
            $result = '<table class="table table-striped"><thead><tr><th>Replacement</th><th>Description</th></tr></thead><tbody>';

            foreach ($fields as $code => $desc) {
                $result .= "<tr><td>{{ {$code} }}</td><td>{$desc}</td></tr>";
            }
            $result .= "</tbody></table>";

            return $result;
        }

        return null;
    }

    protected function renderTemplateParts(AbstractTemplate $template): void
    {
        $template->layout = $this->emailTemplate->getLayout();
        $template->subject = $this->renderEngine->render($this->emailTemplate->getSubject(), $this->fields);
        $template->logo = $this->renderEngine->render($this->emailTemplate->getLogo(), $this->fields);
        $template->preview = $this->renderEngine->render($this->emailTemplate->getPreview(), $this->fields);
        $template->body = $this->renderEngine->render($this->emailTemplate->getBody(), $this->fields);
        $template->head = $this->renderEngine->render($this->emailTemplate->getHead(), $this->fields);
        $template->style = $this->renderEngine->render($this->emailTemplate->getStyle(), $this->fields);
    }

    private function setDefaultStyles(?string $message): ?string
    {
        if (empty($message)) {
            return $message;
        }

        $xml = new \DOMDocument();
        $xmlUtf8 = '<?xml encoding="UTF-8">';
        $xml->loadHTML($xmlUtf8 . $message, LIBXML_NOERROR | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $setStyleIfNotDefined = [
            'color' => '#4684c4',
            'font-weight' => 'bold',
            'text-decoration' => 'none',
        ];

        foreach ($xml->getElementsByTagName('a') as $link) {
            $style = $link->getAttribute('style');
            $isTitle = false !== strpos($style, 'line-height');

            foreach ($setStyleIfNotDefined as $attr => $value) {
                if ($isTitle && 'font-weight' === $attr) {
                    continue;
                }

                if (false === stripos($style, $attr)) {
                    $style .= ";{$attr}:{$value}";
                }
            }

            $link->setAttribute('style', $style);
        }

        return $xml->saveHTML();
    }
}
