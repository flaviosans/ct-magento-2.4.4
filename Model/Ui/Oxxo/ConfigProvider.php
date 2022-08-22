<?php

namespace Conekta\Payments\Model\Ui\Oxxo;

use Conekta\Payments\Helper\Data as ConektaHelper;
use Magento\Checkout\Model\{ConfigProviderInterface, Session};
use Magento\Framework\Exception\{LocalizedException, NoSuchEntityException};
use Magento\Framework\View\Asset\Repository;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;

class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'conekta_oxxo';
    protected Session $checkoutSession;
    protected Repository $assetRepository;
    protected ConektaHelper $conektaHelper;

    /**
     * @param Session $checkoutSession
     * @param Repository $assetRepository
     * @param ConektaHelper $conektaHelper
     */
    public function __construct(
        Session $checkoutSession,
        Repository $assetRepository,
        ConektaHelper $conektaHelper
    ) {
        $this->conektaHelper = $conektaHelper;
        $this->assetRepository = $assetRepository;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getConfig(): array
    {
        return [
            'payment' => [
                self::CODE => [
                    'total' => $this->getQuote()->getGrandTotal()
                ]
            ]
        ];
    }

    /**
     * @return CartInterface|Quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }
}
