<?php

namespace AwardWallet\MainBundle\Security\Encryptor;

use AwardWallet\MainBundle\Security\Encryptor\Backend\BackendInterface;
use AwardWallet\MainBundle\Security\Encryptor\Exception\NoEncryptionDetectedException;

class Encryptor
{
    /**
     * @var iterable<BackendInterface>
     */
    private $otherBackends;
    /**
     * @var BackendInterface
     */
    private $actualBackend;

    /**
     * Encryptor constructor.
     *
     * @param iterable<BackendInterface> $otherBackends
     */
    public function __construct(BackendInterface $actualBackend, iterable $otherBackends)
    {
        $this->otherBackends = $otherBackends;
        $this->actualBackend = $actualBackend;
    }

    public function encrypt(string $plantext): string
    {
        return $this->actualBackend->encrypt($plantext);
    }

    public function decrypt(string $ciphertext): string
    {
        if ($backend = $this->getSupportingBackend($ciphertext)) {
            return $backend->decrypt($ciphertext);
        }

        throw new NoEncryptionDetectedException();
    }

    public function supports(string $ciphertext): bool
    {
        return null !== $this->getSupportingBackend($ciphertext);
    }

    private function getSupportingBackend(string $ciphertext): ?BackendInterface
    {
        foreach ($this->getBackendsIter() as $backend) {
            if ($backend->supports($ciphertext)) {
                return $backend;
            }
        }

        return null;
    }

    /**
     * @return iterable<BackendInterface>
     */
    private function getBackendsIter(): iterable
    {
        yield $this->actualBackend;

        yield from $this->otherBackends;
    }
}
