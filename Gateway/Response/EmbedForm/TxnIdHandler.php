<?php

namespace Conekta\Payments\Gateway\Response\EmbedForm;

use Conekta\Payments\Logger\Logger as ConektaLogger;
use Conekta\Payments\Model\Ui\EmbedForm\ConfigProvider;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;

class TxnIdHandler implements HandlerInterface
{
    public const TXN_ID = 'TXN_ID';
    public const ORD_ID = 'ORD_ID';
    private SubjectReader $subjectReader;
    private ConektaLogger $conektaLogger;

    /**
     * TxnIdHandler constructor.
     *
     * @param ConektaLogger $conektaLogger
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        ConektaLogger $conektaLogger,
        SubjectReader $subjectReader
    ) {
        $this->conektaLogger = $conektaLogger;
        $this->subjectReader = $subjectReader;
        $this->conektaLogger->info('Response TxnIdHandler :: __construct');
    }

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $this->conektaLogger->info('Response TxnIdHandler :: handle', $response);

        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();
        $paymentMethod = $payment->getAdditionalInformation('payment_method');
        switch ($paymentMethod) {
            case ConfigProvider::PAYMENT_METHOD_CREDIT_CARD:
                $this->handleCreditCard($payment, $response);
                break;
            case ConfigProvider::PAYMENT_METHOD_OXXO:
            case ConfigProvider::PAYMENT_METHOD_SPEI:
                $this->handleOffline($payment, $response);
                break;
        }
    }

    /**
     * @param $payment
     * @param $response
     * @return void
     */
    private function handleCreditCard($payment, $response): void
    {
        $order = $payment->getOrder();

        $order->setExtOrderId($response[self::ORD_ID]);

        if (isset($response['payment_method_details']['payment_method']['monthly_installments'])
            && ! empty($response['payment_method_details']['payment_method']['monthly_installments'])) {
            $installments = $response['payment_method_details']['payment_method']['monthly_installments'];
            $order->addStatusHistoryComment(__('Monthly installments select %1 months', $installments));
        }

        $data = [
            'cc_type'      => $payment->getAdditionalInformation('cc_type'),
            'cc_exp_year'  => $payment->getAdditionalInformation('cc_exp_year'),
            'cc_exp_month' => $payment->getAdditionalInformation('cc_exp_month'),
            'cc_bin'       => $payment->getAdditionalInformation('cc_bin'),
            'cc_last_4'    => $payment->getAdditionalInformation('cc_last_4'),
            'card_token'   => $payment->getAdditionalInformation('card_token')
        ];

        $payment->setCcType($payment->getAdditionalInformation('cc_type'));
        $payment->setCcExpMonth($payment->getAdditionalInformation('cc_exp_month'));
        $payment->setCcExpYear($payment->getAdditionalInformation('cc_exp_year'));
        $payment->setAdditionalInformation('additional_data', $data);
        $payment->unsAdditionalInformation('cc_type');
        $payment->unsAdditionalInformation('cc_exp_year');
        $payment->unsAdditionalInformation('cc_exp_month');
        $payment->unsAdditionalInformation('cc_bin');
        $payment->unsAdditionalInformation('cc_last_4');
        $payment->unsAdditionalInformation('card_token');
        $payment->setIsTransactionPending(false);
        $payment->setTransactionId($response[self::TXN_ID]);
        $payment->setIsTransactionClosed(true);
        $payment->setShouldCloseParentTransaction(true);
    }

    /**
     * @param $payment
     * @param $response
     * @return void
     */
    private function handleOffline($payment, $response): void
    {
        $order = $payment->getOrder();

        $order->setExtOrderId($response[self::ORD_ID]);

        $payment->setTransactionId($response[self::TXN_ID]);
        $payment->setAdditionalInformation('offline_info', $response['offline_info']);

        $payment->setIsTransactionPending(true);
        $payment->setIsTransactionClosed(false);
        $payment->setShouldCloseParentTransaction(false);
    }
}
