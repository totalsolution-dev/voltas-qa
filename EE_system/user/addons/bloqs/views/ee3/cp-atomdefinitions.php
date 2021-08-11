<div class="grid-wrap" data-group="grid"><!-- grid-wrap -->
    <div class="grid-label">
        <?=form_label(lang('bloqs_blockdefinition_atomdefinition_type'), NULL, array('class' => 'grid_col_type'))?>
        <?=form_label(lang('bloqs_blockdefinition_atomdefinition_name'), NULL, array('class' => ''))?>
        <?=form_label(lang('bloqs_blockdefinition_atomdefinition_shortname'), NULL, array('class' => ''))?>
        <?=form_label(lang('bloqs_blockdefinition_atomdefinition_instructions'), NULL, array('class' => ''))?>
        <?=form_label(lang('bloqs_blockdefinition_atomdefinition_extra'), NULL, array('class' => 'label-data'))?>
        <?=form_label(lang('bloqs_blockdefinition_atomdefinition_settings'), NULL, array('class' => 'grid_col_options'))?>
    </div>

    <div class="grid-clip"><!-- grid-clip -->
        <div class="grid-clip-inner"><!-- grid-clip-inner -->
            <?php foreach ($columns as $column): ?>
                <?=$column?>
            <?php endforeach ?>
        </div> <!-- end: grid-clip-inner -->
    </div><!-- end: grid-clip -->

</div> <!-- end: grid-wrap -->

<div id="grid_col_settings_elements" data-group="always-hidden" class="hidden">
    <?=$blank_col?>

    <?php foreach ($settings_forms as $form): ?>
        <?=$form?>
    <?php endforeach ?>
</div>
