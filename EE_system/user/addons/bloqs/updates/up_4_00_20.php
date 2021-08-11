<?php

use Basee\Update\AbstractUpdate;
use BoldMinded\Bloqs\Service\Setting;

class Update_4_00_20 extends AbstractUpdate
{
    public function doUpdate()
    {
        $this->addHooks([
            [
                'hook' => 'after_channel_entry_save',
                'method' => 'after_channel_entry_save',
                'priority' => 4, // Ensure it fires before Publisher, which is set to 5.
            ]
        ]);
    }
}
