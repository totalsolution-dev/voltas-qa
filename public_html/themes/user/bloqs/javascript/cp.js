;jQuery(function ($) {

    var summarizers = {
        'text': function ($atomContainer) {
            return $atomContainer.find('input').val();
        },
        'date': function ($atomContainer) {
            return $atomContainer.find('input').val();
        },
        'email_address': function ($atomContainer) {
            return $atomContainer.find('input').val();
        },
        'url': function ($atomContainer) {
            return $atomContainer.find('input').val();
        },
        'wygwam': function ($atomContainer) {
            return $atomContainer.find('textarea').first().val();
        },
        'file': function ($atomContainer) {
            // Account for EE4 and EE3 File class names
            return $atomContainer.find('.fields-upload-chosen-file img, .file-chosen img').attr('alt');
        },
        'relationship': function ($atomContainer) {
            var relateFields = $atomContainer.find('.fields-relate');
            var isMultiselect = $atomContainer.find('.fields-relate-multi').length;
            if (isMultiselect) {
                // If multi...
                var text = [];
                relateFields.find('.ui-sortable').each(function () {
                    $(this).find('label').each(function () {
                        // Strip the channel name...
                        var relatedEntryTitle = $(this).html().replace(/<i>(.*?)<\/i>/g, '');
                        // Now strip all the other html...
                        relatedEntryTitle = $(relatedEntryTitle).text().trim();

                        text.push(relatedEntryTitle);
                    });
                });

                return text.join(', ');
            }
            else {
                // If single...
                return $atomContainer.find('.field-input-selected').text();
            }
        },
        'textarea': function ($atomContainer) {
            return $atomContainer.find('textarea').val();
        },
        'rte': function ($atomContainer) {
            return $atomContainer.find('textarea').val();
        }
    };

    var nestableFieldOptions = {
        rootClass: 'nestable--blocksft',
        listNodeName: 'ul',
        listClass: 'tbl-list',
        itemNodeName: 'li',
        itemClass: 'tbl-list-item',
        dragClass: 'drag-tbl-row tbl-list-item',
        handleClass: 'reorder',
        placeClass: 'drag-placeholder__nestable',
        expandBtnHTML: '',
        collapseBtnHTML: '',
        maxDepth: 10
    };

    $(window).resize(function () {
        $('.blocksft-insert--control').trigger('blocks.resize');
    });

    $(document).keyup(function (e) {
        if (e.key === "Escape") {
            hideAllFilterMenus();
        }
    });

    var $bloqsContext = null;

    var showFilterMenu = function ($control, $filterMenu, location) {
        hideAllFilterMenus();
        // Since we're using the same menu and moving it around the page, update the current context.
        $bloqsContext = $control.closest('.blocksft-block');

        if ($bloqsContext.length === 0) {
            $bloqsContext = $control.closest('.blocksft-block--no-results');
        }

        $control
            .addClass('visible')
            .on('blocks.resize', function () {
                $filterMenu.offset(getPosition($control, $filterMenu.find('.sub-menu')));
            })
        ;

        $filterMenu
            .find('[data-location]')
            .attr('data-location', location)
        ;

        $filterMenu
            .removeClass('hidden')
            .addClass('visible')
            .show()
            .offset(getPosition($control, $filterMenu.find('.sub-menu')))
        ;
    };

    /**
     * @param originElement Element to get origin
     * @param targetElement Element we're setting position of in relation to the origin
     * @returns {{top: *, left: number}}
     */
    var getPosition = function (originElement, targetElement) {
        var width = targetElement.width();
        var originPos = originElement.offset();

        return {
            left: (originPos.left - (width / 2)) + 12, // 12 is half the width of the + control
            top: originPos.top + 4
        };
    };

    var filterMenuHandler = function(event) {
        // if the target is a descendent of container do nothing
        if ($(event.target).is('.blocksft-insert--control, .blocksft-filters-menu *')) return;

        hideAllFilterMenus();
    };

    var hideAllFilterMenus = function () {
        $('.blocksft-filters-menu')
            .addClass('hidden')
            .removeClass('visible')
        ;

        $('.blocksft-insert--control')
            .removeClass('visible')
        ;
    };

    $(document).on("click", filterMenuHandler);

    $('.blocksft').each(function () {

        var $blocksFieldType = $(this);
        var blocksFieldId = $blocksFieldType.data('field-id');
        var $blocks = $blocksFieldType.find('.blocksft-blocks');
        var newBlockCount = 1;
        var $filterMenu = $('.blocksft-filters-menu');

        $blocks.on('click', '[js-insert-above]', function (e) {
            e.preventDefault();
            showFilterMenu($(this), $filterMenu, 'above');
        });

        $blocks.on('click', '[js-insert-below]', function (e) {
            e.preventDefault();
            showFilterMenu($(this), $filterMenu, 'below');
        });

        $blocksFieldType.on('click', '[js-newblock]', function (e) {
            e.preventDefault();
            var newButton = $(this);
            var templateId = newButton.attr('data-template');
            var location = newButton.attr('data-location');

            createBlock($blocksFieldType, $blocks, templateId, location, $bloqsContext, blocksFieldId, newBlockCount);

            newBlockCount++;
        });

        fireEvent("display", $blocks.find('[data-fieldtype]'));

        $blocksFieldType.closest('fieldset').find('.field-instruct').addClass('field-instruct--blocksft');

        //callback for ajax form validation - we'll need to customize this process a bit so it works with bloqs
        if (blocksFieldId != '') {
            EE.cp && void 0 !== EE.cp.formValidation.bindCallbackForField && EE.cp.formValidation.bindCallbackForField('field_id_' + blocksFieldId, _bloqsValidationCallback);
        }

        $.each($blocks.find('.blocksft-block'), function () {
            if ($(this).find('.blocksft-atom[data-fieldtype="checkboxes"]').length > 0) {
                EE.cp && void 0 !== EE.cp.formValidation.bindCallbackForField && EE.cp.formValidation.bindCallbackForField('field_id_' + blocksFieldId + '[]', _bloqsCheckboxValidationCallback);
            }
        });

        var isNestable = $blocksFieldType.data('setting-nestable');

        if (isNestable !== 'y') {
            $blocksContainer = $blocksFieldType.find('.sortable--blocksft');
            $blocksContainer.sortable({
                axis: 'y',  // Only allow vertical dragging
                handle: '.blocksft-reorder', // Set drag handle
                items: '.tbl-list-item', // Only allow these to be sortable
                sort: EE.sortable_sort_helper,
                forcePlaceholderSize: true,
                placeholder: {
                    element: function (currentItem) {
                        var height = currentItem.height() - 4;
                        return $('<li><div class="tbl-list-item drag-placeholder"><div class="none" style="height: ' + height + 'px"></div></div></li>')[0];
                    },
                    update: function (container, p) {
                        return;
                    }
                },
                start: function (event, ui) {
                    var block = $(ui.item);

                    if (EE.bloqs.collapseOnDrag === true) {
                        // Dragging and sorting expanded blocks is awkward
                        $blocks.find('.blocksft-block').each(function () {
                            collapseBlock($(this));
                        });
                    }

                    block.find('[data-fieldtype]').each(function () {
                        fireEvent('beforeSort', $(this));
                    });
                },
                stop: function (event, ui) {
                    var block = $(ui.item);
                    block.removeAttr('style'); // sorting adds inline styles, which mess up the <li> positioning the 2nd time a block has been sorted.
                    block.find('[data-fieldtype]').each(function () {
                        fireEvent('afterSort', $(this));
                    });
                },
                update: function (event, ui) {
                    reorderFields($blocksFieldType);
                }
            });
        } else {
            $blocksContainer = $blocksFieldType.find('.nestable--blocksft');
            nestableFieldOptions = $.extend(nestableFieldOptions, {
                onDragStart: function (container, element) {
                    var block = $(element);

                    if (EE.bloqs.collapseOnDrag === true) {
                        // Dragging and sorting expanded blocks is awkward
                        $blocks.find('.blocksft-block').each(function () {
                            collapseBlock($(this));
                        });
                    }

                    block.find('[data-fieldtype]').each(function () {
                        fireEvent('beforeSort', $(this));
                    });

                    $blocksContainer.data('draggedItem', block);
                }
            });

            $blocksContainer.nestable(nestableFieldOptions).on('change', function (event) {
                var $element = $(event.target);
                // If a text field inside of a block is updated this on change event will trigger,
                // but we only want to take action if a block position moved, so make sure the changed
                // element is a block, not a child field.
                if ($element.hasClass('nestable--blocksft')) {
                    var block = $blocksContainer.data().draggedItem;
                    if (block) {
                        block.find('[data-fieldtype]').each(function () {
                            fireEvent('afterSort', $(this));
                        });
                        updateTreeField($element, block);
                    }
                }
            });

            // Set the tree data on first load based on the current nesting.
            // Calling this now wasn't necessary until Live Preview came around.
            updateTreeField($blocksContainer);

            // Update the tree data again on form submit to ensure the latest tree data,
            // if modified since loading, is submitted.
            $blocksFieldType.closest('form').on('submit', function () {
                updateTreeField($blocksContainer);
            });
        }

        $blocks.on('click', '[js-remove]', function (e) {
            e.preventDefault();
            var button = $(this);
            var block = button.closest('.blocksft-block');
            var deletedInput = block.find('[data-deleted-field]');

            if (deletedInput.length) {
                deletedInput.val('true');
                block.addClass('deleted');
                clearErrorsOnBlock(block);
                block.find('[data-order-field]').remove();
            } else {
                clearErrorsOnBlock(block);
                block.remove();
            }

            reorderFields($blocksFieldType);

            if ($blocksFieldType.find('.blocksft-blocks li:not(.blocksft-block--no-results)').length === 0) {
                $blocksFieldType.find('.blocksft-block--no-results').show();
            }
        });

        $blocks.on('click', '[js-nextstep]', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var button = $(this);
            var multistep = button.closest('.multistep');
            var current = parseInt(multistep.attr('data-currentstep'), 10) || 1;
            multistep.attr('data-currentstep', current + 1);
        });

        $blocks.on('click', '[js-previousstep]', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var button = $(this);
            var multistep = button.closest('.multistep');
            var current = parseInt(multistep.attr('data-currentstep'), 10) || 1;
            multistep.attr('data-currentstep', current - 1);
        });

        $blocks.on('click', '[js-toggle-expand]', function (e) {
            e.preventDefault();
            var $button = $(this);
            var $block = $button.closest('.blocksft-block');
            var visibility = $block.find('> .tbl-row').attr('data-blockvisibility');

            if (visibility === 'expanded') {
                collapseBlock($block);
            } else {
                expandBlock($block);
            }
        });

        $blocks.on('mousedown', '.blocksft-header', function (e) {
            // Don't prevent default on the drag handle.
            if ($(e.target).is('.blocksft-block-handle')) {
                return;
            }

            // Prevent default so we don't highlight a bunch of stuff when double-clicking.
            e.preventDefault();
        });

        $blocks.on('dblclick', '.blocksft-header', function (e) {
            var $block = $(this).closest('.blocksft-block');
            var visibility = $block.find('> .tbl-row').attr('data-blockvisibility');

            if (visibility === 'expanded') {
                collapseBlock($block);
            } else {
                expandBlock($block);
            }
        });

        $blocksFieldType.on('click', '[js-expandall]', function (e) {
            e.preventDefault();
            $blocks.find('.blocksft-block').each(function () {
                expandBlock($(this));
            });
            $(this).addClass('hidden');
            $(this).next('.collapse-all').removeClass('hidden');
        });

        $blocksFieldType.on('click', '[js-collapseall]', function (e) {
            e.preventDefault();
            $blocks.find('.blocksft-block').each(function () {
                collapseBlock($(this));
            });
            $(this).addClass('hidden');
            $(this).prev('.expand-all').removeClass('hidden');
        });

        $blocks.on('click', '[js-toggle-status]', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var $block = $(this).closest('.blocksft-block');
            var isOn = $btn.hasClass('on');

            if (isOn) {
                setBlockDraft($block);
            } else {
                setBlockLive($block);
            }
        });

        $blocks.find('.blocksft-block').each(function () {
            var block = $(this);
            summarizeBlock(block);

            // Special exception for the new React based drag and drop File field in EE 5.1
            block.find('div[data-file-field-react-bloqs]').each(function () {
                var $field = $(this);

                $field
                    .attr('data-file-field-react', $field.attr('data-file-field-react-bloqs'))
                    .removeAttr('data-file-field-react-bloqs');

                // Call native EE function to re-instantiate a new instance of the React field.
                setupFileField($field.closest('.blocksft-atomcontainer'), false);
            });
        });

    }); // $('.blocksft').each

    /**
     * @param $element
     * @param block
     */
    function updateTreeField($element, block) {
        var $treeField = $element.find('.tree');
        var nestedSet = asNestedSet($element);
        var blockDefinitions = $element.closest('.blocksft').find('.blockDefinitions').data('definitions');

        $treeField.val(JSON.stringify(nestedSet));

        // Only nestable fields will pass the blockId
        if (block) {
            validateTreeField(block, blockDefinitions, nestedSet);
        }
    }

    function validateTreeField(block, blockDefinitions, nestedSet) {
        var treeValidation = new TreeValidation(block, blockDefinitions, nestedSet);
        treeValidation.validate();
    }

    /**
     * Lifted from https://github.com/RamonSmit/Nestable2 and slightly modified
     *
     * @param list
     * @returns {Array}
     */
    function asNestedSet(list) {
        var o = nestableFieldOptions, depth = -1, ret = [], lft = 1;
        var items = list.find(o.listNodeName).first().children(o.itemNodeName);

        items.each(function () {
            lft = traverse(this, depth + 1, lft);
        });

        ret = ret.sort(function(a,b){ return (a.lft - b.lft); });
        return ret;

        function traverse(item, depth, lft) {
            var rgt = lft + 1,
                id,
                parentId,
                definitionId,
                parentDefinitionId,
                name
            ;

            if ($(item).children(o.listNodeName).children(o.itemNodeName).length > 0 ) {
                depth++;
                $(item).children(o.listNodeName).children(o.itemNodeName).each(function () {
                    rgt = traverse($(this), depth, rgt);
                });
                depth--;
            }

            id = $(item).attr('data-id');
            if (!isNaN(id)) {
                id = parseInt(id);
            }

            parentId = $(item).parent(o.listNodeName).parent(o.itemNodeName).attr('data-id') || null;
            if (parentId !== null && !isNaN(parentId)) {
                parentId = parseInt(parentId);
            }

            definitionId = parseInt($(item).attr('data-definition-id'));

            parentDefinitionId = $(item).parent(o.listNodeName).parent(o.itemNodeName).attr('data-definition-id') || null;

            if (parentDefinitionId) {
                parentDefinitionId = parseInt(parentDefinitionId);
            }

            name = $(item).attr('data-name');

            if (id) {
                ret.push({
                    "id": id,
                    "name": name,
                    "definition_id": definitionId,
                    "parent_id": parentId,
                    "parent_definition_id": parentDefinitionId,
                    "depth": depth,
                    "lft": lft,
                    "rgt": rgt
                });
            }

            lft = rgt + 1;
            return lft;
        }
    }

    function createBlock(blocksFieldType, blocks, templateId, location, context, blockFieldId, newBlockCount) {
        hideAllFilterMenus();

        // Get the block template from the templateId
        var template = blocksFieldType.find('#' + templateId).find('.blocksft-block');
        var blockClone = template.clone(true, true);
        var clonedHtml = blockClone.html()
            .replace(RegExp("blocks_new_block_[0-9]{1,}", "g"), "blocks_new_block_" + newBlockCount);

        blockClone.html(clonedHtml);
        blockClone.find(':input').removeAttr("disabled");
        blockClone.attr('data-id', 'blocks_new_block_' + newBlockCount);

        blocksFieldType.find('.blocksft-block--no-results').hide();

        switch (location) {
            case 'above':
                context.before(blockClone);
                break;
            case 'below':
                context.after(blockClone);
                break;
            case 'bottom':
                blocks.append(blockClone);
                break;
        }

        fireEvent("display", blockClone.find('[data-fieldtype]'));

        // Special exception for the new React based drag and drop File field in EE 5.1
        blockClone.find('div[data-file-field-react-bloqs]').each(function () {
            var $field = $(this);

            $field
                .attr('data-file-field-react', $field.attr('data-file-field-react-bloqs'))
                .removeAttr('data-file-field-react-bloqs');

            // Call native EE function to re-instantiate a new instance of the React field.
            setupFileField($field.closest('.blocksft-atomcontainer'), true);
        });

        // @todo - is this the best way to handle this?
        var textArea = blockClone.find('.grid-textarea');
        if (typeof textArea != undefined && textArea != '') {
            if ($.isFunction($.fn.FilePicker)) {
                $('.textarea-field-filepicker, li.html-upload', textArea).FilePicker({
                    callback: filePickerCallback
                });
            }
        }

        EE.cp && void 0 !== EE.cp.formValidation && EE.cp.formValidation.bindInputs(blockClone);
        EE.cp && void 0 !== EE.cp.formValidation.bindCallbackForField && EE.cp.formValidation.bindCallbackForField('field_id_' + blockFieldId, _bloqsValidationCallback);

        bindMenuClickEvent(blockClone);
        reorderFields(blocksFieldType);

        var isNestable = blocksFieldType.data('setting-nestable');
        if (isNestable === 'y') {
            var $blocksContainer = blocksFieldType.find('.' + nestableFieldOptions.rootClass);
            updateTreeField($blocksContainer, blockClone);
        }
    }

    /**
     * @param {jQuery} $container
     * @param {boolean} resetOnSetup
     */
    function setupFileField ($container, resetOnSetup) {
        if (resetOnSetup) {
            resetFileFigure($container.find('.fields-upload-chosen'));
        }

        $('.file-field-filepicker', $container).FilePicker({
            callback: EE.FileField.pickerCallback
        });

        $('li.remove a').click(function (e) {
            resetFileFigure($(this).closest('.fields-upload-chosen'));
            e.preventDefault();
        });

        // Drag and drop component
        FileField.renderFields($container);
    }

    /**
     * @param {jQuery} $figureContainer
     */
    function resetFileFigure ($figureContainer) {
        $figureContainer.addClass('hidden');
        $figureContainer.siblings('em').remove(); // Hide the "missing file" error
        $figureContainer.siblings('input[type="hidden"]').val('').trigger('change');
        $figureContainer.siblings('.fields-upload-btn').removeClass('hidden');
    }

    /**
     * EE core JS does not account for dynamically added sub-menus :(
     */
    function bindMenuClickEvent(block) {
        block.find('.blocksft-template--nav-sub').on('click', function () {
            block.find('.nav-open').not(this)
                .removeClass('nav-open')
                .siblings('.nav-sub-menu').hide();

            $(this)
                .siblings('.nav-sub-menu').toggle()
                .end()
                .toggleClass('nav-open');

            $(this).siblings('.nav-sub-menu').find('.autofocus').focus();

            return false;
        });
    }

    /**
     * Set the order value for all of the fields.
     */
    function reorderFields(blocksFieldType) {
        var order = 1;
        blocksFieldType.find('[data-order-field]').each(function () {
            $(this).val(order);
            order++;
        });
    }

    function collapseBlock(block) {
        block.find('> .tbl-row').attr('data-blockvisibility', 'collapsed');
        summarizeBlock(block);
    }

    function setBlockDraft(block) {
        block.find('> .tbl-row .block-draft').val('1');
        block.addClass('block-draft');
    }

    function setBlockLive(block) {
        block.find('> .tbl-row .block-draft').val('0');
        block.removeClass('block-draft');
    }

    function expandBlock(block) {
        block.find('> .tbl-row').attr('data-blockvisibility', 'expanded');
    }

    function stripHtml(string) {
        if (!string) {
            return '';
        }

        return string.replace(/<\/?[^>]+(>|$)/g, '');
    }

    function summarizeBlock(blockContainer) {
        var summarized = '';
        blockContainer.find('> .tbl-row').each(function () {
            var block = $(this);
            block.find('[data-fieldtype]').each(function () {
                var atom = $(this);
                var fieldtype = atom.attr('data-fieldtype');
                if (summarizers[fieldtype]) {
                    var text = stripHtml(summarizers[fieldtype](atom.find('.blocksft-atomcontainer')));
                    if (text && !/^\s*$/.test(text)) {
                        summarized += ' \u2013 ' + text;
                    }
                }
            });
            if (summarized !== '') {
                summarized = summarized.substring(0, 30) + '...';
                block.find('[js-summary]').text(summarized);
            }
        });
    }

    function fireEvent(eventName, fields) {
        fields.each(function () {
            // Some field types require this.
            window.Grid.Settings.prototype._fireEvent(eventName, $(this));
        });
    }

    // On occassion, Blocks will load before another field type within a
    // block, and so Grid.bind will not have been called yet. So, we need to
    // intercept those and initialize them as well. I'm not convinced this is
    // the best way to do this, so it may need to be refined in the future.
    var g = Grid;
    var b = g.bind;
    g.bind = function (fieldType, eventName, callback) {
        b.apply(g, [fieldType, eventName, callback]);
        if (eventName === "display") {
            fireEvent("display", $('.blocksft .blocksft-blocks [data-fieldtype="' + fieldType + '"]'));
        }
    };

    $('a.m-link').click(function (e) {
        var modalIs = $('.' + $(this).attr('rel'));
        $('.checklist', modalIs)
            .html('') // Reset it
            .append('<li>' + $(this).data('confirm') + '</li>');
        $('input[name="blockdefinition"]', modalIs).val($(this).data('blockdefinition'));

        e.preventDefault();
    });

    var filePickerCallback = function (i, e) {
        var t = e.input_value;

        // Output as image tag if image
        if (
            // May be a markItUp button
            0 == t.size() && (t = e.source.parents(".markItUpContainer").find("textarea.markItUpEditor")),
                // Close the modal
                e.modal.find(".m-close").click(),
                // Assign the value {filedir_#}filename.ext
                file_string = "{filedir_" + i.upload_location_id + "}" + i.file_name, i.isImage) {
            var a = '<img src="' + file_string + '"';
            a += ' alt=""', i.file_hw_original && (dimensions = i.file_hw_original.split(" "), a = a + ' height="' + dimensions[0] + '" width="' + dimensions[1] + '"'), a += ">", t.insertAtCursor(a)
        } else
        // Output link if non-image
            t.insertAtCursor('<a href="' + file_string + '">' + i.file_name + "</a>")
    };

    function _bloqsValidationCallback(success, message, input) {
        var form = input.parents('form'),
            blocksErrorElement = 'em.blocks-ee-form-error-message',
            blocksAtomContainer = input.closest('.blocksft-atomcontainer'),
            blocksAtom = blocksAtomContainer.parent('.blocksft-atom'),
            fieldSet = blocksAtom.closest('fieldset.col-group');

        //The ajax-validate toggleErrorFields function is a bit ambitious when it comes to removing field errors,
        //especially with the way blocks is built. To keep it from adding/removing errors unnecessasrily, we change the
        //class name on the error message html element. We'll take care of that ourselves.
        message = $(message).removeClass('ee-form-error-message').addClass('blocks-ee-form-error-message no');

        if (success === false) {
            fieldSet.find('em.ee-form-error-message').remove();
            blocksAtom.addClass('invalid');
            if (blocksAtomContainer.has(blocksErrorElement).length) {
                blocksAtomContainer.find(blocksErrorElement).remove();
            }
            blocksAtomContainer.append(message);
        }
        else {
            fieldSet.find('em.ee-form-error-message').remove();
            blocksAtom.removeClass('invalid');
            blocksAtomContainer.find(blocksErrorElement).remove();

            if (fieldSet.find('.invalid').length && !fieldSet.hasClass('invalid')) {
                fieldSet.addClass('invalid');
                _disablePublishFormButtons(form, input);
            }
        }
        return;
    }

    function clearErrorsOnBlock(block) {
        var blocksAtomContainer = block.find('.blocksft-atoms');
        if (EE.cp && EE.cp.formValidation !== false) {
            EE.cp.formValidation.markFieldValid(blocksAtomContainer.find("input, select, textarea"));
        }
    }

    function _bloqsCheckboxValidationCallback(success, message, input) {
        var form = input.parents('form'),
            blocksErrorElement = 'em.blocks-ee-form-error-message',
            blocksAtom = $(input).closest('.blocksft-atom'),
            blocksAtomContainer = input.closest('.blocksft-atomcontainer'),
            fieldSet = blocksAtom.closest('fieldset'),
            checkboxes = blocksAtom.find('input[type="checkbox"]'),
            hasSelectedValue = false;

        if (blocksAtom.hasClass('required') !== true) {
            return;
        }

        $.each(checkboxes, function () {
            if (this.checked) {
                hasSelectedValue = true;
            }
        });

        if (hasSelectedValue) {
            blocksAtom.removeClass('invalid');
            blocksAtom.find(blocksErrorElement).remove();
            if (fieldSet.find('.invalid').length > 0 && !fieldSet.hasClass('invalid')) {
                fieldSet.addClass('invalid');
            }
            else {
                fieldSet.removeClass('invalid');
            }
        }
        else {
            fieldSet.addClass('invalid');
            if (!blocksAtom.hasClass('invalid')) {
                blocksAtom.addClass('invalid');
            }
            if (typeof message != 'undefined') {
                message = $(message).removeClass('ee-form-error-message').addClass('blocks-ee-form-error-message no');
                blocksAtomContainer.append(message);
            }
            _disablePublishFormButtons(form, input);
        }
    }

    function _disablePublishFormButtons(form, input) {
        var tab_container = input.parents('.tab'),
            tabRel = (tab_container.size() > 0) ? tab_container.attr('class').match(/t-\d+/) : '', // Grabs the tab identifier (ex: t-2)
            tab = $(tab_container).parents('.tab-wrap').find('a[rel="' + tabRel + '"]'), // Tab link
            // See if this tab has its own submit button
            tabHasOwnButton = (tab_container.size() > 0 && tab_container.find('.form-ctrls input.btn').size() > 0),
            // Finally, grab the button of the current form
            button = (tabHasOwnButton) ? tab_container.find('.form-ctrls .btn') : form.find('.form-ctrls .btn');

        // Disable submit button
        button.addClass('disable').attr('disabled', 'disabled');
        if (button.is('input')) {
            button.attr('value', EE.lang.btn_fix_errors);
        } else if (button.is('button')) {
            button.text(EE.lang.btn_fix_errors);
        }
    }

});
