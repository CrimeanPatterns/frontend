<?php

namespace AwardWallet\MainBundle\FrameworkExtension\HttpFoundation;

use GuzzleHttp\Psr7\BufferStream;
use GuzzleHttp\Psr7\Stream;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamCopyResponse extends StreamedResponse
{
    /**
     * StreamCopyResponse constructor.
     *
     * @param Stream|resource $stream
     * @param int $size
     * @param int $status
     */
    public function __construct($stream, $size, $status = 200, array $headers = [])
    {
        parent::__construct(
            /** @var Stream $stream */
            function () use ($size, &$stream) {
                while (ob_get_level()) {
                    ob_end_flush();
                }

                flush();

                $stdout = fopen('php://output', 'wb');

                $streamWriter = function ($from) use ($size, $stdout): void {
                    try {
                        \stream_copy_to_stream(
                            $from,
                            $stdout,
                            $size,
                            0
                        );
                    } finally {
                        \fclose($stdout);
                        \fclose($from);
                    }
                };

                $stringWriter = function (string $from) use ($size, $stdout): void {
                    try {
                        \fwrite($stdout, $from, $size);
                    } finally {
                        \fclose($stdout);
                    }
                };

                if ($stream instanceof Stream) {
                    $stream->rewind();
                    // do not shadow or move $stream out of scope! Destructors will close stream immediately!
                    $resource = $this->getStreamResource($stream);
                    $streamWriter($resource);
                } elseif ($stream instanceof BufferStream) {
                    $stringWriter($stream->getContents());
                } elseif (\is_resource($stream)) {
                    $streamWriter($stream);
                }
            },
            $status,
            array_merge(['Content-Length' => $size], $headers)
        );
    }

    /**
     * @return resource
     */
    protected function getStreamResource(Stream $stream)
    {
        $reflClass = new \ReflectionClass(Stream::class);
        $reflProp = $reflClass->getProperty('stream');
        $reflProp->setAccessible(true);
        $resource = $reflProp->getValue($stream);
        $reflProp->setAccessible(false);

        return $resource;
    }
}
