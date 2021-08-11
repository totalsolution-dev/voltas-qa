<?php

use Basee\Update\AbstractUpdate;

class Update_3_01_01 extends AbstractUpdate
{
    public function doUpdate()
    {
        $this->addHooks([
           [
               'hook' => 'after_channel_entry_delete',
               'method' => 'after_channel_entry_delete',
           ]
        ]);
    }
}
