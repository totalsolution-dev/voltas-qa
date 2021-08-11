<?php

/**
 * Accessory for Structure
 *
 * This file must be in your /system/third_party/structure directory of your ExpressionEngine installation
 *
 * @package             Structure for EE2 & EE3
 * @author              EEHarbor <help@eeharbor.com>
 * @copyright           Copyright (c) 2016 EEHarbor
 * @link                http://buildwithstructure.com
 */

use EEHarbor\Structure\FluxCapacitor\FluxCapacitor;

require_once PATH_THIRD.'structure/addon.setup.php';
require_once PATH_THIRD.'structure/mod.structure.php';

class Structure_acc
{
    public $name = 'Structure';
    public $id = 'structure-acc';

    public $description = 'Access your Structure Assets anywhere';
    public $sections = array();

    public $structure;
    public $installed = false;
    public $data = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->flux = new FluxCapacitor;
        $this->version = $this->flux->getConfig('version');

        if (! isset($this->cache['module_id_query'])) {
            $results = ee()->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Structure'");
            $this->cache['module_id_query'] = $results;
        }

        if ($this->cache['module_id_query']->num_rows > 0) {
            $this->installed = true;
        }

        if ($this->installed === false) {
            return;
        }

        $this->structure = new Structure();
    }


    public function set_sections()
    {
        if ($this->installed === false) {
            $this->sections['Not Installed'] = "Structure is not installed.";
        } else {
            $this->sections['Assets'] = $this->get_assets();
        }
    }


    /**
     * Get Assets
     *
     * @access  public
     * @return  string
     */
    public function get_assets()
    {
        $data['theme_url'] = $this->flux->getAddonThemesDir();
        $data['asset_data'] = $this->structure->get_structure_channels('asset');

        if (! is_array($data['asset_data'])) {
            $data['asset_data'] = array();
        }

        $data['foundation'] = $this->flux;

        return $this->flux->view('accessory', $data, true);
    }
}
