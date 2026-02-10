<?php

namespace AwardWallet\Tests\Unit\MainBundle\Updater;

use AwardWallet\MainBundle\Updater\RequestSerializer;
use AwardWallet\Tests\Unit\BaseTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Updater\RequestSerializer
 */
class RequestSerializerTest extends BaseTest
{
    private const SIMPLE_SERIALIZED_REQUEST =
        '{"query":{"a":1},"request":{"b":1},"attributes":{"c":1},"cookies":{"d":1},"server":{"e":1}}';
    /**
     * @var RequestSerializer
     */
    private $serializer;

    public function _before()
    {
        parent::_before();

        $this->serializer = new RequestSerializer();
    }

    /**
     * @covers ::serializeRequest
     */
    public function testSerializeRequest(): void
    {
        $request = new Request(
            ['a' => 1],
            ['b' => 1],
            ['c' => 1],
            ['d' => 1],
            [],
            ['e' => 1]
        );
        $this->serializer->serializeRequest($request);
        $this->assertEquals(self::SIMPLE_SERIALIZED_REQUEST, $this->serializer->serializeRequest($request));
    }

    /**
     * @covers ::deserializeRequest
     */
    public function testDeserializeRequestInvalidJson(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageRegExp('/^Invalid request data:/');
        $this->serializer->deserializeRequest('{');
    }

    /**
     * @covers ::deserializeRequest
     */
    public function testDeserializeRequestIncompleteData(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageRegExp('/^Missing request data/');

        $this->serializer->deserializeRequest('{"files": []}');
    }

    /**
     * @covers ::deserializeRequest
     */
    public function testDeserializeRequestSuccess(): void
    {
        $request = $this->serializer->deserializeRequest(self::SIMPLE_SERIALIZED_REQUEST);
        $this->assertEquals(['a' => 1], $request->query->all());
        $this->assertEquals(['b' => 1], $request->request->all());
        $this->assertEquals(['c' => 1], $request->attributes->all());
        $this->assertEquals(['d' => 1], $request->cookies->all());
        $this->assertEquals(['e' => 1], $request->server->all());
    }
}
