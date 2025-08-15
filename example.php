<?php

require ("vendor/autoload.php");

use Nowpayments\Template\NOWPaymentsApi;
use Nowpayments\Template\Currency;
use Nowpayments\Template\Response\CreateInvoice;
use Nowpayments\Template\Response\CreatePayment;
use Nowpayments\Template\Response\GetEstimatePrice;
use Nowpayments\Template\Response\GetListPayments;

$apiClient = new NOWPaymentsApi("YOUR API KEY");

try {
    $invoice = new CreateInvoice(4.0234, Currency::BTC);
    $invoice->setCancelUrl("https://cancel.url");
    $invoiceReturn = $apiClient->createInvoice($invoice);
    var_dump($invoiceReturn);
    var_dump($apiClient->status());
    var_dump($apiClient->getCurrencies());
    var_dump($apiClient->getListPayments());
    var_dump($apiClient->getListPayments(new GetListPayments()));
    var_dump($apiClient->getEstimatePrice(new GetEstimatePrice('3999.5000', 'usd', 'btc')));
    var_dump($apiClient->createPayment(new CreatePayment(3999.5, Currency::BTC, Currency::ADA)));
    var_dump($apiClient->getMinimumPaymentAmount(Currency::BTC, Currency::ADA));

    {
        $apiClient->setToken('token');

        $data = [
            id => 111,
            offset => 0,
            limit => 10,
            order =>'DESC'
        ];
        $apiClient.callPost('/sub-partner/transfer', $data);
    }
    {
        $apiClient2 = new NOWPaymentsApi("YOUR API KEY");
        $apiClient2->auth('username', 'password');
        $params = [
            id => 111,
            offset => 0,
            limit => 10,
            order => 'DESC'
        ];
        $apiClient2.callGet('/sub-partner', $params);
    }
   
} catch (MyException $e) {
    var_dump($e->getMessage());
}