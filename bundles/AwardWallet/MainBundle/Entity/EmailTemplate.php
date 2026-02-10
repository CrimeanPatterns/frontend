<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * EmailTemplate.
 *
 * @ORM\Table(name="EmailTemplate")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(fields={"code"}, errorPath="code")
 */
class EmailTemplate
{
    public const RENDER_REPLACE = 1;
    public const RENDER_TWIG = 2;

    public const TYPE_OFFER = 1;
    public const TYPE_PRODUCT_UPDATE = 2;
    public const TYPE_OTHER = 3;

    // Filename Layout/Offer/blog_newsletter.twig
    public const LAYOUT_BLOGNEWSLETTER_FILENAME = 'blog_newsletter';

    /**
     * @var int
     * @ORM\Column(name="EmailTemplateID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $emailTemplateID;

    /**
     * @var string
     * @ORM\Column(name="Code", type="string", length=250, nullable=false, unique=true)
     * @Assert\NotBlank()
     * @Assert\Length(min = "3", max = "250", allowEmptyString="true")
     * @Assert\Regex("/^[a-z_0-9]+$/")
     */
    protected $code;

    /**
     * @var string
     * @ORM\Column(name="DataProvider", type="string", length=200, nullable=false)
     * @Assert\NotBlank()
     */
    protected $dataProvider;

    /**
     * @var string
     * @ORM\Column(name="Subject", type="string", length=250, nullable=true)
     * @Assert\Length(min = "3", max = "250", allowEmptyString="true")
     */
    protected $subject;

    /**
     * @var string
     * @ORM\Column(name="Logo", type="string", length=60000, nullable=true)
     * @Assert\Length(max = "60000")
     */
    protected $logo;

    /**
     * @var string
     * @ORM\Column(name="Preview", type="string", length=65536, nullable=true)
     * @Assert\Length(max = "65536")
     */
    protected $preview;

    /**
     * @var string
     * @ORM\Column(name="Body", type="string", length=60000, nullable=true)
     * @Assert\Length(max = "60000")
     */
    protected $body;

    /**
     * @var string
     * @ORM\Column(name="Head", type="string", length=60000, nullable=true)
     * @Assert\Length(max = "60000")
     */
    protected $head;

    /**
     * @var string
     * @ORM\Column(name="Style", type="string", length=60000, nullable=true)
     * @Assert\Length(max = "60000")
     */
    protected $style;

    /**
     * @var string
     * @ORM\Column(name="Layout", type="string", length=250, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min = "3", max = "250", allowEmptyString="true")
     */
    protected $layout = 'base';

