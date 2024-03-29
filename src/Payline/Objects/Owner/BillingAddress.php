<?php
/*
 * This file is part of the Payline package.
 *
 * (c) Monext <http://www.monext.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Payline\Objects\Owner;


/**
 * Type addressOwner
 * @see https://docs.payline.com/display/DT/Object+-+owner
 */
class BillingAddress
{
    public $street;

    public $cityName;

    public $zipCode;

    public $country;

    public $phone;
}
