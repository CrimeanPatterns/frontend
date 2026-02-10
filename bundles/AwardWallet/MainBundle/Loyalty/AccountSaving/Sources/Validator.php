<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Sources;

class Validator
{
    /**
     * @var ValidatorInterface[]
     */
    private $validators;

    public function __construct(iterable $validators)
    {
        $this->validators = $validators;
    }

    /**
     * @param SourceInterface[] $sources
     * @return SourceInterface[]
     */
    public function getLiveSources(array $sources): array
    {
        return array_filter($sources, function (SourceInterface $source) {
            foreach ($this->validators as $validator) {
                $valid = $validator->isValid($source);

                if ($valid !== null) {
                    return $valid;
                }
            }

            return true;
        });
    }
}
