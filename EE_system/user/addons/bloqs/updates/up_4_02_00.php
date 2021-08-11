<?php

use Basee\Update\AbstractUpdate;

class Update_4_02_00 extends AbstractUpdate
{
    public function doUpdate()
    {
        $db = ee('db');

        if (!$db->field_exists('preview_image', 'blocks_blockdefinition')) {
            $db->query("ALTER TABLE `" . ee()->db->dbprefix . "blocks_blockdefinition` ADD `preview_image` text DEFAULT NULL AFTER `instructions`");
        }

        if (!$db->field_exists('group_id', 'blocks_blockdefinition')) {
            $db->query("ALTER TABLE `" . ee()->db->dbprefix . "blocks_blockdefinition` ADD `group_id` int(10) DEFAULT 0 AFTER `id`");
        }
    }
}
