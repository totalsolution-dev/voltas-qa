<?php

use Basee\Update\AbstractUpdate;
use BoldMinded\Bloqs\Service\Setting;

class Update_4_00_06 extends AbstractUpdate
{
    public function doUpdate()
    {
        /** @var Setting $setting */
        $setting = ee('bloqs:Setting');
        $setting->createTable();
        $setting->save([
            'installed_date' => time(),
            'installed_version' => BLOQS_VERSION,
            'installed_build' => BLOQS_BUILD_VERSION,
        ]);

        $this->addHooks([
            [
                'hook' => 'core_boot',
                'method' => 'core_boot',
            ]
        ]);
    }
}
