<?php


$lang = array(

// -------------------------------------------
//  Module CP
// -------------------------------------------

    'bloqs_module_name' => 'Bloqs',
    'bloqs_module_description' => 'A modular content add-on for ExpressionEngine 3',

    'bloqs_fieldsettings_associateblocks' => 'Block types',
    'bloqs_fieldsettings_associateblocks_desc' => 'Select the types of blocks to use for this field',
    'bloqs_fieldsettings_noblocksdefined' => 'No block types have been defined. Block types must be defined before being associated with a field.',
    'bloqs_fieldsettings_manageblockdefinitions' => 'Edit Block Types',
    'bloqs_fieldsettings_template_code' => 'Template Code',
    'bloqs_fieldsettings_template_code_desc' => 'This is a basic example of template code necessary to render this field. Note that it is only meant as a starting point. You will probably need to modify this to meet your project needs.',

    'bloqs_fieldsettings_auto_expand' => 'Auto Expand?',
    'bloqs_fieldsettings_auto_expand_desc' => 'You may have all blocks display in expanded mode when editing an entry.',

    'bloqs_fieldsettings_menu_grid_display' => 'Display add block menu as grid?',
    'bloqs_fieldsettings_menu_grid_display_desc' => 'Display the add block menu options as grid, otherwise the options will be displayed vertically in a list (this is the default behavior). Grid display generally works best when you assign preview images or icons to the block.',

    'bloqs_fieldsettings_nestable' => 'Nestable?',
    'bloqs_fieldsettings_nestable_desc' => 'Should this field allow nestable blocks?',

    'bloqs_blockdefinitions_title' => 'Block Types',
    'bloqs_blockdefinitions_name' => 'Block Type',
    'bloqs_blockdefinitions_shortname' => 'Short Name',
    'bloqs_blockdefinitions_manage' => 'Manage',
    'bloqs_blockdefinitions_edit' => 'Edit',
    'bloqs_blockdefinitions_clone' => 'Clone',
    'bloqs_blockdefinitions_delete' => 'Delete',
    'bloqs_blockdefinitions_add' => 'Create new Block',
    'bloqs_definitions_no_results' => 'No blocks exist.  Create your first block to get started.',

    'bloqs_module_home' => 'Bloqs Configuration',

    'bloqs_blockdefinition_title' => '', // Not used; use the name of the block
                                          // definition instead
    //Bloqs Definition View
    'bloqs_blockdefinition_settings' => 'Block Settings',
    'bloqs_blockdefinition_name' => 'Name',
    'bloqs_blockdefinition_name_info' => 'This is the name that will appear in the PUBLISH page',
    'bloqs_blockdefinition_name_note' => '',
    'bloqs_blockdefinition_shortname' => 'Short Name',
    'bloqs_blockdefinition_shortname_info' => 'Single word, no spaces. Underscores and dashes allowed',
    'bloqs_blockdefinition_shortname_note' => '',
    'bloqs_blockdefinition_shortname_invalid' => 'The shortname must be a single word with no spaces. Underscores and dashes are allowed.',
    'bloqs_blockdefinition_shortname_inuse' => 'The shortname provided is already in use.',
    'bloqs_blockdefinition_field_header' => 'Atoms (fields)',
    'bloqs_blockdefinition_instructions' => 'Instructions',
    'bloqs_blockdefinition_instructions_info' => 'Instructions for authors on how or what to enter into this field when submitting an entry.',
    'bloqs_blockdefinition_instructions_note' => '',
    'bloqs_blockdefinition_preview_image' => 'Preview Image',
    'bloqs_blockdefinition_preview_image_info' => 'Add a small image or icon to help content editors visualize the block. Image will be displayed at 50x50 pixels.',
    'bloqs_blockdefinition_submit' => 'Save',
    'bloqs_blockdefinition_alert_title' => 'Cannot Create Field',
    'bloqs_blockdefinition_alert_message' => 'We were unable to create this field, please review and fix errors below.',
    'bloqs_blockdefinition_alert_unique' => 'Block shortname cannot match other blocks or channel field shortnames',

    'bloqs_blockdefinition_nestable_section' => 'Nesting Options',
    'bloqs_blockdefinition_nesting_root' => 'Nesting Restrictions',
    'bloqs_blockdefinition_nesting_root_info' => 'Choose some basic nesting restrictions for this block.',
    'bloqs_blockdefinition_nesting_no_children' => 'Can have children?',
    'bloqs_blockdefinition_nesting_no_children_info' => 'This block can have child blocks, regardless of its nesting level.',
    'bloqs_blockdefinition_nesting_child_of' => 'Parents',
    'bloqs_blockdefinition_nesting_child_of_info' => 'This block can only be a child of the selected blocks. If no blocks are selected, then it can be a child of any block. A block can not be a child of another block and designated as a root block at the same time.',
    'bloqs_blockdefinition_nesting_description' => 'If this block is added to a nestable field you can set its nesting rules below. If the block was added to a non-nestable field then these rules will not apply. These rules are global, thus they apply to any nestable field they are assigned to.',

    'bloqs_blockdefinition_atomdefinition_type' => 'Type',
    'bloqs_blockdefinition_atomdefinition_name' => 'Name',
    'bloqs_blockdefinition_atomdefinition_shortname' => 'Short Name',
    'bloqs_blockdefinition_atomdefinition_instructions' => 'Instructions',
    'bloqs_blockdefinition_atomdefinition_extra' => 'Is this data...',
    'bloqs_blockdefinition_atomdefinition_extra_required' => 'Required?',
    'bloqs_blockdefinition_atomdefinition_extra_search' => 'Searchable?',
    'bloqs_blockdefinition_atomdefinition_settings' => 'Settings',
    'bloqs_blockdefinition_atomdefinition_alert_title' => 'Invaild Block Configuration',
    'bloqs_blockdefinition_atomdefinition_alert_message' => 'An error was encountered. Please review and fix the issues highlighted below.',

    'bloqs_confirmdelete_title' => 'Delete Block Definition',
    'bloqs_confirmdelete_content' => 'Are you sure you want to permanently delete this Block Definition?',
    'bloqs_confirmdelete_submit' => 'Delete',

    'bloqs_validation_error' => 'There was an error in one or more blocks',
    'bloqs_field_required' => 'This field is required',
    'bloqs_field_shortname_not_unique' => 'Short name cannot match name of an existing block',

    'bloqs_nesting_error_no_close_tags' => 'An error occurred a nested Bloq field, probably because you forgot to add the {close:[block_name]}{/close:[block_name]} tag pair. <a href="https://eebloqs.com/documentation/nesting">Please refer to the documentation</a>.',

    'bloqs_license' => 'License',
    'bloqs_license_name' => 'License Key',
    'bloqs_license_desc' => 'Enter your license key from boldminded.com, or the expressionengine.com store. If you purchased from expressionengine.com you need to <a href="https://boldminded.com/claim">claim your license</a>.',
);
