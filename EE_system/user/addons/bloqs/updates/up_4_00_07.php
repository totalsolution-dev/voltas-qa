<?php

use Basee\Update\AbstractUpdate;
use BoldMinded\Bloqs\Service\Setting;

class Update_4_00_07 extends AbstractUpdate
{
    public function doUpdate()
    {
        $this->addHooks([
            [
                'hook' => 'after_channel_entry_update',
                'method' => 'after_channel_entry_update',
            ]
        ]);
    }
}
