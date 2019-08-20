<?php
/*
 * This file is part of the Payline package.
 *
 * (c) Monext <http://www.monext.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Payline;

class Order
{

    public $ref;

    public $origin;

    public $country;

    public $taxes;

    public $amount;

    public $currency;

    public $date;

    public $quantity;

    public $comment;

    public $details;

    public $deliveryTime;

    public $deliveryMode;

    public $deliveryExpectedDate;

    public $deliveryExpectedDelay;

    public $deliveryCharge;

    public $discountAmount;

    public $otaPackageType;

    public $otaDestinationCountry;

    public $bookingReference;

    public $orderDetail;

    public $orderExtended;

    public $orderOTA;
}
