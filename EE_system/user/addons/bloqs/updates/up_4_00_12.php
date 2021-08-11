<?php

use Basee\Update\AbstractUpdate;
use BoldMinded\Bloqs\Service\Setting;

class Update_4_00_12 extends AbstractUpdate
{
    public function doUpdate()
    {
        $this->addHooks([
            [
                'hook' => 'relationships_query',
                'method' => 'relationships_query',
                'priority' => 4, // Ensure it fires before Publisher, which is set to 5.
            ]
        ]);
    }
}
