<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\SpentAnalysisFormatterInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @NoDI()
 */
class SpentAnalysisQuery
{
    /**
     * @var int[]
     */
    protected $subAccountIds = [];
    /**
     * @var ?int
     */
    protected $range;
    /**
     * @var ?int
     */
    protected $limit = SpentAnalysisService::DEFAULT_MERCHANT_DATA_LIMIT;
    /**
     * @var ?int
     */
    protected $merchant;
    /**
     * @var int[]
     */
    protected $offerFilterIds = [];
    /**
     * @var SpentAnalysisFormatterInterface
     */
    protected $formatter;

    protected $extraData;

    /**
     * @return int[]
     */
    public function getSubAccountIds(): array
    {
        return $this->subAccountIds;
    }

    public function setSubAccountIds(array $subAccountIds): self
    {
        $this->subAccountIds = \array_map('\\intval', $subAccountIds);

        return $this;
    }

    public function getRange(): ?int
    {
        return $this->range;
    }

    public function setRange(?int $range): self
    {
        $this->range = $range;

        return $this;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setLimit(?int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function getMerchant(): ?int
    {
        return $this->merchant;
    }

    public function setMerchant(?int $merchant): self
    {
        $this->merchant = $merchant;

        return $this;
    }

    /**
     * @return int[]
     */
    public function getOfferFilterIds(): array
    {
        return $this->offerFilterIds;
    }

    public function setOfferFilterIds(array $offerFilterIds): self
    {
        $this->offerFilterIds = \array_map('\\intval', $offerFilterIds);

        return $this;
    }

    public function getFormatter(): ?SpentAnalysisFormatterInterface
    {
        return $this->formatter;
    }

    public function getExtraData()
    {
        return $this->extraData;
    }

    /**
     * @return SpentAnalysisQuery
     */
    public function setExtraData($extraData)
    {
        $this->extraData = $extraData;

        return $this;
    }

    public function setFormatter(SpentAnalysisFormatterInterface $formatter): self
    {
        $this->formatter = $formatter;

        return $this;
    }

    public static function createMerchantDataQueryByRequest(Request $request): self
    {
        $query = (new self())
            ->setSubAccountIds((array) $request->request->get('ids', []))
            ->setRange(\abs((int) $request->request->get('range')))
            ->setOfferFilterIds((array) $request->request->get('offerFilterIds', []));

        if (
            (null !== ($limit = $request->request->get('limit')))
            && (((int) $limit) > 0)
        ) {
            $query->setLimit((int) $limit);
        }

        return $query;
    }

    public static function createMerchantTransactionQueryByRequest(Request $request): self
    {
        $query = self::createMerchantDataQueryByRequest($request)
            ->setMerchant((int) $request->request->get('merchant'));

        return $query;
    }

    public static function createMerchantDataQueryByForm(FormInterface $form): self
    {
        return self::createMerchantDataQueryByFormData($form->getData());
    }

    public static function createMerchantDataQueryByFormData(array $formData): self
    {
        $query = (new self())
            ->setRange(\abs((int) $formData['date_range']))
            ->setSubAccountIds(\array_values($formData['owner_cards']["owner_cards:{$formData['owner']}"] ?? []))
            ->setOfferFilterIds(
                it($formData['offer_cards'])
                ->flatten(1)
                ->flatMapIndexed(function (bool $value, string $name) use ($formData) {
                    if ($value) {
                        yield $formData['definition']['cards'][$name]['creditCardId'];
                    }
                })
                ->toArray()
            );

        return $query;
    }

    public static function createMerchantTransactionQueryByForm(FormInterface $form, int $merchantId): self
    {
        $query = self::createMerchantDataQueryByForm($form)
            ->setMerchant($merchantId);

        return $query;
    }
}
