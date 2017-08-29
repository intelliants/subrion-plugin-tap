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
