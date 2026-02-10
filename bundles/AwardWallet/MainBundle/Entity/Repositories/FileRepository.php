<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\File;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Request;

class FileRepository extends EntityRepository
{
    public function saveFile(Request $request)
    {
        $file = new File();
        $file->setResource($request->get('resource'));
        $file->setResourceId($request->get('resourceId'));
        $file->setFile($request->files->get('upload'));
        $this->_em->persist($file);
        $this->_em->flush($file);

        return [$file->getWebPath(), $file->getFilename()];
    }

    public function deleteAllFilesByResourceId($id)
    {
        $files = $this->findBy(['ResourceId' => $id]);

        foreach ($files as $file) {
            $this->_em->remove($file);
        }
        $this->_em->flush();
    }
}
