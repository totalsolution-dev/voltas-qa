<?php

use Basee\Update\AbstractUpdate;

class Update_3_02_00 extends AbstractUpdate
{
    public function doUpdate()
    {
        $this->addActions([
            [
                'class' => 'Bloqs_mcp',
                'method' => 'fetch_template_code',
            ]
        ]);
    }
}