    /**
     * @var \DateTime
     * @ORM\Column(name="CreateDate", type="datetime", nullable=false)
     */
    protected $createDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="UpdateDate", type="datetime", nullable=false)
     */
    protected $updateDate;

    /**
     * @var int
     * @ORM\Column(name="RenderEngine", type="integer", nullable=false)
     * @Assert\NotBlank()
     * @Assert\Choice({1, 2})
     */
    protected $renderEngine = self::RENDER_REPLACE;

    /**
     * @var int
     * @ORM\Column(name="Type", type="integer", nullable=false)
     * @Assert\NotBlank()
     * @Assert\Choice(callback = "getEmailTypes")
     */
    protected $type = self::TYPE_OFFER;

    /**
     * @var bool
     * @ORM\Column(name="Enabled", type="boolean", nullable=false)
     */
    protected $enabled = false;

    /**
     * @var string
     * @ORM\Column(name="ListBlogPostID", type="string", length=255, nullable=true)
     * @Assert\Length(max="255")
     */
    private $listBlogPostID;

    /**
     * @var string
     * @ORM\Column(name="CID", type="string", length=64, nullable=true)
     * @Assert\Length(max="64")
     */
    private $cid;

    /**
     * @var string
     * @ORM\Column(name="MID", type="string", length=64, nullable=true)
     * @Assert\Length(max="64")
     */
    private $mid;

    /**
     * @ORM\Column(name="Exclusions", type="json", nullable=true)
     */
    private $exclusions;

    /**
     * @ORM\Column(name="ExcludedCreditCards", type="json_array", nullable=true)
     */
    private array $excludedCreditCards;

    public function __construct()
    {
        $this->createDate = new \DateTime();
        $this->exclusions = [];
        $this->excludedCreditCards = [];
    }

    public function __toString()
    {
        if (empty($this->getId())) {
            return "New Template";
        } else {
            return sprintf("#%d (%s)", $this->getId(), $this->getCode());
        }
    }

    /**
     * @return list<int>
     */
    public function getExcludedCreditCards(): array
    {
        return $this->excludedCreditCards;
    }

    /**
     * @param list<int> $excludedCreditCards
     */
    public function setExcludedCreditCards(array $excludedCreditCards): self
    {
        $this->excludedCreditCards = $excludedCreditCards;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->emailTemplateID;
    }

    /**
     * @return int
     */
    public function getEmailTemplateID()
    {
        return $this->getId();
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    public function getDashboardLink(): string
    {
        return "https://kibana.awardwallet.com/app/dashboards#/view/57f2a550-a1af-11ea-8601-8313683eea38?_g=" . urlencode("(filters:!(),refreshInterval:(pause:!t,value:0),time:(from:'" . $this->createDate->format("Y-m-d") . "T00:00:00.000Z',to:now))") . "&_a=" . urlencode("(description:'',filters:!(),fullScreenMode:!f,options:(darkTheme:!f,hidePanelTitles:!f,useMargins:!t),query:(language:lucene,query:'{$this->getCode()}-{$this->getDataProvider()}'),timeRestore:!t,title:'Email%20Offer%20Stats',viewMode:view)");
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getDataProvider()
    {
        return $this->dataProvider;
    }

    /**
     * @param string $dataProvider
     * @return EmailTemplate
     */
    public function setDataProvider($dataProvider)
    {
        $this->dataProvider = $dataProvider;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * @param string $logo
     * @return EmailTemplate
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;

        return $this;
    }

    public function getPreview(): ?string
    {
        return $this->preview;
    }

    public function setPreview(?string $preview): EmailTemplate
    {
        $this->preview = $preview;

        return $this;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @return string
     */
    public function getHead()
    {
        return $this->head;
    }

    /**
     * @param string $head
     */
    public function setHead($head)
    {
        $this->head = $head;

        return $this;
    }

    /**
     * @return string
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * @param string $style
     */
    public function setStyle($style)
    {
        $this->style = $style;

        return $this;
    }

    /**
     * @return string
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @param string $layout
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->createDate;
    }

    /**
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->updateDate;
    }

    /**
     * @return int
     */
    public function getRenderEngine()
    {
        return $this->renderEngine;
    }

    /**
     * @param int $renderEngine
     */
    public function setRenderEngine($renderEngine)
    {
        $this->renderEngine = $renderEngine;

        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return EmailTemplate
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return $this
     */
    public function setListBlogPostID(?string $blogPostIds): self
    {
        $this->listBlogPostID = $blogPostIds;

        return $this;
    }

    /**
     * @return array|string|null
     */
    public function getListBlogPostID(): ?string
    {
        return $this->listBlogPostID;
    }

    /**
     * @return $this
     */
    public function setCID(?string $cid): self
    {
        $this->cid = $cid;

        return $this;
    }

    public function getCID(): ?string
    {
        return $this->cid;
    }

    /**
     * @return $this
     */
    public function setMID(?string $mid): self
    {
        $this->mid = $mid;

        return $this;
    }

    public function getMID(): ?string
    {
        return $this->mid;
    }

    public function getExclusions(): ?array
    {
        return $this->exclusions;
    }

    public function setExclusions(?array $exclusions): self
    {
        $this->exclusions = $exclusions;

        return $this;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function prePersist()
    {
        $this->updateDate = new \DateTime();
    }

    public static function getEmailTypes()
    {
        return [
            "Offer" => self::TYPE_OFFER,
            "Product Update" => self::TYPE_PRODUCT_UPDATE,
            "All Users" => self::TYPE_OTHER,
        ];
    }
}
