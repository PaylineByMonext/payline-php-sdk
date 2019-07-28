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

class Payment
{

    public $amount;

    public $currency;

    public $action;

    public $mode;

    public $method;

    public $contractNumber;

    public $differedActionDate;

    public $softDescriptor;

    public $cardBrand;

    public $registrationToken;

    public $cumulatedAmount;
}
