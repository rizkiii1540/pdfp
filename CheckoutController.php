<?php

namespace App\Http\Controllers;

use App\{
    Models\Order,
    Http\Controllers\Controller,
};
use App\Http\Controllers\Payment\FaspayController;
use Carbon\Carbon;
use Faspay\Credit\Entity\Payment\FaspayPaymentCredit;
use Faspay\Credit\Entity\Payment\FaspayPaymentCreditWrapperProd;
use Faspay\Credit\Entity\Payment\Wrapper\FaspayPaymentCreditBillData;
use Faspay\Credit\Entity\Payment\Wrapper\FaspayPaymentCreditCardData;
use Faspay\Credit\Entity\Payment\Wrapper\FaspayPaymentCreditConfigApp;
use Faspay\Credit\Entity\Payment\Wrapper\FaspayPaymentCreditDomicileData;
use Faspay\Credit\Entity\Payment\Wrapper\FaspayPaymentCreditItemData;
use Faspay\Credit\Entity\Payment\Wrapper\FaspayPaymentCreditShippingdata;
use Faspay\Credit\Entity\Payment\Wrapper\FaspayPaymentCreditShopperData;
use Faspay\Credit\Entity\Payment\Wrapper\FaspayPaymentCreditTransactionData;
use Faspay\Entity\Notify\NotifyHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function requestProductStore(Request $request)
    {
        $billing_address = [
            'customer_name' => $request->name,
            'customer_email' => $request->email,
            'shipping_address' => $request->street_address,
            'shipping_state' => $request->state,
            'shipping_post_code' => $request->zipcode,
            'shipping_country' => $request->country,
        ];

        $order_number = Carbon::now()->timestamp;

        $cart = [
            "name" => "Create a Wordpress Website Designers Ecommerce, Multivendor Website Software",
            "slug" => "Create-a-Wordpress-Website-Designers-Ecommerce--Multivendor-Website-Software",
            "qty" => "1",
            "price" => $request->amount,
        ];

        $currency = 'IDR';

        $transaction_number = $order_number;

        $order_status = 'Pending';

        $shipping_info = [
            "ship_first_name" => $request->name,
            "ship_last_name" => $request->name,
            "ship_email" => $request->email,
            "ship_phone" => $request->phone,
            "ship_company" => null,
            "ship_address" => $request->street_address,
            "ship_zip" => $request->zipcode,
            "ship_city" => $request->state,
            "ship_country" => $request->country
        ];

        $billing_info = [
            "bill_name" => $request->name,
            "bill_email" => $request->email,
            "bill_phone" => $request->phone,
        ];

        $payment_status = 'Unpaid';

        Order::create([
            'order_number' => $order_number,
            'cart' => json_encode($cart),
            'payment_method' => $payment_method,
            'currency_sign' => $currency,
            'tax' => $tax,
            'transaction_number' => $transaction_number,
            'order_status' => $order_status,
            'shipping_info' => json_encode($shipping_info),
            'billing_info' => json_encode($billing_info),
            'payment_status' => $payment_status,
            'billing_address' => json_encode($billing_address),
        ]);

        return $this->checkoutFaspay(transaction_number: $order_number, amount: $request->amount, currency: 'IDR', isLN: 0, isProductPayment: 0);
    }

    private function requestProductStoreLN(Request $request)
    {
        $billing_address = [
            'customer_name' => $request->name,
            'customer_email' => $request->email,
            'shipping_address' => $request->street_address,
            'shipping_state' => $request->state,
            'shipping_post_code' => $request->zipcode,
            'shipping_country' => $request->country,
        ];

        $order_number = Carbon::now()->timestamp;

        $cart = [
            "name" => "Create a Wordpress Website Designers Ecommerce, Multivendor Website Software",
            "qty" => "1",
            "price" => convertToIDR($request->amount),
            "main_price" => convertToIDR($request->amount),
        ];

        $payment_method = 'Faspay';

        $currency = 'IDR';

        $tax = '0.35';

        $transaction_number = $order_number;

        $order_status = 'Pending';

        $shipping_info = [
            "ship_first_name" => $request->name,
            "ship_last_name" => $request->name,
            "ship_email" => $request->email,
            "ship_phone" => $request->phone,
            "ship_company" => null,
            "ship_address" => $request->street_address,
            "ship_zip" => $request->zipcode,
            "ship_city" => $request->state,
            "ship_country" => $request->country
        ];

        $billing_info = [
            "_token" => "jTCG9ZNZDqMAR360lPsHDregw1OS4KZw5YQphKZY",
            "bill_name" => $request->name,
            "bill_email" => $request->email,
            "bill_phone" => $request->phone,
            "same_ship_address" => "on"
        ];

        $payment_status = 'Unpaid';

        Session::put('billing_address', $billing_address);

        Order::create([
            'order_number' => $order_number,
            'cart' => json_encode($cart),
            'payment_method' => $payment_method,
            'currency_sign' => $currency,
            'tax' => $tax,
            'transaction_number' => $transaction_number,
            'order_status' => $order_status,
            'shipping_info' => json_encode($shipping_info),
            'billing_info' => json_encode($billing_info),
            'payment_status' => $payment_status,
            'billing_address' => json_encode($billing_address),
        ]);

        return $this->checkoutFaspay(transaction_number: $order_number, amount: $request->amount, currency: 'IDR', isLN: 1, isProductPayment: 0);
    }

    public function checkoutFaspay($transaction_number, $amount, $currency, $isLN, $isProductPayment)
    {
        $usr = new FaspayController();

        if ($isProductPayment == 1) {
            $order = Order::where('transaction_number', $transaction_number)->first();
            $shipping = json_decode($order['shipping_info']);

            $shopData = new FaspayPaymentCreditShopperData(
                custname: $shipping->ship_first_name,
                custemail: $shipping->ship_email,
            );

            $shippingData = new FaspayPaymentCreditShippingdata(
                receiver_name_for_shipping: $shipping->ship_first_name,
                shipping_address: $shipping->ship_address1,
                shipping_address_state: $shipping->ship_city,
                shipping_address_poscode: $shipping->ship_zip,
                shipping_address_country_code: $shipping->ship_country ? get_country_id($shipping->ship_country) : null,
            );
        } else {
            $order = Session::get('billing_address');

            if ($isLN == 1) {
                $amount = convertToIDR($amount);
            }

            $shopData = new FaspayPaymentCreditShopperData(
                custname: $order['customer_name'],
                custemail: $order['customer_email'],
            );

            $shippingData = new FaspayPaymentCreditShippingdata(
                receiver_name_for_shipping: $order['customer_name'],
                shipping_address: $order['shipping_address'],
                shipping_address_state: $order['shipping_state'],
                shipping_address_poscode: $order['shipping_post_code'],
                shipping_address_country_code: $order['shipping_country'],
            );
        }

        $w = new FaspayPaymentCreditWrapperProd(
            user: $usr,
            transactionData: new FaspayPaymentCreditTransactionData($usr, $transaction_number, $currency, round($amount)),
            shopperData: $shopData,
            app: new FaspayPaymentCreditConfigApp(FaspayPaymentCredit::RESPONSE_TYPE_POST, config('faspay.redirecturl_process')),
            billData: new FaspayPaymentCreditBillData(billing_address_country_code: "ID"),
            shippingdata: $shippingData,
            itemData: new FaspayPaymentCreditItemData(array()),
            domicileData: new FaspayPaymentCreditDomicileData(),
            cardData: new FaspayPaymentCreditCardData()
        );

        // Return HTML Response
        return $w->generateHtml();
    }

    // Callback from Faspay, POST Request
    public function notifyFaspay()
    {
        $a = new NotifyHandler();
        $data = $a->handle();

        if ($data->TXN_STATUS == 'C' || $data->TXN_STATUS == 'S') {
            $order = Order::where('transaction_number', $data->MERCHANT_TRANID)->first();

            DB::beginTransaction();

            $order->payment_status = 'Paid';
            $order->order_status = 'Success';
            $order->save();

            DB::commit();

            // Return if success

        } else {
            // Return if failed
        }
    }
}
