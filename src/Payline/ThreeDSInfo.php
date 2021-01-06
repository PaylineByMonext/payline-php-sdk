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

class ThreeDSInfo {
    public $challengeInd;

    public $threeDSReqPriorAuthData;

    public $threeDSReqPriorAuthMethod;

    public $threeDSReqPriorAuthTimestamp;

    public $browser;

    public $sdk;

    public $threeDSMethodNotificationURL;

    public $threeDSMethodResult;

    public $challengeWindowSize;
}

