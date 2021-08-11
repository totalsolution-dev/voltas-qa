<?php

use EEBlocks\Controller\ModalController;
use EllisLab\ExpressionEngine\Library\CP\Table;

$table = ee('CP/Table', array('sortable' => false));
$table->setNoResultsText('bloqs_definitions_no_results');

$tbl_cols = array(
    'bloqs_blockdefinitions_name',
    'bloqs_blockdefinitions_shortname',
    'bloqs_blockdefinitions_manage' => array('type' => Table::COL_TOOLBAR),
);
$table->setColumns($tbl_cols);

$rows = array();

$modalController = new ModalController();

foreach( $blockDefinitions as $blockDefinition )
{
    $rows[] = array(
        array(
            'href' => ee('CP/URL')->make('addons/settings/bloqs/blockdefinition', array('blockdefinition' => $blockDefinition->id))->compile(),
            'content' => $blockDefinition->name,
        ),
        array(
            'content' => $blockDefinition->shortname,
        ),
        array(
            'toolbar_items' => array(
                'edit' => array(
                    'href' => ee('CP/URL')->make('addons/settings/bloqs/blockdefinition', array('blockdefinition' => $blockDefinition->id))->compile(),
                    'title' => lang('edit'),
                ),
                'copy' => array(
                    'href'    => '',
                    'title'   => lang('copy'),
                    'class'   => 'm-link',
                    'rel'     => 'modal-confirm-copy',
                    'data-confirm' => 'Block Name: '.$blockDefinition->name,
                    'data-blockdefinition' => $blockDefinition->id,
                ),
                'remove' => array(
                    'href'    => '',
                    'title'   => lang('delete'),
                    'class'   => 'm-link',
                    'rel'     => 'modal-confirm-remove',
                    'data-confirm' => 'Block Name: '.$blockDefinition->name,
                    'data-blockdefinition' => $blockDefinition->id,
                ),
            )
        ),
    );

    $modalController->create('modal-confirm-remove', 'ee:_shared/modal_confirm_remove', array(
        'form_url' => $confirmdelete_url,
        'hidden' => array('blockdefinition' => $blockDefinition->id),
        'checklist' => array(array('kind' => 'Block Name', 'desc' => $blockDefinition->name))
    ));

    $sections = [];

    $sections[0][] = array(
        'title' => 'bloqs_blockdefinition_name',
        'desc' => lang('bloqs_blockdefinition_name_info'),
        'fields' => array(
            'blockdefinition_name' => array(
                'required' => 1,
                'type' => 'text',
                'value' => '',
            )
        )
    );
    $sections[0][] = array(
        'title' => 'bloqs_blockdefinition_shortname',
        'desc' => lang('bloqs_blockdefinition_shortname_info'),
        'fields' => array(
            'blockdefinition_shortname' => array(
                'required' => 1,
                'type' => 'text',
                'value' => '',
            )
        )
    );

    $modalController->create('modal-confirm-copy', 'copy', array(
        'form_url' => $copyblock_url,
        'blockDefinitionId' => $blockDefinition->id,
        'blockName' => $blockDefinition->name,
        'sections' => $sections
    ));

}

$table->setData($rows);

?>

<div class="box bloqs">
    <div class="tbl-ctrls">
        <fieldset class="tbl-search right">
            <a class="btn tn action" href="<?=$blockdefinition_url;?>"><?=lang('bloqs_blockdefinitions_add')?></a>
        </fieldset>
        <h1><?= lang('bloqs_blockdefinitions_title') ?></h1>
        <?=ee('CP/Alert')->getAllInlines()?>
        <?php $this->embed('ee:_shared/table', $table->viewData()); ?>
    </div>
</div>

