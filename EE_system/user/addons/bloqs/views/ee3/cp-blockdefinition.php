<?php
// Show "Required Fields" in header if there are any required fields
if ( ! isset($required) || ! is_bool($required))
{
  $required = FALSE;

  foreach ($sections as $name => $settings)
  {
    foreach ($settings as $setting)
    {
      if ( ! is_array($setting))
      {
        continue;
      }

      foreach ($setting['fields'] as $field_name => $field)
      {
        if ($required = (isset($field['required']) && $field['required'] == TRUE))
        {
          break 3;
        }
      }
    }
  }
} 

?>


<div class="box blocks">
	<h1>
		<?=(isset($cp_page_title_alt)) ? $cp_page_title_alt : $cp_page_title?>
		<?php if ($required): ?> 
			<span class="req-title"><?=lang('required_fields')?></span>
		<?php endif ?>
	</h1>

  <?=form_open($post_url, 'class="settings"', $hiddenValues )?>
    <?=ee('CP/Alert')->get('blocks_settings_alert')?>
    <?php 
      if( isset($sections) )
      {
        foreach( $sections as $name => $settings )
        {
          $this->embed('ee:_shared/form/section', array('name' => $name, 'settings' => $settings) );
        }
      }
    ?>

    <h2 data-section-group="blocks" style="display: block;"><?=lang('bloqs_blockdefinition_field_header')?></h2>
    <?=ee('CP/Alert')->get('blocks_block_alert')?>
    <div class="box block-container">
			<?php echo $atomDefinitionsView['body']; ?>
    </div>

    <fieldset class="form-ctrls">
      <?=cp_form_submit($save_btn_text, $save_btn_text_working, NULL, (isset($errors) && $errors->isNotValid()))?>
    </fieldset>
	<?=form_close()?>
</div>