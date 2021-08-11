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

namespace EEBlocks\Model;

class BlockDefinition
{
    // Properties should be marked as private, but for backwards compatibility keeping as public

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $shortname;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $instructions;

    /**
     * @var array
     */
    public $atomDefinitions = [];

    /**
     * @var object
     */
    public $settings;

    /**
     * @var string
     */
    private $previewImage;

    /**
     *
     * _has_unique_shortname
     *
     * @description: method to determine whether or not the value of a shortname is unique
     *
     *   This is used as a form_validation callback method
     *
     * @param value - string - input value
     * @param id - string - id of given BlockDefintion (null if no definition exists)
     *
     * return bool
     *
     * @todo Move this, it should have never been located on the model. It has multiple dependencies
     * and doesn't even use properties from the model.
     *
    **/
    public function hasUniqueShortname($value, $id = null)
    {
        ee()->db->select('shortname')
                 ->where('shortname', $value);

        if( !empty($id) ) {
            ee()->db->where('id !=', $id);
        }

        /** @var \CI_DB_result $block_def_results */
        $block_def_results = ee()->db->get('blocks_blockdefinition');

        /** @var \CI_DB_result $chan_fields_results */
        $chan_fields_results = ee()->db->select('field_name')
                                    ->where('field_name', $value)
                                    ->get('channel_fields');

        if( $block_def_results->num_rows() <= 0 && $chan_fields_results->num_rows <= 0) {
            return true;
        } else {
            ee()->form_validation->set_message(__FUNCTION__, lang('bloqs_blockdefinition_alert_unique'));
            return true;
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getShortName()
    {
        return $this->shortname;
    }

    /**
     * @param string $shortname
     * @return $this
     */
    public function setShortName($shortname)
    {
        $this->shortname = $shortname;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getInstructions()
    {
        return $this->instructions;
    }

    /**
     * @param string $instructions
     * @return $this
     */
    public function setInstructions($instructions)
    {
        $this->instructions = $instructions;

        return $this;
    }

    /**
     * @return array
     */
    public function getAtomDefinitions()
    {
        return $this->atomDefinitions;
    }

    /**
     * @param $shortName
     * @return Atom|null
     */
    public function getAtomDefinition($shortName)
    {
        if (isset($this->atomDefinitions[$shortName])) {
            return $this->atomDefinitions[$shortName];
        }

        return null;
    }

    /**
     * @param array $atomDefinitions
     * @return $this
     */
    public function setAtomDefinitions($atomDefinitions)
    {
        $this->atomDefinitions = $atomDefinitions;

        return $this;
    }

    /**
     * @param $atom
     * @return $this
     */
    public function addAtomDefinition($shortName, $definition)
    {
        $this->atomDefinitions[$shortName] = $definition;

        return $this;
    }

    /**
     * @return object
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param object $settings
     * @return $this
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * @return string
     */
    public function getPreviewImage()
    {
        return $this->previewImage;
    }

    /**
     * @param string $previewImage
     */
    public function setPreviewImage($previewImage)
    {
        $this->previewImage = $previewImage;

        return $this;
    }

    /**
     * @return object
     */
    public function getNestingRules()
    {
        return $this->settings['nesting'];
    }

    /**
     * @param $rule
     * @return null|mixed
     */
    public function getNestingRule($rule)
    {
        $nestingRules = $this->getNestingRules();
        if (isset($nestingRules[$rule])) {
            return $nestingRules[$rule];
        }

        return null;
    }
}
