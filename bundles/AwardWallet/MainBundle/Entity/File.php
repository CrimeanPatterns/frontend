<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\FileRepository")
 * @ORM\Table(name="File")
 * @ORM\HasLifecycleCallbacks
 */
class File
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $FileId;

    /**
     * @ORM\Column(type="string", length=28, nullable=false)
     */
    protected $Filename;

    /**
     * @ORM\Column(type="string", length=16, nullable=false)
     */
    protected $Resource;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $ResourceId;

    /**
     * * @ORM\Column(type="datetime")
     */
    protected $UploadDateTime;

    /**
     * @var int
     */
    protected $deletedId;

    /**
     * @var \Symfony\Component\HttpFoundation\File\File;
     */
    private $file;

    /**
     * @param \DateTime $UploadDateTime
     */
    public function setUploadDateTime($UploadDateTime)
    {
        $this->UploadDateTime = $UploadDateTime;
    }

    /**
     * @return \DateTime
     */
    public function getUploadDateTime()
    {
        return $this->UploadDateTime;
    }

    public function setResourceId($ResourceId)
    {
        $this->ResourceId = $ResourceId;
    }

    public function getResourceId()
    {
        return $this->ResourceId;
    }

    /**
     * @param int $FileId
     */
    public function setFileId($FileId)
    {
        $this->FileId = $FileId;
    }

    /**
     * @return int
     */
    public function getFileId()
    {
        return $this->FileId;
    }

    /**
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->Filename = $filename;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\File\File $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\File\File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        $id = !empty($this->getFileId()) ? $this->getFileId() : $this->deletedId;

        return $id . '-' . $this->Filename;
    }

    public function setResource($Resource)
    {
        $this->Resource = $Resource;
    }

    public function getResource()
    {
        return $this->Resource;
    }

    /**
     * @ORM\PrePersist
     */
    public function preUpload()
    {
        $this->setUploadDateTime(new \DateTime(date('Y-m-d H:i:s')));
        $this->setFilename(time() . '.' . $this->getFile()->guessExtension());
    }

    /**
     * @ORM\PostPersist
     */
    public function upload()
    {
        $this->getFile()->move($this->getBasePath(), $this->getFilename());
    }

    public function getWebPath()
    {
        return "/images/uploaded/{$this->getResource()}/{$this->getSubDir()}/{$this->getFilename()}";
    }

    /**
     * @ORM\PreRemove
     */
    public function setDeletedId()
    {
        $this->deletedId = $this->getFileId();
    }

    /**
     * @ORM\PostRemove
     */
    public function removeFile()
    {
        $basedir = $this->getBasePath();
        $file = $basedir . '/' . $this->getFilename();

        if (is_file($file)) {
            unlink($file);
        }

        if (count(scandir($basedir)) == 2) {
            rmdir($basedir);
        }
    }

    private function getBasePath()
    {
        $dir = __DIR__ . "/../../../../web/images/uploaded/{$this->getResource()}/{$this->getSubDir()}";

        if (!realpath($dir)) {
            mkdir($dir, 0777, true);
        }

        return realpath($dir);
    }

    private function getSubDir()
    {
        return sprintf("%03d", floor($this->getResourceId() / 1000));
    }
}
