<?php

/**
 * @package     ExpressionEngine
 * @subpackage  Extensions
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

namespace EEBlocks\Controller;

/**
 * Provider a filter for which field types are allowed.
 */
class FieldTypeFilter
{
    private $_whitelist;

    function __construct()
    {
        $_whitelist = array();
    }

    public function load($filename)
    {
        $xmlStr = file_get_contents($filename);
        $xml = new \SimpleXMLElement($xmlStr);
        foreach ($xml->whitelist->fieldtype as $fieldtype)
        {
            $name = strval($fieldtype->attributes()->name);
            $version = strval($fieldtype->attributes()->version);
            $adapter = strval($fieldtype->attributes()->adapter);
            $this->_whitelist[$name] = array(
                'version' => $version,
                'adapter' => $adapter
                );
        }
    }

    public function filter($name, $version)
    {
        if (isset($this->_whitelist[$name])) {
            $versionSpec = $this->_whitelist[$name]['version'];
            return $this->testVersion($versionSpec, $version);
        }
        return false;
    }

    private function testVersion($spec, $actual)
    {
        if ($spec == '' || $spec == '*')
        {
            return true;
        }

        $matches = true;
        $specParts = explode(' ', $spec);

        foreach ($specParts as $specPart)
        {
            $specPart = trim($specPart);
            $matches &= $this->testVersionPart($specPart, $actual);
        }

        return $matches;
    }

    private function testVersionPart($specPart, $actual)
    {
        if ($specPart == '' || $specPart == '*')
        {
            return true;
        }

        $matches = array();
        $re = '/^(==|=|eq:|\<\>|!=|ne:|\>=|ge:|\>|gt:|\<=|le:|\<|lt:)(.*)$/';
        if (!preg_match($re, $specPart, $matches))
        {
            throw new \Exception("Unexpected version part: " . $specPart);
        }

        $comparator = $matches[1];
        $version = $matches[2];

        // Remove the trailing ':' from the comparator if necessary.
        if (strpos($comparator, ':'))
        {
            $comparator = substr($comparator, 0, strpos($comparator, ':'));
        }

        return version_compare($actual, $version, $comparator);
    }

    public function adapter($name)
    {
        if (isset($this->_whitelist[$name])) {
            $adapter = $this->_whitelist[$name]['adapter'];
            if ($adapter === '') {
                return null;
            }
            return $adapter;
        }
        return null;
    }
}
