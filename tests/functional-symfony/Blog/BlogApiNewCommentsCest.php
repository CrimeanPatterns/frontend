<?php

namespace AwardWallet\Tests\FunctionalSymfony\Blog;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\Blog\BlogCommentNotification;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 * @group frontend-functional
 */
class BlogApiNewCommentsCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /** @var Router */
    private $router;

    private $comment = [
        'postTitle' => 'Title blogpost 111',
        'postLink' => 'blog-post-link',
        'postUpdate' => '2015-10-06 11:12',
        'postComments' => 10,
        'commentLink' => 'https://awardwallet.com/blog/test-url-post#comment-test',
        'commentAuthor' => 'author-0',
        'commentEmail' => 'test0@awardwallet.com',
        'commentContent' => 'Although the annual fee of this card can easily be justified due to the 300 credit and the free night, the lack of bonus categories for everyday spend is disappointing. Like another poster said, I think I’m just going to keep my SPG business card.',
        'commentDate' => '2018-02-03 12:10',
        'emails' => ['registered@fakemail.com', 'unregistered@fakemail.com'],
    ];

    private $registeredName;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->router = $I->grabService('router');
    }

    public function testNoAuth(\TestSymfonyGuy $I)
    {
        $I->sendPOST($this->router->generate('aw_blog_new_comment'));
        $I->seeResponseCodeIs(403);
    }

    public function testBadAuth(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader('Authorization', 'basic ' . base64_encode('awardwallet:somebadpass'));
        $I->sendPOST($this->router->generate('aw_blog_new_comment'));
        $I->seeResponseCodeIs(403);
    }

    public function testRegisteredUserEmail(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader('Authorization', 'basic ' . base64_encode('awardwallet:' . $I->getContainer()->getParameter('blog.api.secret')));

        $registeredEmail = 'fakeregistered' . StringUtils::getRandomCode(6) . '@fakemail.com';
        $I->createAwUser(
            null,
            null,
            [
                'Email' => $registeredEmail,
                'FirstName' => StringUtils::getRandomCode(12),
            ]);
        $this->comment['emails'][0] = $registeredEmail;
        $I->seeInDatabase('Usr', ['email' => $registeredEmail]);

        $blogCommentNotification = $I->grabService(BlogCommentNotification::class);
        $I->sendPOST($this->router->generate('aw_blog_new_comment'), $this->comment);

        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['status' => 'success']);
        $I->seeInDatabase(
            'BlogComment',
            [
                'PostTitle' => $this->comment['postTitle'],
                'PostLink' => $this->comment['postLink'],
                'CommentEmail' => $this->comment['commentEmail'],
            ]);

        $blogCommentNotification->send();

        $email = $blogCommentNotification::DEBUG ? 'test@awardwallet.com' : $this->comment['emails'][0];
        $I->seeEmailTo($email, 'new comments have been published on the AwardWallet Blog', 'fee of this card can easily be justified due');
    }

    public function testUnregisteredUserEmail(\TestSymfonyGuy $I)
    {
        $blogCommentNotification = $I->grabService(BlogCommentNotification::class);
        $blogCommentNotification->send($this->getComments());

        $email = $blogCommentNotification::DEBUG ? 'test@awardwallet.com' : 'test-2@awardwallet.com';
        $I->seeEmailTo($email, 'new comments have been published on the AwardWallet Blog', 'comment-content-blogpost-2');
    }

    private function getComments()
    {
        return [
            [
                'BlogCommentID' => 0,
                'PostLink' => 'blog-post--1',
                'PostTitle' => 'Title blogpost 111',
                'PostUpdate' => '2015-10-06 11:12',
                'CommentCount' => 10,
                'CommentLink' => 'https://awardwallet.com/blog/test-url-post#comment-test',
                'CommentAuthor' => 'author-0',
                'CommentEmail' => 'test0@awardwallet.com',
                'CommentContent' => 'Although the annual fee of this card can easily be justified due to the 300 credit and the free night, the lack of bonus categories for everyday spend is disappointing. Like another poster said, I think I’m just going to keep my SPG business card.',
                'CommentDate' => '2018-02-03 12:10',

                'Subscribers' => json_encode(['test@awardwallet.com', 'test-2@awardwallet.com']),
            ],
            [
                'BlogCommentID' => 0,
                'PostLink' => 'blog-post--2',
                'PostTitle' => 'Blogpost title 222',
                'PostUpdate' => '2016-12-32 08:40',
                'CommentCount' => 3,
                'CommentLink' => 'https://awardwallet.com/blog/test-url-post#comment-test',
                'CommentAuthor' => 'author-2',
                'CommentEmail' => 'test2@awardwallet.com',
                'CommentContent' => 'comment-content-blogpost-2 I’m pretty disappointed in the 100k sign up bonus. that’s what the $95 Marriott card was introduced at. bummer.',
                'CommentDate' => '2018-01-03 22:05',

                'Subscribers' => json_encode(['test-2@awardwallet.com']),
            ],
            [
                'BlogCommentID' => 0,
                'PostLink' => 'blog-post--1',
                'PostTitle' => 'Title blogpost 111',
                'PostUpdate' => '2015-10-06 11:12',
                'CommentCount' => 10,
                'CommentLink' => 'https://awardwallet.com/blog/test-url-post#comment-test',
                'CommentAuthor' => 'author-1',
                'CommentEmail' => 'test1@awardwallet.com',
                'CommentContent' => 'Going to be a pass for me. Have enough points to get hotels free nearly every time so a credit doesn’t have a lot of need. Most the benefits can be had in other cards with lower fee or better overall benefits. Amex Hilton Aspire is a much better high end hotel card IMO. Get top status for $0 spend.',
                'CommentDate' => '2018-06-03 09:30',

                'Subscribers' => json_encode(['test-1@awardwallet.com', 'test-2@awardwallet.com']),
            ],
            [
                'BlogCommentID' => 0,
                'PostLink' => 'blog-post--3',
                'PostTitle' => '333 blog post title',
                'PostUpdate' => '2018-12-31 00:00',
                'CommentCount' => 1,
                'CommentLink' => 'https://awardwallet.com/blog/test-url-post#comment-test',
                'CommentAuthor' => 'author-3',
                'CommentEmail' => 'test3@awardwallet.com',
                'CommentContent' => 'Perhaps worth getting for welcome bonus the first year. Not convinced it’s a keeper after that, though…',
                'CommentDate' => '2018-10-03 2:55',

                'Subscribers' => json_encode(['test-1@awardwallet.com']),
            ],
            [
                'BlogCommentID' => 0,
                'PostLink' => 'blog-post--1',
                'PostTitle' => 'Title blogpost 111',
                'PostUpdate' => '2015-10-06 11:12',
                'CommentCount' => 10,
                'CommentLink' => 'https://awardwallet.com/blog/test-url-post#comment-test',
                'CommentAuthor' => 'author-0',
                'CommentEmail' => 'test0@awardwallet.com',
                'CommentContent' => 'Although the annual fee of this card can easily be justified due to the 300 credit and the free night, the lack of bonus categories for everyday spend is disappointing. Like another poster said, I think I’m just going to keep my SPG business card.',
                'CommentDate' => '2018-02-03 12:10',

                'Subscribers' => json_encode(['test@awardwallet.com', 'test-2@awardwallet.com']),
            ],
        ];
    }
}
