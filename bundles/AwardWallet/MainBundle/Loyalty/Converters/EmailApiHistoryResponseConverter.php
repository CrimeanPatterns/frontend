<?php

namespace AwardWallet\MainBundle\Loyalty\Converters;

use AwardWallet\Common\API\Email\V2\ParseEmailResponse;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\Serializer;
use Monolog\Logger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EmailApiHistoryResponseConverter implements ParamConverterInterface
{
    /**
     * @var Serializer
     */
    private $serializer;
    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Serializer $serializer, ValidatorInterface $validator, Logger $logger)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->logger = $logger;
    }

    public function supports(ParamConverter $configuration)
    {
        return $configuration->getClass() && $configuration->getClass() == ParseEmailResponse::class;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        try {
            $apiResponse = $this->serializer->deserialize(
                $request->getContent(),
                ParseEmailResponse::class,
                'json'
            );
        } catch (RuntimeException $e) {
            $this->logger->log('error', 'Received malformed JSON from Email API', ['errors' => $e->getMessage()]);

            throw new \RuntimeException(sprintf('Could not deserialize request content to object of type "%s"', ParseEmailResponse::class));
        }

        $errors = $this->validator->validate($apiResponse);

        if (count($errors)) {
            $this->logger->log('error', 'Received malformed JSON from Email API', ['errors' => (string) $errors]);
        } else {
            $request->attributes->set($configuration->getName(), $apiResponse);
        }
    }
}
