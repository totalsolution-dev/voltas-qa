<?php

use Basee\Update\AbstractUpdate;

class Update_3_03_04 extends AbstractUpdate
{
    public function doUpdate()
    {
        $db = ee('db');
        if ($db->field_exists('site_id', 'blocks_block')) {
            $db->query("ALTER TABLE `" . $db->dbprefix . "blocks_block` DROP COLUMN `site_id`");
        }
    }
}
