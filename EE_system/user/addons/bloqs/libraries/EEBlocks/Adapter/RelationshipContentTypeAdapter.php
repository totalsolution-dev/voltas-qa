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

namespace EEBlocks\Adapter;

use \ReflectionObject;

class RelationshipContentTypeAdapter
{
    private $fieldtype;

    public function setFieldtype($fieldtype)
    {
        // Some of the builtin fieldtypes have some things hardcoded when
        // content_type is 'grid'. Since Blocks is pretty darn close to Grid,
        // we want these builtin fieldtypes to treat us like Grid. So, let's
        // lie and say we're Grid.
        //
        // $fieldtype->content_type = 'grid';
        //
        // Unfortunately, content_type is a private variable. So, we need to
        // be even sneakier.
        $refObject = new ReflectionObject($fieldtype);
        $refProperty = $refObject->getProperty('content_type');
        $refProperty->setAccessible(true);
        $refProperty->setValue($fieldtype, 'grid');

        $this->fieldtype = $fieldtype;
    }

    public function display_field($data) {
        // This is a little bit hacky, but here goes. When we call
        // `display_field` on the relationship field type to create a new
        // atom, the relationship field sets a bunch of where clauses on an
        // SQL query. How it generates these where clauses, when it does it in
        // Blocks, ends up pulling in an actual value. So a new block ends up
        // having the value of the most recently created relationship, instead
        // of being blank. If instead we set some dummy default values to 0,
        // that query won't return any values, and the new block will be
        // empty, like it should be.
        if ($this->fieldtype->settings['grid_row_id'] === null) {
            $this->fieldtype->settings['col_id'] = 0;
            $this->fieldtype->settings['grid_field_id'] = 0;
            $this->fieldtype->settings['grid_row_id'] = 0;
            $this->fieldtype->settings['fluid_field_data_id'] = 0;
        }
        return $this->fieldtype->display_field($data);
    }
}
