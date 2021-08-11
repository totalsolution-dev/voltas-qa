<?php

use Basee\Update\AbstractUpdate;

class Update_3_00_00 extends AbstractUpdate
{
    public function doUpdate()
    {
        $old_mod_name = ee()->db
            ->select('module_name')
            ->where('module_name', 'Blocks')
            ->get('modules');

        if ($old_mod_name->num_rows() >= 1) {
            ee()->db
                ->where('module_name', 'blocks')
                ->update( 'modules', ['module_name' => 'Bloqs', 'module_version' => '3.0.0']);
        }

        // Update fieldtype name/version
        $old_ft_name = ee()->db
            ->select('name')
            ->where('name', 'blocks')
            ->get('fieldtypes');

        if ($old_ft_name->num_rows() >= 1) {
            ee()->db
                ->where('name', 'blocks')
                ->update( 'fieldtypes', ['name' => 'bloqs', 'version' => '3.0.0']);
        }

        // Update channel fields
        $old_cf_ft = ee()->db
            ->select('field_type')
            ->where('field_type', 'blocks')
            ->get('channel_fields');

        if ($old_cf_ft->num_rows() >= 1) {
            ee()->db
                ->where('field_type', 'blocks')
                ->update( 'channel_fields', ['field_type' => 'bloqs']);
        }
    }
}
