<?php

    include_once 'autoload.php';
    $addonJson = json_decode(file_get_contents(__DIR__ . '/addon.json'));

    return array(
        'name'              => $addonJson->name,
        'version'           => $addonJson->version,
        'description'       => $addonJson->description,
        'namespace'         => $addonJson->namespace,
        'author'            => 'EEHarbor',
        'author_url'        => 'https://eeharbor.com/',
        'docs_url'          => 'http://buildwithstructure.com/documentation',
        'settings_exist'    => true,
        'fieldtypes' => array(
          'structure' => array(
            'name' => 'Structure'
          )
        )
    );
