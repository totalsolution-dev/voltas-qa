<?php

use Basee\Update\AbstractUpdate;

class Update_4_00_18 extends AbstractUpdate
{
    public function doUpdate()
    {
        // Some installs may have the priority set to 6, which was the original value in the 4.0.12 update, and was incorrect.
        ee('db')
            ->where([
                'class' => 'Bloqs_ext',
                'hook' => 'relationships_query'
            ])
            ->update('extensions', [
                'priority' => 4
            ]);
    }
}
