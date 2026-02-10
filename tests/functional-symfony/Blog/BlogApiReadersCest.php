<?php

namespace AwardWallet\Tests\FunctionalSymfony\Blog;

use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 * @group frontend-functional
 */
class BlogApiReadersCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /** @var Router */
    private $router;

    /** @var Usr */
    private $user;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->router = $I->grabService('router');
        $this->user = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
        $I->sendGET($this->router->generate('aw_page_index', ['page' => 'about', '_switch_user' => $this->user->getLogin()]));
        $I->saveCsrfToken();
    }

    public function saveTime(\TestSymfonyGuy $I)
    {
        $I->sendPOST($this->router->generate('aw_blog_reader'), [
            'report' => [
                $this->user->getRefcode() => [
                    '1' => ['i' . (time() - 86400), 'o' . (time() - 86000)],
                ],
            ],
        ]);

        $I->seeInDatabase('BlogUserReport', [
            'UserID' => $this->user->getId(),
        ]);
    }

    public function checkOutGreaterInTime(\TestSymfonyGuy $I)
    {
        $I->sendPOST($this->router->generate('aw_blog_reader'), [
            'report' => [
                $this->user->getRefcode() => [
                    0 => ['i' . time(), 'o' . (time() - 1)],
                ],
            ],
        ]);

        $I->dontSeeInDatabase('BlogUserReport', [
            'UserID' => $this->user->getId(),
        ]);
    }

    public function checkNone(\TestSymfonyGuy $I)
    {
        $I->sendPOST($this->router->generate('aw_blog_reader'), [
            'report' => [
                'none' => [
                    0 => ['i' . (time() - 1), 'o' . time()],
                ],
            ],
        ]);

        $I->seeInDatabase('BlogUserReport', [
            'UserID' => $this->user->getId(),
        ]);
    }

    public function checkAnotherData(\TestSymfonyGuy $I)
    {
        $I->sendPOST($this->router->generate('aw_blog_reader'), [
            'report' => [
                'another' => [
                    0 => ['i' . (time() - 1), 'o' . time()],
                ],
            ],
        ]);

        $I->dontSeeInDatabase('BlogUserReport', [
            'UserID' => $this->user->getId(),
        ]);
    }

    public function checkDuplicateByNone(\TestSymfonyGuy $I)
    {
        $I->sendPOST($this->router->generate('aw_blog_reader'), [
            'report' => [
                'none' => [
                    0 => ['i' . (time() - 1), 'o' . time()],
                ],
                $this->user->getRefcode() => [
                    0 => ['i' . (time() - 1), 'o' . time()],
                ],
            ],
        ]);

        $I->seeInDatabase('BlogUserReport', [
            'UserID' => $this->user->getId(),
        ]);

        $I->assertEquals(1, $I->grabCountFromDatabase('BlogUserReport', ['UserID' => $this->user->getId()]));
    }

    public function checkDuplicateByKey(\TestSymfonyGuy $I)
    {
        $I->sendPOST($this->router->generate('aw_blog_reader'), [
            'report' => [
                $this->user->getRefcode() => [
                    0 => ['i' . (time() - 1), 'o' . time()],
                    1 => ['i' . (time() - 1), 'o' . time()],
                    10 => ['i' . (time() - 1), 'o' . time()],
                    20 => ['i' . (time() - 1), 'o' . time()],
                ],
            ],
        ]);

        $I->seeInDatabase('BlogUserReport', [
            'UserID' => $this->user->getId(),
        ]);

        $I->assertEquals(1, $I->grabCountFromDatabase('BlogUserReport', ['UserID' => $this->user->getId()]));
    }
}
