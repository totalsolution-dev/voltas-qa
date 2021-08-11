<div class="modal-wrap <?php echo $name ?> hidden">
    <div class="modal">
        <div class="col-group">
            <div class="col w-16 last">
                <a class="m-close" href="#"></a>
                <div class="box">
                    <h1>Copy Block Definition</h1>
                    <?php echo form_open($form_url, array(), array(
                        'blockdefinition' => $blockDefinitionId,
                    )); ?>
                    <div class="txt-wrap">
                        <?php
                        foreach($sections as $name => $settings ) {
                            $this->embed('ee:_shared/form/section', array('name' => $name, 'settings' => $settings) );
                        }
                        ?>
                    </div>
                    <fieldset class="form-ctrls">
                        <input class="btn" type="submit" value="Submit">
                    </fieldset>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
