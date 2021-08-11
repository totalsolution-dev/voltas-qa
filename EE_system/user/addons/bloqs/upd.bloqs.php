<?php

use Basee\Updater;
use BoldMinded\Bloqs\Service\Setting;

if (!defined('BASEPATH')) exit('No direct script access allowed');

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

class Bloqs_upd EXTENDS Bloqs_base
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return boolean
     */
    public function install()
    {
        $ee2_blocks = ee('Model')->get('Module')
            ->filter('module_name', '==', 'Blocks')
            ->first();

        $mod_data = array(
            'module_name' => $this->class_name,
            'module_version' => $this->version,
            'has_cp_backend' => 'y',
            'has_publish_fields' => 'n'
        );

        //
        // The addon name change we implemented (blocks to bloqs)
        //    has caused a bit of a bind during EE2 to EE3 updates
        //    we need to revisit this issue, but we're going to implement
        //    a quick work around for now..
        //
        if (empty($ee2_blocks)) {
            ee()->db->insert('modules', $mod_data);
            $this->update();
        } else {
            ee()->db->update('modules', $mod_data, "module_name = 'Blocks'");
            $this->update();
        }

        ee()->load->dbforge();

        /** @var Setting $setting */
        $setting = ee('bloqs:Setting');
        $setting->createTable();
        $setting->save([
            'installed_date' => time(),
            'installed_version' => BLOQS_VERSION,
            'installed_build' => BLOQS_BUILD_VERSION,
        ]);

        return true;
    }

    /**
     * Uninstall
     * @return boolean
     */
    public function uninstall()
    {
        ee()->load->dbforge();

        // remove row from exp_modules
        ee()->db->delete('modules', array('module_name' => $this->class_name));

        $tablePrefix = ee()->db->dbprefix;

        // remove bloq's specific tables
        ee()->db->query("DROP TABLE IF EXISTS {$tablePrefix}blocks_blockfieldusage");
        ee()->db->query("DROP TABLE IF EXISTS {$tablePrefix}blocks_atom");
        ee()->db->query("DROP TABLE IF EXISTS {$tablePrefix}blocks_block");
        ee()->db->query("DROP TABLE IF EXISTS {$tablePrefix}blocks_atomdefinition");
        ee()->db->query("DROP TABLE IF EXISTS {$tablePrefix}blocks_blockdefinition");

        // un-register the field type / content type
        ee()->db->query("DELETE FROM {$tablePrefix}content_types WHERE name = 'blocks'");

        /** @var Setting $setting */
        $setting = ee('bloqs:Setting');
        $setting->dropTable();

        return true;
    }

    /**
     * @return boolean
     */
    public function update($current = '')
    {
        ee()->load->dbforge();

        $updater = new Updater();
        $updater
            ->setFilePath(PATH_THIRD.'bloqs/updates')
            ->setHookTemplate([
                'class' => 'Bloqs_ext',
                'settings' => '',
                'priority' => 5,
                'version' => BLOQS_VERSION,
                'enabled' => 'y',
            ])
            ->fetchUpdates($current)
            ->runUpdates();

        ee()->db->update('fieldtypes', array('version' => $this->version), "name = '{$this->pkg}'");

        return true;
    }
}
