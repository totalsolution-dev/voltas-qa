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

use Api_channel_fields;
use EEBlocks\Model\Atom;
use EEBlocks\Model\AtomDefinition;
use EEBlocks\Model\FieldType;
use EllisLab\ExpressionEngine\Legacy\Facade;
use \Exception;
use \stdClass;

class FieldTypeManager
{
    /**
     * @var Facade
     */
    private $EE;

    /**
     * @var HookExecutor
     */
    private $_hookExecutor;

    /**
     * @var string
     */
    private $_prefix;

    /**
     * @var FieldTypeFilter
     */
    private $_filter;

    /**
     * @var array
     */
    private $_fieldtypeArray = [];

    /**
     * @param $ee
     * @param $filter
     * @param $hookExecutor
     */
    function __construct($ee, $filter, $hookExecutor) {
        $this->EE = $ee;
        $this->_prefix = 'blocks';
        $this->_filter = $filter;
        $this->_hookExecutor = $hookExecutor;
    }


    /**
     * @param $fieldtype
     * @return \EEBlocks\Controller\FieldTypePackageLoader
     */
    private function getFieldTypePackageLoader($fieldtype)
    {
        $fieldtypeApi = $this->EE->api_channel_fields;
        $_ftPath = $fieldtypeApi->ft_paths[$fieldtype];

        return new FieldTypePackageLoader($this->EE, $_ftPath);
    }

    /**
     * @param AtomDefinition $atomDefinition
     * @param null $rowName
     * @param null $blockId
     * @param int $fieldId
     * @param int $entryId
     * @return \EEBlocks\Controller\FieldTypeWrapper|null
     * @throws Exception
     */
    public function instantiateFieldtype(
        AtomDefinition $atomDefinition,
        $rowName = null,
        $blockId = null,
        $fieldId = 0,
        $entryId = 0
    )
    {
        $fieldtypeArray = $this->buildFieldTypeArray();
        /** @var FieldType $fieldtypeObject */
        $fieldtypeObject = $this->findFieldTypeInArray($fieldtypeArray, $atomDefinition->getType());

        if (is_null($fieldtypeObject)) {
            return null;
        }

        // Instantiate fieldtype
        $fieldtype = $this->EE->api_channel_fields->setup_handler($fieldtypeObject->getType(), true);

        if (!$fieldtype) {
            return null;
        }

        FieldTypeWrapper::initializeFieldtype(
            $fieldtype,
            $atomDefinition,
            $rowName,
            $blockId,
            $fieldId,
            $entryId
        );

        $ftpl = $this->getFieldTypePackageLoader($atomDefinition->type);
        $fta = null;

        if ($fieldtypeObject->getAdapter()) {
            $fta = $fieldtypeObject->getAdapter();

            if (is_callable(array($fta, 'setFieldtype'))) {
                call_user_func(array($fta, 'setFieldtype'), $fieldtype);
            }
        }

        $ftw = new FieldTypeWrapper($fieldtype, $ftpl, $fta);

        if ($ftw->getContentType() === 'none') {
            throw new Exception("Specified fieldtype '{$atomDefinition->getType()}' does not support blocks");
        }

        return $ftw;
    }

    /**
     * @param $array
     * @param $type
     * @return null
     */
    private function findFieldTypeInArray($array, $type)
    {
        /** @var FieldType $fieldtype */
        foreach ($array as $fieldtype) {
            if ($fieldtype->getType() === $type) {
                return $fieldtype;
            }
        }

        return null;
    }

    /**
     * @return array
     */
    public function getFieldTypes()
    {
        return $this->buildFieldTypeArray();
    }

    /**
     * @param $name
     * @return null
     */
    private function instantiateAdapter($name)
    {
        $adapterName = null;

        if (!is_null($this->_filter)) {
            $adapterName = $this->_filter->adapter($name);
        }

        if (is_null($adapterName)) {
            return null;
        }

        try {
            return new $adapterName();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @return array
     */
    private function buildFieldTypeArray()
    {
        if (!empty($this->_fieldtypeArray)) {
            return $this->_fieldtypeArray;
        }

        $this->EE->load->library('api');
        $this->EE->legacy_api->instantiate('channel_fields');

        /** @var Api_channel_fields $fieldtypeApi */
        $fieldtypeApi = $this->EE->api_channel_fields;
        $fieldtypes = $fieldtypeApi->fetch_installed_fieldtypes();

        // For some reason, calling setup_handler on blocks makes it so that
        // the module can't load any views. So, don't let setup_handler be
        // called on blocks.
        unset($fieldtypes['blocks']);

        $fieldtypeArray = [];

        foreach ($fieldtypes as $fieldName => $data) {
            $fieldtype = $fieldtypeApi->setup_handler($fieldName, true);
            $ftpl = $this->getFieldTypePackageLoader($fieldName);
            $ftw = new FieldTypeWrapper($fieldtype, $ftpl, null);

            if (!$ftw->supportsGrid()) {
                continue;
            }

            if (!$ftw->supportsBlocks()) {
                // It doesn't support Blocks. But don't be too hasty; maybe it's in the whitelist.
                if (is_null($this->_filter) || !$this->_filter->filter($fieldName, $fieldtypes[$fieldName]['version'])) {
                    // OK, the whitelist didn't like it, either.
                    continue;
                }
            }

            $fieldType = new FieldType();
            $fieldType->setType($fieldName);
            $fieldType->setVersion($fieldtypes[$fieldName]['version']);
            $fieldType->setAdapter($this->instantiateAdapter($fieldName));
            $fieldType->setName($fieldtypes[$fieldName]['name']);

            $fieldtypeArray[] = $fieldType;
        }

        $fieldtypeArray = $this->_hookExecutor->discoverFieldtypes($fieldtypeArray);

        usort($fieldtypeArray, function($a, $b) {
            /**
             * @var FieldType $a
             * @var FieldType $b
             */
            if ($a->getName() < $b->getName()) {
                return -1;
            } else if ($a->getName() == $b->getName()) {
                return 0;
            } else {
                return 1;
            }
        });

        $this->_fieldtypeArray = $fieldtypeArray;

        return $this->_fieldtypeArray;
    }
}
