<?php

namespace Nowpayments\Template;

require('vendor/autoload.php');
require('response/StatusReturn.php');
require('response/EstimatedPriceReturn.php');
require('response/CreatePaymentReturn.php');
require('response/PaymentStatusReturn.php');
require('response/MinimumPaymentAmountResponse.php');
require('response/ListPaymentItem.php');
require('response/GetListPaymentsReturn.php');
require('response/InvoiceReturn.php');
require('Exception.php');

use DateTime;
use MyException;
use Nowpayments\Template\Response\CreateInvoice;
use Nowpayments\Template\Response\CreatePaymentReturn;
use Nowpayments\Template\Response\EstimatedPriceReturn;
use Nowpayments\Template\Response\GetListPaymentsReturn;
use Nowpayments\Template\Response\InvoiceReturn;
use Nowpayments\Template\Response\ListPaymentItem;
use Nowpayments\Template\Response\MinimumPaymentAmountResponse;
use Nowpayments\Template\Response\PaymentStatusReturn;
use Nowpayments\Template\Response\StatusReturn;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

/**
 * https://documenter.getpostman.com/view/7907941/S1a32n38#3e3ce25e-f43f-4636-bbd9-11560e46048b
 * @author Bearname
 * @package Nowpayments\Template
 */
class NOWPaymentsApi
{
    const SORT_BY_FIELDS = ["payment_id", "payment_status", "pay_address", "price_amount", "price_currency",
        "pay_amount", "actually_paid", "pay_currency",
        "order_id", "order_description", "purchase_id", "outcome_amount", "outcome_currency",
    ];

    private $apiKey;
    private $url;

    /**
     * NOWPaymentsApi constructor.
     * @param $apiKey
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
//        $this->url = 'https://api.nowpayments.io/v1';
        $this->url = 'http://localhost:3000/v1';
    }

    /**
     * @return StatusReturn
     * @throws MyException
     */
    public function status(): StatusReturn
    {
        $httpClient = HttpClient::create();
        try {
            $response = $httpClient->request('GET', $this->url . '/status');
            $content = $response->getContent();
            $decode = json_decode($content, true);

            if ($this->checkExistKey($decode, "message")) {
                throw new MyException("response json don't have message field");
            }
            return new StatusReturn($decode["message"]);
        } catch (ExceptionInterface $e) {
            throw new MyException($e->getMessage());
        }
    }

    /**
     * @return array
     * @throws MyException
     */
    public function getCurrencies(): array
    {
        try {
            $httpClient = HttpClient::create();

            $options = [
                'headers' => ['x-api-key' => $this->apiKey],
            ];

            $response = $httpClient->request('GET', $this->url . '/currencies', $options);
            $content = $response->getContent();
            var_dump($content);
            $decode = json_decode($content, true);

            if ($this->checkExistKey($decode, "currencies")) {
                throw new MyException("response json don't have currencies field");
            }

            return $decode["currencies"];
        } catch (ExceptionInterface $e) {
            throw new MyException($e->getMessage());
        }
    }

    /**
     * @param $amount
     * @param $currency_from
     * @param $currency_to
     * @return EstimatedPriceReturn
     * @throws MyException
     */
    public function getEstimatePrice($amount, $currency_from, $currency_to): EstimatedPriceReturn
    {
        try {
            $httpClient = HttpClient::create();

            $response = $httpClient->request('GET', $this->url . '/estimate', [
                'headers' => ['x-api-key' => $this->apiKey],
                'query' => [
                    "amount" => $amount,
                    "currency_from" => $currency_from,
                    "currency_to" => $currency_to
                ]
            ]);

            $content = $response->getContent();
            $decode = json_decode($content, true);

            if ($this->checkExistKey($decode, "currency_from")) {
                throw new MyException("response json don't have currency_from field");
            }
            if ($this->checkExistKey($decode, "amount_from")) {
                throw new MyException("response json don't have amount_from field");
            }
            if ($this->checkExistKey($decode, "currency_to")) {
                throw new MyException("response json don't have currency_to field");
            }
            if ($this->checkExistKey($decode, "estimated_amount")) {
                throw new MyException("response json don't have estimated_amount field");
            }

            return new EstimatedPriceReturn($decode["currency_from"], $decode["amount_from"], $decode["currency_to"], $decode["estimated_amount"]);
        } catch (ExceptionInterface $e) {
            throw new MyException($e->getMessage());
        }
    }

