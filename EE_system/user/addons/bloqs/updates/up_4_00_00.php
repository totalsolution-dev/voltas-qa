<?php

use Basee\Update\AbstractUpdate;

class Update_4_00_00 extends AbstractUpdate
{
    public function doUpdate()
    {
        $db = ee('db');

        if (!$db->field_exists('parent_id', 'blocks_block')) {
            $db->query("ALTER TABLE `" . ee()->db->dbprefix . "blocks_block` ADD `parent_id` int(11) DEFAULT 0 AFTER `order`");
            $db->query("ALTER TABLE `" . ee()->db->dbprefix . "blocks_block` ADD `depth` int(11) DEFAULT 0 AFTER `parent_id`");
            $db->query("ALTER TABLE `" . ee()->db->dbprefix . "blocks_block` ADD `lft` int(11) DEFAULT 0 AFTER `depth`");
            $db->query("ALTER TABLE `" . ee()->db->dbprefix . "blocks_block` ADD `rgt` int(11) DEFAULT 0 AFTER `lft`");
        }

        if (!$db->field_exists('settings', 'blocks_blockdefinition')) {
            $db->query("ALTER TABLE `" . ee()->db->dbprefix . "blocks_blockdefinition` ADD `settings` text DEFAULT NULL AFTER `instructions`");
        }
    }
}
