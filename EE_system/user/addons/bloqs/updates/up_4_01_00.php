<?php

use Basee\Update\AbstractUpdate;

class Update_4_01_00 extends AbstractUpdate
{
    public function doUpdate()
    {
        $db = ee('db');

        if (!$db->field_exists('draft', 'blocks_block')) {
            $db->query("ALTER TABLE `" . ee()->db->dbprefix . "blocks_block` ADD `draft` int(1) DEFAULT 0 AFTER `parent_id`");
        }
    }
}