    /**
     *   * This is the method to create a payment. You need to provide your data as a JSON-object payload. Next is a description of the required request fields:
     *   price_amount (required) - the fiat equivalent of the price to be paid in crypto. If the pay_amount parameter is left empty, our system will automatically convert this fiat price into its crypto equivalent. Please note that this does not enable fiat payments, only provides a fiat price for yours and the customer’s convenience and information.
     *   price_currency (required) - the fiat currency in which the price_amount is specified (usd, eur, etc).
     *   pay_amount (optional) - the amount that users have to pay for the order stated in crypto. You can either specify it yourself, or we will automatically convert the amount you indicated in price_amount.
     *   pay_currency (required) - the crypto currency in which the pay_amount is specified (btc, eth, etc).
     *   ipn_callback_url (optional) - url to receive callbacks, should contain "http" or "https", eg. "https://nowpayments.io"
     *   order_id (optional) - inner store order ID, e.g. "RGDBP-21314"
     *   order_description (optional) - inner store order description, e.g. "Apple Macbook Pro 2019 x 1"
     *   purchase_id (optional) - id of purchase for which you want to create aother payment, only used for several payments for one order
     *   payout_address (optional) - usually the funds will go to the address you specify in your Personal account. In case you want to receive funds on another address, you can specify it in this parameter.
     *   payout_currency (optional) - currency of your external payout_address, required when payout_adress is specified.
     *   payout_extra_id(optional) - extra id or memo or tag for external payout_address.
     *   fixed_rate(optional) - boolean, can be true or false. Required for fixed-rate exchanges.
     * @param int $priceAmount
     * @param string $priceCurrency
     * @param string $payCurrency
     * @param array $optionals
     * @return CreatePaymentReturn
     * @throws MyException
     */
    public function createPayment(int $priceAmount, string $priceCurrency, string $payCurrency, array $optionals = []): CreatePaymentReturn
    {
        try {
            $httpClient = HttpClient::create();

            if ($this->checkExistKey($optionals, "payout_address") && $this->checkExistKey($optionals, "payout_currency")) {
                throw new MyException("currency of your external payout_address, required when payout_adress is specified.");
            }
            $optionals['price_amount'] = $priceAmount;
            $optionals['price_currency'] = $priceCurrency;
            $optionals['pay_currency'] = $payCurrency;

            $response = $httpClient->request('POST', $this->url . '/payment', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-api-key' => $this->apiKey
                ],
                'body' => json_encode($optionals)
            ]);

            $content = $response->getContent();
            $decode = json_decode($content, true);

