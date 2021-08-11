<div class="grid-item" data-field-name="<?=$field_name?>">
    <div class="grid-fields">

        <fieldset class="col-group">
            <div class="setting-field col w-16">
                <?=form_dropdown(
                    'grid[cols]['.$field_name.'][col_type]',
                    $fieldtypes,
                    isset($atomDefinition->type) ? $atomDefinition->type : 'text',
                    'class="grid_col_select"')?>
            </div>
        </fieldset>

        <fieldset class="col-group <?php if( !empty($field_errors['col_label']) ): ?> invalid <?php endif; ?>">
            <div class="setting-field col w-16">
                <?=form_input('grid[cols]['.$field_name.'][col_label]', isset($atomDefinition->name) ? $atomDefinition->name : '', ' class="grid_col_field_label"')?>
            </div>
        </fieldset>

        <fieldset class="col-group <?php if( !empty($field_errors['col_name']) ): ?> invalid <?php endif; ?>">
            <div class="setting-field col w-16">
                <?=form_input('grid[cols]['.$field_name.'][col_name]', isset($atomDefinition->shortname) ? $atomDefinition->shortname : '', ' class="grid_col_field_name"')?>
            </div>
        </fieldset>

        <fieldset class="col-group">
            <div class="setting-field col w-16">
                <?=form_input('grid[cols]['.$field_name.'][col_instructions]', isset($atomDefinition->instructions) ? $atomDefinition->instructions : '')?>
            </div>
        </fieldset>

        <fieldset class="col-group">
            <div class="setting-field col w-16 last">
                <label class="choice block"><?=form_checkbox('grid[cols]['.$field_name.'][col_required]', 'y', isset($atomDefinition->settings['col_required']) && $atomDefinition->settings['col_required'] == 'y')?><?=lang('bloqs_blockdefinition_atomdefinition_extra_required')?></label>
                <label class="choice block"><?=form_checkbox('grid[cols]['.$field_name.'][col_search]', 'y', isset($atomDefinition->settings['col_search']) && $atomDefinition->settings['col_search'] == 'y')?><?=lang('bloqs_blockdefinition_atomdefinition_extra_search')?></label>
            </div>
        </fieldset>


<?php

/* TODO - Sort this out...
        <fieldset class="col-group last<?php if (in_array('grid[cols]['.$field_name.'][col_width]', $error_fields)): ?> invalid<?php endif ?>">

        // We may not actually need this last fieldset element as we're not really doing anything with the col_width attribute

*/

?>
        <fieldset class="col-group last">
            <div class="setting-txt col w-16">
                <h3><?=lang('grid_col_width')?></h3>
                <em><?=lang('grid_col_width_desc')?></em>
            </div>
            <div class="setting-field col w-16 last">
                <?php if (isset($settingsForm)): ?>
                    <?php //echo $settingsForm; ?>
                <?php endif ?>
            </div>
        </fieldset>
    </div>

    <div class="grid-col-settings-custom"><!-- grid-col-settings-custom -->
        <?php if( isset($settingsForm) ): ?>
            <?=$settingsForm?>
        <?php endif ?>
    </div><!-- end: grid-col-settings-custom -->

    <fieldset class="grid-tools">
        <ul class="toolbar">
            <li class="reorder"><a href="" title="<?=lang('grid_reorder_field')?>"></a></li>
            <li class="copy"><a href="" title="<?=lang('grid_copy_field')?>"></a></li>
            <li class="add"><a href="" title="<?=lang('grid_add_field')?>"></a></li>
            <li class="remove"><a href="" title="<?=lang('grid_remove_field')?>"></a></li>
        </ul>
    </fieldset>
</div>
