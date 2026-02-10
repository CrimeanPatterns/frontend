<?php

namespace AwardWallet\MainBundle\Globals\AccountList\Mapper\MobileFormatter;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Globals\DateTimeUtils;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\CallableEncoder;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\EncoderInterface;

/**
 * @psalm-import-type Block from BlockFactory
 * @psalm-type EntityData = array{
 *     CustomFields?: ?array
 * }
 * @NoDI()
 */
class DocumentBlockEncoderFactory
{
    private LocalizeService $localizer;
    private string $documentFieldKey;
    private ?string $locale;

    public function __construct(LocalizeService $localizer, string $documentFieldKey, ?string $locale)
    {
        $this->localizer = $localizer;
        $this->documentFieldKey = $documentFieldKey;
        $this->locale = $locale;
    }

    /**
     * @template CallableInput
     * @return CallableEncoder<CallableInput, Block>
     */
    public function createStringBlockEncoder(string $name): CallableEncoder
    {
        return new CallableEncoder(function ($value) use ($name) {
            return BlockFactory::createBlock(
                BlockFactory::BLOCK_TYPE_STRING,
                $name,
                $value
            );
        });
    }

    /**
     * @template CallableInput
     * @return CallableEncoder<CallableInput, Block>
     */
    public function createTypeValueBlockEncoder(string $type): CallableEncoder
    {
        return new CallableEncoder(function ($value) use ($type) {
            return BlockFactory::createBlock(
                $type,
                null,
                $value
            );
        });
    }

    /**
     * @return callable(EntityData): ?mixed
     */
    public function createGetter(string $name): callable
    {
        return function (array $input) use ($name) {
            return $input['CustomFields'][$this->documentFieldKey][$name] ?? null;
        };
    }

    /**
     * @template TransformerInput
     * @template TransformerOutput
     * @param callable(TransformerInput): ?TransformerOutput $transformer
     * @return EncoderInterface<EntityData, Block>
     */
    public function createTransformerStringEncoder(string $field, string $name, callable $transformer): EncoderInterface
    {
        return CallableEncoder::new($this->createGetter($field))
            ->andThenIfNotEmpty(new CallableEncoder($transformer))
            ->andThenIfNotEmpty($this->createStringBlockEncoder($name));
    }

    /**
     * @template TransformerInput
     * @template TransformerOutput
     * @param callable(TransformerInput): ?TransformerOutput $transformer
     * @return EncoderInterface<EntityData, Block>
     */
    public function createTransformerTypeValueEncoder(string $field, string $type, callable $transformer): EncoderInterface
    {
        return CallableEncoder::new($this->createGetter($field))
            ->andThenIfNotEmpty(new CallableEncoder($transformer))
            ->andThenIfNotEmpty($this->createTypeValueBlockEncoder($type));
    }

    /**
     * @return EncoderInterface<EntityData, Block>
     */
    public function createStringEncoder(string $field, string $name): EncoderInterface
    {
        return
            CallableEncoder::new($this->createGetter($field))
            ->andThenIfNotEmpty($this->createStringBlockEncoder($name));
    }

    /**
     * @return EncoderInterface<EntityData, Block>
     */
    public function createDateEncoder(string $field, string $name): EncoderInterface
    {
        return $this->createTransformerStringEncoder(
            $field,
            $name,
            function (array $date) {
                return $this->localizer->formatDateTime(
                    DateTimeUtils::fromSerializedArray($date),
                    'medium',
                    null,
                    $this->locale
                ) ?: null;
            }
        );
    }

    /**
     * @return EncoderInterface<EntityData, Block>
     */
    public function createOptionsEncoder(string $field, string $name, array $options): EncoderInterface
    {
        return $this->createTransformerStringEncoder(
            $field,
            $name,
            function ($input) use ($options) {
                return $options[$input] ?? null;
            }
        );
    }

    /**
     * @return EncoderInterface<EntityData, Block>
     */
    public function createCheckboxEncoder(string $field, string $name): EncoderInterface
    {
        return $this->createTransformerStringEncoder(
            $field,
            $name,
            function ($input) {
                return $input ? '✓' : null;
            }
        );
    }

    /**
     * @param callable(int): ?string $countryLocalizer
     * @return EncoderInterface<EntityData, Block>
     */
    public function createCountryEncoder(string $field, string $name, callable $countryLocalizer): EncoderInterface
    {
        return $this->createTransformerStringEncoder(
            $field,
            $name,
            $countryLocalizer
        );
    }
}
