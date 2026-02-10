<?php

namespace AwardWallet\Tests\Unit;

/**
 * Class AngularSymfonyTranslationExtractorTest.
 *
 * @covers \AwardWallet\MainBundle\FrameworkExtension\AngularSymfonyTranslationExtractor::parsePlaceholders
 * @group frontend-unit
 */
class AngularSymfonyTranslationExtractorTest extends BaseTest
{
    /**
     * @var \AwardWallet\MainBundle\FrameworkExtension\AngularSymfonyTranslationExtractor
     */
    private $extractor;

    public function _before()
    {
        /** @var Symfony $symfony2 */
        $symfony2 = $this->getModule('Symfony');
        $container = $symfony2->_getContainer();
        $this->extractor = $container->get('aw.extension.translation.angular_extractor');
        $this->assertNotEmpty($this->extractor, 'extractor is not available');
    }

    /**
     * @dataProvider transFilterProvider
     */
    public function testTransFilter($text, $expect)
    {
        $this->assertEquals($expect, $this->extractor->parsePlaceholders($text));
    }

    public function transFilterProvider()
    {
        return $this->_createGenerator([
            "<p ng-if=\"tripsCount<1\" ng-blahblah-html=\"{{'somekey'|somefilter}}\" ng-bind-html=\"{{'trips.list.no-trips.messages.one'|trans:{}:'mobile'|desc:'In this version of the mobile app the only way to add trips is to retrieve them automatically by <a href=\'%adding%\'>ADDING</a> or <a href=\'%updating%\'>UPDATING</a> which have travel reservations.'}}\"></p>" => [
                [
                    'key' => 'trips.list.no-trips.messages.one',
                    'domain' => 'mobile',
                    'desc' => "In this version of the mobile app the only way to add trips is to retrieve them automatically by <a href='%adding%'>ADDING</a> or <a href='%updating%'>UPDATING</a> which have travel reservations.",
                    'line' => 1,
                    'column' => 83,
                ],
            ],
            "{{'accounts.n'|transChoice:view.updated:{accounts:view.updated}}}" => [
                [
                    'key' => 'accounts.n',
                    'domain' => 'messages',
                    'line' => 1,
                    'column' => 1,
                ],
            ],
            "<h1>{{'menu.about-us'|trans:{}:'menu'}}</h1>" => [
                [
                    'key' => 'menu.about-us',
                    'domain' => 'menu',
                    'line' => 1,
                    'column' => 5,
                ],
            ],
            "{{'pimp.my.key'|trans}}" => [
                [
                    'key' => 'pimp.my.key',
                    'domain' => 'messages',
                    'line' => 1,
                    'column' => 1,
                ],
            ],
            "{{'p\\'imp.my.key'|trans}}" => [
                [
                    'key' => "p'imp.my.key",
                    'domain' => 'messages',
                    'line' => 1,
                    'column' => 1,
                ],
            ],
            "{{'pimp.my.key'|trans:{}:'cooldomain'}}" => [
                [
                    'key' => "pimp.my.key",
                    'domain' => 'cooldomain',
                    'line' => 1,
                    'column' => 1,
                ],
            ],
            "{{'pimp.my.key'|trans:{}:'cooldomain'|desc:'My cool description with qoute\\''}}" => [
                [
                    'key' => "pimp.my.key",
                    'domain' => 'cooldomain',
                    'line' => 1,
                    'desc' => "My cool description with qoute'",
                    'column' => 1,
                ],
            ],
            "{{'pimp.my.key'|trans:{'tag_open': '<a>'}:'cooldomain'|desc:'My cool description with qoute\\''}}" => [
                [
                    'key' => "pimp.my.key",
                    'domain' => 'cooldomain',
                    'line' => 1,
                    'desc' => "My cool description with qoute'",
                    'column' => 1,
                ],
            ],
            "{{'pimp.my.key'|trans:{'tag_open': '<a>'}:'cooldomain'|desc:'My cool description with qoute\\''}}<somehtmlcode>Blah blah double curly brackets {{}}</somehtmlcode>
                 {{'second.cool.key'|trans:{'tag_open': '<a>'}:'cooldomain2'|desc:'My cool des\\'cription with two qoutes\\''}}" => [
                [
                    'key' => "pimp.my.key",
                    'domain' => 'cooldomain',
                    'line' => 1,
                    'desc' => "My cool description with qoute'",
                    'column' => 1,
                ],
                [
                    'key' => 'second.cool.key',
                    'domain' => 'cooldomain2',
                    'line' => 2,
                    'desc' => "My cool des'cription with two qoutes'",
                    'column' => 18,
                ],
            ],
            "<p>{{'account.update.updating-text'|transChoice:view.total:{total:view.total, updated:view.updated}:'mobile'|desc:'{1}Updating %updated% of %total% account|]1,Inf[Updating %updated% of %total% accounts'}}</p>" => [
                [
                    'key' => 'account.update.updating-text',
                    'domain' => 'mobile',
                    'desc' => '{1}Updating %updated% of %total% account|]1,Inf[Updating %updated% of %total% accounts',
                    'line' => 1,
                    'column' => 4,
                ],
            ],
            "{{::'award.account.list.totals-multiple'|trans:{users:users.length}:'messages'|desc:'Multiple Users (%users%)'}}" => [
                [
                    'key' => 'award.account.list.totals-multiple',
                    'domain' => 'messages',
                    'desc' => 'Multiple Users (%users%)',
                    'line' => 1,
                    'column' => 1,
                ],
            ],
        ]);
    }

    protected function _after()
    {
        $this->extractor = null;

        parent::_after(); // TODO: Change the autogenerated stub
    }

    protected function _createGenerator(array $data)
    {
        foreach ($data as $pattern => $expected) {
            yield [$pattern, $expected];
        }
    }
}
