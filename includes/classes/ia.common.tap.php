<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2017 Intelliants, LLC <https://intelliants.com>
 *
 * This file is part of Subrion.
 *
 * Subrion is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Subrion is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Subrion. If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @link https://subrion.org/
 *
 ******************************************************************************/

class iaTap extends abstractCore
{
    const RESPONSE_SUCCESS = 'Success';
    const RESULT_SUCCESS = 'SUCCESS';
    const RESULT_FAILED = 'FAILED';
    const PAYMENT_STATUS_PAID = 'CAPTURED';
    const PAYMENT_STATUS_FAILED = 'FAILED';

    public $baseUrl;
    public $apiKey;
    public $merchantID;
    public $username;
    public $password;
    public $currency;
    public $gateways;

    public function init()
    {
        parent::init();
        $this->baseUrl = 'https://www.gotapnow.com/TapWebConnect/Tap/WebPay/';
        if ($this->iaCore->get('tap_demo_mode')) {
            $this->baseUrl = 'http://tapapi.gotapnow.com/TapWebConnect/Tap/WebPay/';
        }

        $this->apiKey = $this->iaCore->get('tap_api_key');
        $this->merchantID = $this->iaCore->get('tap_merchant_id');
        $this->username = $this->iaCore->get('tap_username');
        $this->password = $this->iaCore->get('tap_password');
        $this->currency = $this->iaCore->get('tap_currency_code');
        $this->gateways = $this->iaCore->get('tap_gateways');
    }


    public function getHash($reference, $memberId)
    {
        $string = 'X_MerchantID' . $this->merchantID . 'X_UserName' . $this->username . 'X_ReferenceID' . $reference
            . 'X_Mobile' . $memberId . 'X_CurrencyCode' . $this->currency . 'X_Total' . 1;

        return hash_hmac('sha256', $string, $this->apiKey);
    }

    /**
     * @param $member array member info
     * @param $transaction array transaction
     * @param $hash string hash
     * @param $reference string reference ID
     *
     * @return string|bool json response or false on error
     */
    public function createPayment($member, $transaction, $hash, $reference)
    {
        $params = [
            'CustomerDC' => [
                'Email' => $member['email'],
                'Mobile' => $member['phone'],
                'Name' => ($member['fullname'] ? $member['fullname'] : $member['username']),
            ],
            'lstProductDC' => [
                [
                    'CurrencyCode' => $this->currency,
                    'Quantity' => 1,
                    'TotalPrice' => $transaction['amount'],
                    'UnitDesc' => $transaction['operation'],
                    'UnitName' => $transaction['operation'],
                    'UnitPrice' => $transaction['amount'],
                ],
            ],
            'lstGateWayDC' => [
                [
                    'Name' => $this->gateways,
                ],
            ],
            'MerMastDC' => [
                'AutoReturn' => 'Y',
                'HashString' => $hash,
                'LangCode' => strtoupper($this->iaView->language),
                'MerchantID' => $this->merchantID,
                'Password' => $this->password,
                'ReferenceID' => $reference,
                'ReturnURL' => IA_RETURN_URL . 'completed' . IA_URL_DELIMITER,
                'UserName' => $this->username,
            ],
        ];

        $json = json_encode($params, JSON_UNESCAPED_SLASHES);
        $response = $this->_sendRequest($this->baseUrl . 'PaymentRequest/', $json);
        if ($response) {
            return json_decode($response, true);
        }

        return false;
    }

    public function validate($reference, $result, $trackId, $hash)
    {
        $string = 'x_account_id' . $this->merchantID . 'x_ref' . $reference . 'x_result' . $result . 'x_referenceid' . $trackId;
        $generatedHash = hash_hmac('sha256', $string, $this->apiKey);

        return ($generatedHash == $hash);
    }

    public function getPaymentStatus($reference)
    {
        $json = json_encode([
            'ReferenceID' => $reference,
            'UserName' => $this->username,
            'Password' => $this->password,
            'MerchantId' => $this->merchantID,
        ]);

        return $this->_sendRequest($this->baseUrl . 'GetPaymentStatus/', $json);
    }

    private function _sendRequest($url, $jsonParams)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $jsonParams,
            CURLOPT_HTTPHEADER => [
                'content-type: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            $message = 'Tap: cURL Error #: ' . $error;
            iaDebug::log($message);
            exit($message);
        }

        return $response;
    }
}