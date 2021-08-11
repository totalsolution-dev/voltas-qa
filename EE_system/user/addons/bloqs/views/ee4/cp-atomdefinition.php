<div class="fields-grid-item fields-grid-item---open" data-field-name="<?=$field_name?>">
    <?=$this->embed('grid:grid-col-tools')?>

    <div class="toggle-content">
        <div class="fields-grid-common">

        <fieldset>
            <div class="field-instruct">
                <label><?php echo lang('type'); ?></label>
            </div>
            <div class="field-control">
                <?php echo $this->embed('ee:_shared/form/fields/dropdown', [
                    'choices' => ee('View/Helpers')->normalizedChoices($fieldtypes),
                    'value' => (isset($atomDefinition->type) ? $atomDefinition->type : 'text'),
                    'field_name' => 'grid[cols]['.$field_name.'][col_type]'
                ]); ?>
            </div>
        </fieldset>

        <fieldset class="fieldset-required <?php if( !empty($field_errors['col_label']) ): ?> invalid <?php endif; ?>">
            <div class="field-instruct">
                <label><?php echo lang('name'); ?></label>
            </div>
            <div class="field-control">
                <?=form_input('grid[cols]['.$field_name.'][col_label]', isset($atomDefinition->name) ? $atomDefinition->name : '', ' class="grid_col_field_label"')?>
            </div>
        </fieldset>

        <fieldset class="fieldset-required <?php if( !empty($field_errors['col_name']) ): ?> invalid <?php endif; ?>">
            <div class="field-instruct">
                <label><?php echo lang('field_name'); ?></label>
                <em><i><?php echo lang('alphadash_desc'); ?></i></em>
            </div>
            <div class="field-control">
                <?=form_input('grid[cols]['.$field_name.'][col_name]', isset($atomDefinition->shortname) ? $atomDefinition->shortname : '', ' class="grid_col_field_name"')?></div>
            </div>
        </fieldset>

        <fieldset class="">
            <div class="field-instruct">
                <label><?php echo lang('instructions'); ?></label>
                <em><i><?php echo lang('instructions_desc'); ?></i></em>
            </div>
            <div class="field-control">
                <?=form_input('grid[cols]['.$field_name.'][col_instructions]', isset($atomDefinition->instructions) ? $atomDefinition->instructions : '')?>
            </div>
        </fieldset>

        <fieldset class="">
            <div class="field-instruct">
                <label><?php echo lang('require_field'); ?></label>
                <em><i><?php echo lang('require_field_desc'); ?></i></em>
            </div>
            <div class="field-control">
                <?php
                    echo $this->embed('ee:_shared/form/fields/toggle', [
                    'yes_no' => true,
                    'value' => (isset($atomDefinition->settings['col_required']) && $atomDefinition->settings['col_required'] == 'y' ? 'y' : 'n'),
                    'disabled' => false,
                    'field_name' => 'grid[cols]['.$field_name.'][col_required]'
                ]); ?>
            </div>
        </fieldset>

        <fieldset class="">
            <div class="field-instruct">
                <label><?php echo lang('include_in_search'); ?></label>
                <em><i><?php echo lang('include_in_search_desc'); ?></i></em>
            </div>
            <div class="field-control">
                <?php echo $this->embed('ee:_shared/form/fields/toggle', [
                    'yes_no' => true,
                    'value' => (isset($atomDefinition->settings['col_search']) && $atomDefinition->settings['col_search'] == 'y' ? 'y' : 'n'),
                    'disabled' => false,
                    'field_name' => 'grid[cols]['.$field_name.'][col_search]'
                ]); ?>
            </div>
        </fieldset>

        <div class="grid-col-settings-custom">
            <?php if( isset($settingsForm) ): ?>
                <?=$settingsForm?>
            <?php endif ?>
        </div>
    </div>

</div>
