<?php

use Basee\Update\AbstractUpdate;

class Update_1_00_00 extends AbstractUpdate
{
    public function doUpdate()
    {
        $tablePrefix = ee()->db->dbprefix;

        $tables = [
            'tbl_one' => [
                'name' => $tablePrefix.'blocks_blockdefinition',
                'definition' => "CREATE TABLE `{$tablePrefix}blocks_blockdefinition` (
                    `id` bigint(20) NOT NULL AUTO_INCREMENT,
                    `group_id` int(10) DEFAULT  0,
                    `shortname` tinytext NOT NULL,
                    `name` text NOT NULL,
                    `instructions` text,
                    `preview_image` text,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
            ],

            'tbl_two' => [
                'name' => $tablePrefix.'blocks_atomdefinition',
                'definition' => "CREATE TABLE `{$tablePrefix}blocks_atomdefinition` (
                    `id` bigint(20) NOT NULL AUTO_INCREMENT,
                    `blockdefinition_id` bigint(20) NOT NULL,
                    `shortname` tinytext NOT NULL,
                    `name` text NOT NULL,
                    `instructions` text NOT NULL,
                    `order` int(11) NOT NULL,
                    `type` varchar(50) DEFAULT NULL,
                    `settings` text,
                    PRIMARY KEY (`id`),
                    KEY `fk_blocks_atomdefinition_blockdefinition` (`blockdefinition_id`),
                    CONSTRAINT `fk_blocks_atomdefinition_blockdefinition` FOREIGN KEY (`blockdefinition_id`) REFERENCES `{$tablePrefix}blocks_blockdefinition` (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
            ],

            'tbl_three' => [
                'name' => $tablePrefix.'blocks_block',
                'definition' => "CREATE TABLE `{$tablePrefix}blocks_block` (
                    `id` bigint(20) NOT NULL AUTO_INCREMENT,
                    `blockdefinition_id` bigint(20) NOT NULL,
                    `entry_id` int(11) NOT NULL,
                    `field_id` int(6) NOT NULL,
                    `order` int(11) NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `fk_blocks_blockdefinition_block` (`blockdefinition_id`),
                    CONSTRAINT `fk_blocks_blockdefinition_block` FOREIGN KEY (`blockdefinition_id`) REFERENCES `{$tablePrefix}blocks_blockdefinition` (`id`),
                    KEY `ix_blocks_block_siteid_entryid_fieldid` (`entry_id`,`field_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
            ],

            'tbl_four' => [
                'name' => $tablePrefix.'blocks_atom',
                'definition' => "CREATE TABLE `{$tablePrefix}blocks_atom` (
                    `id` bigint(20) NOT NULL AUTO_INCREMENT,
                    `block_id` bigint(20) NOT NULL,
                    `atomdefinition_id` bigint(20) NOT NULL,
                    `data` longtext NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_blocks_atom_blockid_atomdefinitionid` (`block_id`,`atomdefinition_id`),
                    KEY `fk_blocks_atom_block` (`atomdefinition_id`),
                    CONSTRAINT `fk_blocks_atom_block` FOREIGN KEY (`block_id`) REFERENCES `{$tablePrefix}blocks_block` (`id`),
                    CONSTRAINT `fk_blocks_atom_atomdefinition` FOREIGN KEY (`atomdefinition_id`) REFERENCES `{$tablePrefix}blocks_atomdefinition` (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
            ],

            'tbl_five' => [
                'name' => $tablePrefix.'blocks_blockfieldusage',
                'definition' => "CREATE TABLE `{$tablePrefix}blocks_blockfieldusage` (
                    `id` int(20) NOT NULL AUTO_INCREMENT,
                    `field_id` int(6) NOT NULL,
                    `blockdefinition_id` bigint(20) NOT NULL,
                    `order` int(11) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_blocks_blockfieldusage_fieldid_blockdefinitionid` (`field_id`,`blockdefinition_id`),
                    CONSTRAINT `fk_blocks_blockfieldusage_blockdefinition` FOREIGN KEY (`blockdefinition_id`) REFERENCES `{$tablePrefix}blocks_blockdefinition` (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
            ],
        ];

        foreach ($tables as $table) {
            if (!ee()->db->table_exists($table['name'])) {
                ee()->db->query($table['definition']);
            }
        }
    }
}
