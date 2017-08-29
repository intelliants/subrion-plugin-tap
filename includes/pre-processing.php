<?php

$iaTap = $iaCore->factoryPlugin('tap', 'common');

$reference = $transaction['id'] . $transaction['plan_id'] . $transaction['item_id'];

$hash = $iaTap->getHash($reference, iaUsers::getIdentity()->id);

$response = $iaTap->createPayment(iaUsers::getIdentity(true), $transaction, $hash, $reference);

if (iaTap::RESPONSE_SUCCESS == $response['ResponseMessage']) {
    $referenceId = $response['ReferenceID'];
    $paymentUrl = $response['PaymentURL'];

    iaUtil::go_to($paymentUrl);
} else {
    $messages[] = $response['ResponseMessage'];
}
