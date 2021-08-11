jQuery(function($) {

	var blockselectors = $('.blockselectors');
	var fieldNames = [];

	// A work around which re-enables our hidden fields which
	// The ee core is trying to disable on page load
	$(function(){
		var hidden_inputs = blockselectors.find( $('input:hidden[name^="blockdefinitions"]') );
		hidden_inputs.each(function(){
			$(this).removeAttr('disabled');
		});
	});

	var updateSelectors = function() {
		var count = 1;
		fieldNames = [];
		blockselectors.find('.blockselector').each(function() {
			var $selector = $(this);
			var $order = $selector.find('[js-order]');
			var $checkbox = $selector.find('[js-checkbox]');
			var fieldName = $selector.find('[js-field-name]');

			if ($checkbox.is(':checked')) {
				$order.val(count);
				count++;
				fieldNames.push(fieldName.val());
			}
			else {
				$order.val(0);
			}
		});

		fetchTemplateCode();
	};

	blockselectors.bind('change', 'js-checkbox', updateSelectors);

    $('.blockselectors').sortable({
        axis: 'y',						// Only allow vertical dragging
        handle: '.list-reorder',		// Set drag handle
        items: '.nestable-item',		// Only allow these to be sortable
        sort: EE.sortable_sort_helper,
        forcePlaceholderSize: true,
        placeholder: {
            element: function (currentItem) {
                var height = currentItem.height() - 4;
                return $('<li><div class="nestable-item drag-placeholder"><div class="none" style="height: '+ height +'px"></div></div></li>')[0];
            },
            update: function (container, p) {
                return;
            }
        },
        update: updateSelectors
    });

    var $fieldId = $('[name="field_id"]');
    var $fieldName = $('[name="field_name"]');
    var fetchTemplateCode = function() {
        if (!$fieldId.val()) {
            return;
        }
        $.ajax({
            type: "GET",
            url: EE.bloqs.ajax_fetch_template_code,
            data: {
                field_name: $fieldName.val(),
                include_blocks: fieldNames
            },
            success: function (data) {
                if (!data) {
                    return;
                }

                $('.bloqs-template-code').html(data);
            }
        });
    };

    fetchTemplateCode();
    $fieldName.on('change', fetchTemplateCode);
});
