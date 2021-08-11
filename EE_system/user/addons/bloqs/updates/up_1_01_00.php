<?php

use Basee\Update\AbstractUpdate;

class Update_1_01_00 extends AbstractUpdate
{
    public function doUpdate()
    {
        $content_type_exists = ee()->db->select('name')
            ->where('name', 'blocks')
            ->get('content_types');

        if($content_type_exists->num_rows() <= 0)
        {
            ee()->db->insert('content_types', ['name' => 'blocks']);
        }
    }
}
