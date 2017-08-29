<?php

$iaTap = $iaCore->factoryPlugin('tap', 'common');

if (isset($_GET['ref'], $_GET['result'], $_GET['trackid'], $_GET['hash'])) {
    $result = $iaTap->validate($_GET['ref'], $_GET['result'], $_GET['trackid'], $_GET['hash']);

    if ($result && $_GET['result']) {
        if (iaTap::RESULT_SUCCESS == $_GET['result']) {
            $result = $iaTap->getPaymentStatus($_GET['ref']);
            $statusInfo = json_decode($result, true);
            switch ($statusInfo['ResponseMessage']) {
                case iaTap::PAYMENT_STATUS_PAID:
                    $transaction = $temp_transaction;
                    $transaction['status'] = iaTransaction::PASSED;
                    $transaction['reference_id'] = $_GET['ref'];

                    $member = $iaUsers->getInfo($transaction['member_id']);

                    $order['payment_gross'] = $transaction['amount'];
                    $order['mc_currency'] = $transaction['currency'];
                    $order['payment_date'] = strftime($iaCore->get('date_format'), time());
                    $order['payment_status'] = iaLanguage::get($transaction['status']);
                    $order['first_name'] = ($member['fullname'] ? $member['fullname'] : $member['username']);
                    $order['last_name'] = '';
                    $order['payer_email'] = $member['email'];
                    $order['txn_id'] = $transaction['reference_id'];
                    break;
                case iaTap::PAYMENT_STATUS_FAILED:
                    $transaction['status'] = iaTransaction::FAILED;
                default:
                    $messages[] = 'Tap payment status: ' . $statusInfo['ResponseMessage'];
            }
        } elseif (iaTap::RESULT_FAILED == $_GET['result']) {
            $transaction['status'] = iaTransaction::FAILED;
            $messages[] = 'Tap error message: ' . $_GET['result'];
        }
    } else {
        $messages[] = iaLanguage::get('error');
    }
}