            $this->buildPaymentReturn($decode);
        } catch (ExceptionInterface $e) {
            throw new MyException($e->getMessage());
        }
    }

    /**
     * @param $paymentId
     * @return PaymentStatusReturn
     * @throws MyException
     */
    public function getPaymentStatus($paymentId): PaymentStatusReturn
    {
        try {
            $httpClient = HttpClient::create();

            $response = $httpClient->request('GET', $this->url . '/payment/' . $paymentId, [
                'headers' => ['x-api-key' => $this->apiKey],
            ]);

            $content = $response->getContent();
            $decode = json_decode($content, true);
            if ($this->checkExistKey($decode, "payment_id")) {
                throw new MyException("response json don't have payment_id field");
            }
            if ($this->checkExistKey($decode, "payment_status")) {
                throw new MyException("response json don't have payment_status field");
            }
            if ($this->checkExistKey($decode, "pay_address")) {
                throw new MyException("response json don't have pay_address field");
            }
            if ($this->checkExistKey($decode, "price_amount")) {
                throw new MyException("response json don't have price_amount field");
            }
            if ($this->checkExistKey($decode, "price_currency")) {
                throw new MyException("response json don't have price_currency field");
            }
            if ($this->checkExistKey($decode, "pay_amount")) {
                throw new MyException("response json don't have pay_amount field");
            }
            if ($this->checkExistKey($decode, "actually_paid")) {
                throw new MyException("response json don't have actually_paid field");
            }
            if ($this->checkExistKey($decode, "pay_currency")) {
                throw new MyException("response json don't have pay_currency field");
            }
            if ($this->checkExistKey($decode, "order_id")) {
                throw new MyException("response json don't have order_id field");
            }
            if ($this->checkExistKey($decode, "order_description")) {
                throw new MyException("response json don't have order_description field");
            }
            if ($this->checkExistKey($decode, "purchase_id")) {
                throw new MyException("response json don't have purchase_id field");
            }
            if ($this->checkExistKey($decode, "created_at")) {
                throw new MyException("response json don't have created_at field");
            }
            if ($this->checkExistKey($decode, "updated_at")) {
                throw new MyException("response json don't have updated_at field");
            }
            if ($this->checkExistKey($decode, "outcome_amount")) {
                throw new MyException("response json don't have outcome_currency field");
            }

            return new PaymentStatusReturn(
                $decode["payment_id"], $decode["payment_status"], $decode["pay_address"], $decode["price_amount"],
                $decode["price_currency"], $decode["pay_amount"], $decode["actually_paid"], $decode["pay_currency"],
                $decode["order_id"], $decode["order_description"], $decode["purchase_id"], $decode["created_at"], $decode["updated_at"],
                $decode["outcome_amount"], $decode["outcome_currency"]);
        } catch (ExceptionInterface $e) {
            throw new MyException($e->getMessage());
        }
    }

    /**
     *   Get the minimum payment amount for a specific pair.
     *   You can provide both currencies in the pair or just currency_from, and we will calculate the minimum payment amount for currency_from and currency which you have specified as the outcome in the Store Settings.
     *   In the case of several outcome wallets we will calculate the minimum amount in the same way we route your payment to a specific wallet.
     * @param $currency_from
     * @param $currency_to
     * @return MinimumPaymentAmountResponse
     * @throws MyException
     */
    public function getMinimumPaymentAmount($currency_from, $currency_to): MinimumPaymentAmountResponse
    {
        try {
            $httpClient = HttpClient::create();

            $response = $httpClient->request('GET', $this->url . '/min-amount', [
                'headers' => ['x-api-key' => $this->apiKey],
                'query' => [
                    "currency_from" => $currency_from,
                    "currency_to" => $currency_to
                ]
            ]);

            $content = $response->getContent();
            $result = json_decode($content, true);
            var_dump($result);

            if ($this->checkExistKey($result, "currency_from")) {
                throw new MyException("response json don't have currency_from field");
            }

            if ($this->checkExistKey($result, "currency_to")) {
                throw new MyException("response json don't have currency_to field");
            }

            if ($this->checkExistKey($result, "min_amount")) {
                throw new MyException("response json don't have min_amount field");
            }

            return new MinimumPaymentAmountResponse($result["currency_from"], $result["currency_to"], $result["min_amount"]);
        } catch (ExceptionInterface $e) {
            throw new MyException($e->getMessage());
        }
    }

    /**
     *   Get the minimum payment amount for a specific pair.
     *   You can provide both currencies in the pair or just currency_from, and we will calculate the minimum payment amount for currency_from and currency which you have specified as the outcome in the Store Settings.
     *   In the case of several outcome wallets we will calculate the minimum amount in the same way we route your payment to a specific wallet.
     * @param int $limit
     * @param int $page
     * @param $sortBy
     * @param $orderBy
     * @param $dateFrom
     * @param $dateTo
     * @return GetListPaymentsReturn
     * @throws MyException
     */
    public function getListPayments(int $limit = 2147483647, int $page = 2147483647, string $sortBy = "", string $orderBy = "", string $dateFrom = "", string $dateTo = ""): GetListPaymentsReturn
    {
        try {
            $httpClient = HttpClient::create();

            $data = [];

            if ($limit !== 2147483647 && ($limit < 1 || $limit > 500)) {
                throw new MyException("Valid limit range [1: 500]. Current " . $limit);
            } else {
                $data['limit'] = $limit;
            }

            if ($page !== 2147483647 && $page < 0) {
                throw new MyException("Invalid page value");
            } else {
                $data['page'] = $page;
            }

            if (strlen($sortBy) !== 0 && !in_array($sortBy, $this::SORT_BY_FIELDS)) {
                throw new MyException("Invalid sort fields. Valid sort fields payment_id, payment_status, pay_address, price_amount, price_currency, pay_amount, actually_paid, pay_currency, order_id, order_description, purchase_id, outcome_amount, outcome_currency");
            } else if (strlen($sortBy) !== 0) {
                $data['sortBy'] = $sortBy;
            }

            if (strlen($orderBy) !== 0 && $orderBy !== "asc" && $orderBy !== "desc") {
                throw new MyException("Invalid order by fields. Valid value asc, desc");
            } else if (strlen($orderBy) !== 0) {
                $data['orderBy'] = $orderBy;
            }

            if (strlen($dateFrom) !== 0) {
                $data['dateFrom'] = $dateFrom;
            }
            if (strlen($dateTo) !== 0) {
                $data['dateTo'] = $dateTo;
            }

            $response = $httpClient->request('GET', $this->url . '/payment', [
                'headers' => ['x-api-key' => $this->apiKey],
                'query' => $data
            ]);

            $content = $response->getContent();
            $result = json_decode($content, true);
            var_dump($result);
            if ($this->checkExistKey($result, "data")) {
                throw new MyException("response json don't have date field");
            }
            if ($this->checkExistKey($result, "limit")) {
                throw new MyException("response json don't have limit field");
            }
            if ($this->checkExistKey($result, "page")) {
                throw new MyException("response json don't have page field");
            }
            if ($this->checkExistKey($result, "pagesCount")) {
                throw new MyException("response json don't have pagesCount field");
            }
            if ($this->checkExistKey($result, "total")) {
                throw new MyException("response json don't have total field");
            }

            $getListPaymentsReturn = new GetListPaymentsReturn($result["limit"], $result["page"], $result["pagesCount"], $result["total"]);
            foreach ($result["data"] as $invoice) {
                $invoice1 = new ListPaymentItem($invoice["payment_id"],
                    $invoice["payment_status"],
                    $invoice["pay_address"],
                    $invoice["price_amount"],
                    $invoice["price_currency"],
                    $invoice["pay_amount"],
                    $invoice["actually_paid"],
                    $invoice["pay_currency"],
                    $invoice["order_id"],
                    $invoice["order_description"]);

                if ($this->checkExistKey($invoice, "purchase_id")) {
                    $invoice1->setPurchaseId($invoice["purchase_id"]);
                }
                if ($this->checkExistKey($invoice, "outcome_amount")) {
                    $invoice1->setOutcomeAmount($invoice["outcome_amount"]);
                }
                if ($this->checkExistKey($invoice, "outcome_currency")) {
                    $invoice1->setOutcomeCurrency($invoice["outcome_amount"]);
                }

                $getListPaymentsReturn->pushInvoice($invoice1);
            }

            return $getListPaymentsReturn;
        } catch (ExceptionInterface $e) {
            throw new MyException($e->getMessage());
        }
    }

    /**
     * Creates invoice with url where you can complete the payment. Request fields:
     *
     * price_amount (required) - the amount that users have to pay for the order stated in fiat currency. In case you do not indicate the price in crypto, our system will automatically convert this fiat amount into its crypto equivalent.
     * price_currency (required) - the fiat currency in which the price_amount is specified (usd, eur, etc).
     * pay_currency (optional) - the crypto currency in which the pay_amount is specified (btc, eth, etc).If not specified, can be chosen on the invoice_url
     * ipn_callback_url (optional) - url to receive callbacks, should contain "http" or "https", eg. "https://nowpayments.io"
     * order_id (optional) - inner store order ID, e.g. "RGDBP-21314"
     * order_description (optional) - inner store order description, e.g. "Apple Macbook Pro 2019 x 1"
     * success_url(optional) - url where the customer will be redirected after successful payment.
     * cancel_url(optional) - url where the customer will be redirected after failed payment.
     *
     * @param CreateInvoice $invoice
     * @return InvoiceReturn
     * @throws MyException
     */
    public function createInvoice(CreateInvoice $invoice): InvoiceReturn
    {
        try {
            $httpClient = HttpClient::create();

            $data = [
                "price_amount" => $invoice->getPriceAmount(),
                "price_currency" => $invoice->getPriceCurrency(),
            ];
            if (strlen($invoice->getPayCurrency()) !== 0) {
                $data["pay_currency"] = $invoice->getPayCurrency();
            }
            if (strlen($invoice->getIpnCallbackUrl()) !== 0) {
                $data["ipn_callback_url"] = $invoice->getIpnCallbackUrl();
            }
            if (strlen($invoice->getOrderId()) !== 0) {
                $data["order_id"] = $invoice->getOrderId();
            }
            if (strlen($invoice->getOrderDescription()) !== 0) {
                $data["order_description "] = $invoice->getOrderDescription();
            }
            if (strlen($invoice->getSuccessUrl()) !== 0) {
                $data["success_url"] = $invoice->getSuccessUrl();
            }
            if (strlen($invoice->getCancelUrl()) !== 0) {
                $data["cancel_url"] = $invoice->getCancelUrl();
            }

            $response = $httpClient->request('POST', $this->url . '/invoice', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-api-key' => $this->apiKey
                ],
                'body' => json_encode($data)
            ]);

            $content = $response->getContent();

            $invoice = json_decode($content, true);

            if (!$this->checkExistKey($invoice, "id") || !$this->checkExistKey($invoice, "order_id") ||
                !$this->checkExistKey($invoice, "order_description") || !$this->checkExistKey($invoice, "price_amount") ||
                !$this->checkExistKey($invoice, "price_currency") || !$this->checkExistKey($invoice, "pay_currency") ||
                !$this->checkExistKey($invoice, "ipn_callback_url") || !$this->checkExistKey($invoice, "invoice_url") ||
                !$this->checkExistKey($invoice, "success_url") || !$this->checkExistKey($invoice, "cancel_url") ||
                !$this->checkExistKey($invoice, "created_at") || !$this->checkExistKey($invoice, "updated_at")) {
                throw new MyException("Invalid response");
            }

            return new InvoiceReturn($invoice["id"], $invoice["order_id"], $invoice["order_description"], $invoice["price_amount"],
                $invoice["price_currency"], $invoice["pay_currency"], $invoice["ipn_callback_url"], $invoice["invoice_url"],
                $invoice["success_url"], $invoice["cancel_url"], $invoice["created_at"], $invoice["updated_at"]);
        } catch (ExceptionInterface $e) {
            throw new MyException($e->getMessage());
        } catch (MyException $exception) {
            throw new MyException($exception->getMessage());
        }
    }

    /**
     * @param $array
     * @param $key
     * @return bool
     */
    public function checkExistKey($array, $key): bool
    {
        return array_key_exists($key, $array);
    }

    private function validateDate($date, $format = ' YYYY-MM-DD')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    /**
     * @param $decode
     * @return CreatePaymentReturn
     * @throws MyException
     */
    private function buildPaymentReturn($decode): CreatePaymentReturn
    {
        if ($this->checkExistKey($decode, "payment_id")) {
            throw new MyException("response json don't have payment_id field");
        }
        if ($this->checkExistKey($decode, "payment_status")) {
            throw new MyException("response json don't have payment_status field");
        }
        if ($this->checkExistKey($decode, "pay_address")) {
            throw new MyException("response json don't have pay_address field");
        }
        if ($this->checkExistKey($decode, "price_amount")) {
            throw new MyException("response json don't have price_amount field");
        }
        if ($this->checkExistKey($decode, "price_currency")) {
            throw new MyException("response json don't have price_currency field");
        }
        if ($this->checkExistKey($decode, "pay_amount")) {
            throw new MyException("response json don't have pay_amount field");
        }
        if ($this->checkExistKey($decode, "pay_currency")) {
            throw new MyException("response json don't have pay_currency field");
        }
        if ($this->checkExistKey($decode, "order_id")) {
            throw new MyException("response json don't have order_id field");
        }
        if ($this->checkExistKey($decode, "order_description")) {
            throw new MyException("response json don't have order_description field");
        }
        if ($this->checkExistKey($decode, "ipn_callback_url")) {
            throw new MyException("response json don't have ipn_callback_url field");
        }
        if ($this->checkExistKey($decode, "created_at")) {
            throw new MyException("response json don't have created_at field");
        }
        if ($this->checkExistKey($decode, "purchase_id")) {
            throw new MyException("response json don't have purchase_id field");
        }

        return new CreatePaymentReturn(
            $decode["payment_id"], $decode["payment_status"], $decode["pay_address"], $decode["price_amount"],
            $decode["price_currency"], $decode["pay_amount"], $decode["pay_currency"], $decode["order_id"],
            $decode["order_description"], $decode["ipn_callback_url"], $decode["created_at"], $decode["updated_at"],
            $decode["purchase_id"]);
    }
}