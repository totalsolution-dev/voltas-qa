<?php

use Basee\Update\AbstractUpdate;

class Update_1_02_04 extends AbstractUpdate
{
    public function doUpdate()
    {
        $tablePrefix = ee()->db->dbprefix;

        $constraint_exists = ee()->db->query('SELECT * FROM information_schema.table_constraints WHERE constraint_name = "fk_blocks_atomdefinition_blockdefinition" AND table_schema = database()');
        if ($constraint_exists->num_rows() > 0) {
            ee()->db->query('ALTER TABLE '. $tablePrefix .'blocks_atomdefinition DROP FOREIGN KEY fk_blocks_atomdefinition_blockdefinition;');
        }

        $constraint_exists = ee()->db->query('SELECT * FROM information_schema.table_constraints WHERE constraint_name = "fk_blocks_blockdefinition_block" AND table_schema = database()');
        if ($constraint_exists->num_rows() > 0) {
            ee()->db->query('ALTER TABLE '. $tablePrefix .'blocks_block DROP FOREIGN KEY fk_blocks_blockdefinition_block;');
        }

        $constraint_exists = ee()->db->query('SELECT * FROM information_schema.table_constraints WHERE constraint_name = "fk_blocks_atom_block" AND table_schema = database()');
        if ($constraint_exists->num_rows() > 0) {
            ee()->db->query('ALTER TABLE '. $tablePrefix .'blocks_atom DROP FOREIGN KEY fk_blocks_atom_block;');
        }

        $constraint_exists = ee()->db->query('SELECT * FROM information_schema.table_constraints WHERE constraint_name = "fk_blocks_atom_atomdefinition" AND table_schema = database()');
        if ($constraint_exists->num_rows() > 0) {
            ee()->db->query('ALTER TABLE '. $tablePrefix .'blocks_atom DROP FOREIGN KEY fk_blocks_atom_atomdefinition;');
        }

        $constraint_exists = ee()->db->query('SELECT * FROM information_schema.table_constraints WHERE constraint_name = "fk_blocks_blockfieldusage_blockdefinition" AND table_schema = database()');
        if ($constraint_exists->num_rows() > 0) {
            ee()->db->query('ALTER TABLE '. $tablePrefix .'blocks_blockfieldusage DROP FOREIGN KEY fk_blocks_blockfieldusage_blockdefinition;');
        }

        $constraint_exists = ee()->db->query('SELECT * FROM information_schema.columns WHERE table_schema = database() and table_name = "'. $tablePrefix .'blocks_atom" and column_name = "data" and is_nullable = "NO"');
        if ($constraint_exists->num_rows() > 0) {
            ee()->db->query('ALTER TABLE '. $tablePrefix .'blocks_atom MODIFY data longtext;');
        }
    }
}
