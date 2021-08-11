<div class="blocksft grid-input-form <?php echo $eeVersion ?>"
     data-field-id="<?php echo $fieldid ?>"
     data-setting-nestable="<?php echo $fieldSettingNestable ?>"
>
    <div class="invalid-tree-message"></div>

    <div id="field_id_<?php echo $fieldid ?>"
         class="blocksft-wrapper grid_field_container <?php echo ($fieldSettingNestable == 'y' ? 'nestable' : 'sortable') ?>--blocksft"><!-- start: blocksft-wrapper -->

        <div class="blocksft-expand-collapse nav-custom">
            <a href="#" class="expand-all" js-expandall>Expand All</a>
            <a href="#" class="collapse-all hidden" js-collapseall>Collapse All</a>
        </div>

        <!-- Existing Bloq Data -->
        <ul class="tbl-list blocksft-blocks<?php if ($showEmpty): ?> blocksft-no-results<?php endif; ?>">
            <?php
            foreach ($bloqs as $blockData) {
                $this->embed('block', array(
                    'blockData' => $blockData
                ));
            }
            ?>
            <?php if ($showEmpty): ?>
            <li class="blocksft-block--no-results">
                <div class="tbl-row no-results">
                    <div class="none">
                        <p>No <b>blocks</b> found. Add your first!</p>
                    </div>
                    <div class="blocksft-insert blocksft-insert--below">
                        <a href="#" class="blocksft-insert--control" js-insert-below>+</a>
                    </div>
                </div>
            </li>
            <?php endif; ?>
        </ul>

        <!-- Add new Bloq Data -->
        <div class="filters hidden blocksft-filters-menu">
            <div class="sub-menu <?php echo ($menuGridDisplay ? 'grid' : '') ?>">
                <?php if ($eeVersionNumber >= 4): ?>
                    <fieldset class="filter-search">
                        <input value="" data-fuzzy-filter="true" placeholder="filter blocks" type="text">
                    </fieldset>
                <?php endif; ?>
                <ul>
                    <?php foreach ($blockdefinitions as $blockdefinition): ?>
                        <?php
                        $previewImage = '';
                        if ($menuGridDisplay && isset($blockdefinition['preview_image']) && $blockdefinition['preview_image'] !== '') {
                            $previewImage = sprintf('<img src="%s" class="blocksft-block-preview-thumb" /><br />', $blockdefinition['preview_image']);
                        }
                        ?>
                        <li><a href="#" js-newblock data-template="<?= $blockdefinition['templateid'] ?>" data-location="bottom"><?php echo $previewImage ?><?= $blockdefinition['name'] ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <input type="hidden" name="field_id_<?php echo $fieldid ?>[tree_order]" class="tree" value="" />
        <input type="hidden" name="version_number" value="<?php echo ee()->input->get('version') ?>" />
        <input type="hidden" name="<?php echo $formSecretFieldName ?>" value="<?php echo $formSecret ?>" />
    </div>

    <!-- Templates for new blocks -->
    <div class="blockDefinitions" data-definitions="<?= $jsonDefinitions ?>"></div>

    <?php foreach( $blockdefinitions as $blockdefinition ): ?>
        <div id="<?= $blockdefinition['templateid'] ?>" style="display:none;" class="">
            <li class="tbl-list-item blocksft-block"
                data-definition-id="<?= $blockdefinition['blockdefinitionid'] ?>"
                data-name="<?= $blockdefinition['name'] ?>"
                data-blocktype="<?= $blockdefinition['shortname'] ?>"
            >
                <div class="blocksft-insert blocksft-insert--above">
                    <a href="#" class="blocksft-insert--control" js-insert-above>+</a>
                </div>
                <div class="blocksft-insert blocksft-insert--below">
                    <a href="#" class="blocksft-insert--control" js-insert-below>+</a>
                </div>

                <div class="tbl-row" data-blockvisibility="expanded" data-base-name="<?= htmlspecialchars($blockdefinition['fieldnames']->baseName) ?>">
                    <div class="reorder blocksft-reorder"></div>

                    <div class="txt">
                        <nav class="blocksft-contextmenu">
                            <a href="#" class="toggle-btn yes_no on" data-state="on" role="switch" alt="on" js-toggle-status>
                                <span class="slider"></span>
                                <span class="option"></span>
                            </a>
                            <a class="blocksft-remove" href="#" js-remove></a>
                        </nav>
                        <div class="blocksft-header">
                            <div class="blocksft-title">
                                <span class="ico sub-arrow" js-toggle-expand></span>
                                <span class="title"><?= $blockdefinition['name'] ?></span>
                                <span class="summary" js-summary></span>
                            </div>
                        </div>

                        <div class="secondary blocksft-content"><!-- start: blocksft-content -->
                        <?php if (!is_null($blockdefinition['instructions']) && $blockdefinition['instructions'] != ''): ?>
                            <div class="blocksft-instructions"><?= $blockdefinition['instructions'] ?></div>
                        <?php endif; ?>

                        <div class="blocksft-atoms"><!-- start: blocksft-atoms -->
                        <?php foreach ($blockdefinition['controls'] as $control): ?>
                            <?php
                                $atomType = $control['atom']->type;

                                if ($atomType === 'file') {
                                    // Add an additional class that the React FileField code is expecting to find,
                                    // otherwise events do not get bound to the correct DOM elements.
                                    $atomType = 'file grid-file-upload';
                                }

                                $blocksft_atom_class = 'blocksft-atom';
                                $blocksft_atom_class .= ' grid-'.$atomType;
                                $blocksft_atom_class .= (isset($control['atom']->settings['col_required']) && $control['atom']->settings['col_required'] == 'y') ? ' required' : '';
                            ?>
                             <div class="<?=$blocksft_atom_class?>" data-fieldtype="<?= $control['atom']->type ?>" data-column-id="<?= $control['atom']->id ?>">
                                <h4 class="blocksft-atom-name"><?= $control['atom']->name ?></h4>

                                <?php if (!is_null($control['atom']->instructions) && $control['atom']->instructions != ''): ?>
                                  <label class="blocksft-atom-instructions"><?= $control['atom']->instructions ?></label>
                                <?php endif; ?>

                                 <div class="blocksft-atomcontainer">
                                     <?php
                                     // Special exception for the new React based drag and drop File field in EE 5.1
                                     if (strpos($control['html'], 'data-file-field-react') !== false) {
                                         echo str_replace('data-file-field-react', 'data-file-field-react-bloqs', $control['html']);
                                     } else {
                                         echo $control['html'];
                                     }
                                     ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div><!-- end: blocksft-atoms -->
                    </div><!-- end: blocksft-content -->

                    </div><!-- end: txt -->

                    <input type="hidden" name="<?= $blockdefinition['fieldnames']->blockdefinitionid ?>" value="<?= $blockdefinition['blockdefinitionid'] ?>">
                    <input type="hidden" data-order-field name="<?= $blockdefinition['fieldnames']->order ?>" value="0">
                    <input type="hidden" name="<?= $blockdefinition['fieldnames']->draft ?>" value="0" class="block-draft">
                </div><!-- end: tbl-row -->
            </li>
        </div><!-- end: blocksft-block -->
    <?php endforeach; ?>

</div><!-- end: blocksft grid-publish -->
