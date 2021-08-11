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
 * Copyright (c) 2019. BoldMinded, LLC
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

class FormSecret
{
    const KEY_PREFIX = 'bloqsFormSecret_';

    /**
     * @var int
     */
    private $fieldId = 0;

    /**
     * @var string
     */
    private $secret = '';

    public function __construct()
    {
        if (session_id() === '' && REQ === 'CP') {
            @session_start();
        }
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * @param $fieldId
     * @return $this
     */
    public function setSecret(int $fieldId)
    {
        $this->fieldId = $fieldId;
        $this->secret = md5(uniqid(rand(), true));
        $this->setSessionSecret();

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSessionSecret()
    {
        return isset($_SESSION[self::KEY_PREFIX . $this->getFieldId()]) ?
            $_SESSION[self::KEY_PREFIX . $this->getFieldId()] :
            null;
    }

    /**
     * @return void
     */
    private function setSessionSecret()
    {
        $_SESSION[self::KEY_PREFIX . $this->getFieldId()] = $this->secret;
    }

    /**
     * @return string|null
     */
    public function getPostSecret()
    {
        return isset($_POST[self::KEY_PREFIX . $this->getFieldId()]) ?
            $_POST[self::KEY_PREFIX . $this->getFieldId()] :
            null;
    }

    /**
     * @return void
     */
    private function clearSecret()
    {
        unset($_SESSION[self::KEY_PREFIX . $this->getFieldId()]);
    }

    /**
     * @return bool
     */
    public function isSecretValid()
    {
        $postSecret = $this->getPostSecret();
        $sessionSecret = $this->getSessionSecret();

        // Secret is valid only once, so clear it as soon as we validate.
        $this->clearSecret();

        return $postSecret === $sessionSecret;
    }

    /**
     * @return int
     */
    public function getFieldId()
    {
        return $this->fieldId;
    }

    /**
     * @param int $fieldId
     * @return $this
     */
    public function setFieldId(int $fieldId)
    {
        $this->fieldId = $fieldId;

        return $this;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return self::KEY_PREFIX . $this->getFieldId();
    }
}
