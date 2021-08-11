<?php

namespace BoldMinded\Bloqs\Service;

if ( !defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @package     ExpressionEngine
 * @subpackage  Services
 * @category    Bloqs
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2012, 2019 - BoldMinded, LLC
 * @link        http://boldminded.com/add-ons/bloqs
 * @license
 *
 * Copyright (c) 2017. BoldMinded, LLC
 * All rights reserved.
 *
 * This source is commercial software. Use of this software requires a
 * site license for each domain it is used on. Use of this software or any
 * of its source code without express written permission in the form of
 * a purchased commercial or other license is prohibited.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE.
 *
 * As part of the license agreement for this software, all modifications
 * to this source must be submitted to the original author for review and
 * possible inclusion in future releases. No compensation will be provided
 * for patches, although where possible we will attribute each contribution
 * in file revision notes. Submitting such modifications constitutes
 * assignment of copyright to the original author (Brian Litzinger and
 * BoldMinded, LLC) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
 */

class Trial
{
    /**
     * @var string
     */
    private $installedDate;

    /**
     * @var string
     */
    private $messageTitle;

    /**
     * @var string
     */
    private $messageBody;

    /**
     * @var bool
     */
    private $trialEnabled = false;

    /**
     * @return bool
     */
    public function isTrialExpired()
    {
        if ($this->isTrialEnabled() === false) {
            return false;
        }

        $installedDate = $this->getInstalledDate();

        if ($installedDate && $installedDate < strtotime('-30 days')) {
            return true;
        }

        return false;
    }

    public function showTrialExpiredAlert()
    {
        $alert = ee('CP/Alert');
        $alert
            ->makeStandard()
            ->asImportant()
            ->withTitle($this->getMessageTitle())
            ->addToBody($this->getMessageBody())
            ->now();
    }

    public function showTrialExpiredInline()
    {
        return '<div class="alert inline warn">
            <h3>'. $this->getMessageTitle() .'</h3>
            <p>'. $this->getMessageBody() .'</p>
        </div>';
    }

    /**
     * @return mixed
     */
    public function getInstalledDate()
    {
        return $this->installedDate;
    }

    /**
     * @param mixed $installedDate
     */
    public function setInstalledDate($installedDate)
    {
        $this->installedDate = $installedDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessageTitle()
    {
        return $this->messageTitle;
    }

    /**
     * @param string $messageTitle
     */
    public function setMessageTitle($messageTitle)
    {
        $this->messageTitle = $messageTitle;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessageBody()
    {
        return $this->messageBody;
    }

    /**
     * @param string $messageBody
     */
    public function setMessageBody($messageBody)
    {
        $this->messageBody = $messageBody;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTrialEnabled()
    {
        return $this->trialEnabled;
    }

    /**
     * @param bool $trialEnabled
     */
    public function setTrialEnabled($trialEnabled)
    {
        $this->trialEnabled = $trialEnabled;

        return $this;
    }
}
