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

class Card
{
	public $encryptionKeyId;
	
	public $encryptedData;

    public $number;

    public $type;

    public $expirationDate;

    public $cvx;

    public $ownerBirthdayDate;

    public $password;

    public $cardPresent;

    public $cardholder;

    public $token;
    
    public $paymentData;
}