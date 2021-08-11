<?php use EEBlocks\Model\Block; ?>

<li class="tbl-list-item blocksft-block
<?php if ($blockData['block']->deleted == 'true'): ?> deleted<?php endif; ?>
<?php if ($blockData['block']->draft === 1): ?> block-draft<?php endif; ?>"
    data-id="<?= $blockData['block']->id ?>"
    data-definition-id="<?= $blockData['block']->definition->getId() ?>"
    data-name="<?= $blockData['block']->definition->getName() ?>"
    data-blocktype="<?= $blockData['block']->definition->getShortName() ?>"
>
    <div class="blocksft-insert blocksft-insert--above">
        <a href="#" class="blocksft-insert--control" js-insert-above>+</a>
    </div>
    <div class="blocksft-insert blocksft-insert--below">
        <a href="#" class="blocksft-insert--control" js-insert-below>+</a>
    </div>

    <div class="tbl-row" data-blockvisibility="<?= $blockData['visibility'] ?>" data-base-name="<?= htmlspecialchars($blockData['fieldnames']->baseName) ?>">
        <input type="hidden" name="<?= $blockData['fieldnames']->draft ?>" value="<?= $blockData['block']->draft ?>" class="block-draft">
        <input type="hidden" name="<?= $blockData['fieldnames']->id ?>" value="<?= $blockData['block']->id ?>">
        <input type="hidden" name="<?= $blockData['fieldnames']->definitionId ?>" value="<?= $blockData['block']->definition->getId() ?>">
        <input type="hidden" data-order-field name="<?= $blockData['fieldnames']->order ?>" value="<?= $blockData['block']->order ?>">

        <?php if (isset($blockData['fieldnames']->deleted)): ?>
            <input type="hidden" data-deleted-field name="<?= $blockData['fieldnames']->deleted ?>" value="<?= $blockData['block']->deleted ?>">
        <?php endif; ?>
        <div class="reorder blocksft-reorder"></div>

        <div class="txt">
            <nav class="blocksft-contextmenu">
                <?php $toggleState = $blockData['block']->draft === 1 ? 'off' : 'on'; ?>
                <a href="#" class="toggle-btn yes_no <?php echo $toggleState ?>" data-state="<?php echo $toggleState ?>" role="switch" alt="<?php echo $toggleState ?>" js-toggle-status>
                    <span class="slider"></span>
                    <span class="option"></span>
                </a>
                <a class="blocksft-remove" href="#" js-remove></a>
            </nav>
            <div class="blocksft-header">
                <div class="blocksft-title">
                    <span class="ico sub-arrow" js-toggle-expand></span>
                    <span class="title"><?= $blockData['block']->definition->name ?></span>
                    <span class="summary" js-summary></span>
                </div>
            </div>

            <div class="secondary blocksft-content"><!-- start: blocksft-content -->
                <?php if (!is_null($blockData['block']->definition->instructions) && $blockData['block']->definition->instructions != ''): ?>
                    <div class="blocksft-instructions"><?= $blockData['block']->definition->instructions ?></div>
                <?php endif; ?>

                <div class="blocksft-atoms"><!-- start: blocksft-atoms -->
                    <?php foreach ($blockData['controls'] as $control): ?>
                        <?php
                        $atomType = $control['atom']->definition->type;

                        if ($atomType === 'file') {
                            // Add an additional class that the React FileField code is expecting to find,
                            // otherwise events do not get bound to the correct DOM elements.
                            $atomType = 'file grid-file-upload';
                        }

                        $blocksft_atom_class = 'blocksft-atom';
                        $blocksft_atom_class .= ' grid-'.$atomType;
                        $blocksft_atom_class .= (isset($control['atom']->error)) ?  ' invalid' : '';
                        $blocksft_atom_class .= (isset($control['atom']->definition->settings['col_required']) && $control['atom']->definition->settings['col_required'] == 'y') ? ' required' : '';
                        ?>

                        <div class="<?=$blocksft_atom_class?>" data-fieldtype="<?= $control['atom']->definition->type ?>" data-column-id="<?= $control['atom']->definition->id  ?>" data-row-id="<?= $blockData['block']->id  ?>">
                            <h4 class="blocksft-atom-name"><?= $control['atom']->definition->name ?></h4>

                            <?php if (!is_null($control['atom']->definition->instructions) && $control['atom']->definition->instructions != ''): ?>
                                <label class="blocksft-atom-instructions"><?= $control['atom']->definition->instructions ?></label>
                            <?php endif; ?>

                            <div class="blocksft-atomcontainer grid-<?= $control['atom']->definition->type ?>">
                                <?php
                                // Special exception for the new React based drag and drop File field in EE 5.1
                                if (strpos($control['html'], 'data-file-field-react') !== false) {
                                    echo str_replace('data-file-field-react', 'data-file-field-react-bloqs', $control['html']);
                                } else {
                                    echo $control['html'];
                                }
                                ?>
                                <div style="clear:both"></div>
                                <?php if (isset($control['atom']->error)): ?>
                                    <em class="blocks-ee-form-error-message"><?= $control['atom']->error ?></em>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div><!-- end: blocksft-atoms -->
            </div><!-- end: blocksft-content -->

        </div><!-- end: txt -->

    </div><!-- end: tbl-row -->

    <ul class="tbl-list">
    <?php
    /** @var Block $block */
    $block = $blockData['block'];
    if ($block->hasChildren()) {
        foreach ($block->children as $child) {
            $this->embed('block', array(
                'blockData' => $child
            ));
        }
    }
    ?>
    </ul>

</li>
