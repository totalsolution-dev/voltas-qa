/* global _ */

function TreeValidation(block, blockDefinitions, nestedSet)
{
    var settings;
    var blockDefinition;
    var blockId = block.data('id');
    var blockDefinitionId = block.data('definition-id');
    var errorClassName = 'nesting-error';
    var currentField = block.closest('.blocksft');
    var currentFieldBlocks = currentField.find('.blocksft-block');
    var errorMessages = currentField.data('errorMessages') || {};
    var msgContainer = currentField.find('.invalid-tree-message');

    var validate = function() {
        clearErrorMessages();
        loadSettings();

        if (!validateRootOnly()) {
            showErrorMessages();
            return false;
        }

        if (!validateChildOf()) {
            showErrorMessages();
            return false;
        }

        if (!validateNoChildren()) {
            showErrorMessages();
            return false;
        }

        clearErrorMessages();
    };

    var validateRootOnly = function() {
        if (!settings || (settings && settings.root !== 'root_only')) {
            return true;
        }

        var blockNode = getBlockNode();

        if (blockNode.parent_id !== null) {
            addErrorMessage('<b>' + blockNode.name + '</b> must be root');
            return false;
        }

        return true;
    };

    var validateChildOf = function() {
        if (!settings || !settings.child_of || settings.child_of.length === 0 || settings.child_of[0] === '') {
            return true;
        }

        var blockNode = getBlockNode();
        var parentDefinitionId = blockNode.parent_definition_id;
        var childOf = _.map(settings.child_of, function(value) {
            return parseInt(value);
        });
        var parentDefinitions = getBlockDefinitions(childOf);

        if (!parentDefinitionId || _.indexOf(childOf, parentDefinitionId) === -1) {
            if (!parentDefinitionId) {
                addErrorMessage('<b>' + blockNode.name + '</b> can not be a root block');
            } else {
                var parentDefinitionNames = _.pluck(parentDefinitions, 'name');
                addErrorMessage('<b>' + blockNode.name + '</b> must be a child of <b>' + parentDefinitionNames.join(', ') + '</b>');
            }

            return false;
        }

        clearErrorMessages();

        return true;
    };

    var validateNoChildren = function() {
        var blockNode = getBlockNode();

        if (!blockNode.parent_definition_id) {
            return true;
        }

        // Can't look at the current block's definition b/c the parent is the one containing the rule.
        var parentDefinition = getBlockDefinition(blockNode.parent_definition_id);
        var canHaveChildren = (parentDefinition.settings && parentDefinition.settings.nesting && parentDefinition.settings.nesting.no_children) || null;

        if (canHaveChildren === 'n') {
            var parentBlockNode = getBlockNode(blockNode.parent_id);
            // set the parent as the current block so the error styles are applied to the relevant block.
            block = currentField.find('[data-id="'+ parentBlockNode.id +'"]');
            addErrorMessage('<b>' + parentDefinition.name + '</b> can\'t have child blocks');

            return false;
        }

        return true;
    };

    var getBlockNode = function(id) {
        if (id) {
            return _.findWhere(nestedSet, {'id': id});
        }

        return _.findWhere(nestedSet, {'id': blockId});
    };

    var loadSettings = function() {
        blockDefinition = _.findWhere(blockDefinitions, {'id': blockDefinitionId});
        settings = blockDefinition.settings && blockDefinition.settings.nesting;
    };

    var getBlockDefinition = function(definitionId) {
        return _.findWhere(blockDefinitions, {'id': definitionId});
    };

    var getBlockDefinitions = function(definitionIds) {
        var definitions = [];

        _.each(definitionIds, function(value) {
            definitions.push(getBlockDefinition(value));
        });

        return definitions;
    };

    var addErrorMessage = function(message) {
        block.addClass(errorClassName);
        errorMessages[blockId] = message;
        saveErrorMessages();
    };

    var saveErrorMessages = function() {
        currentField.data('errorMessages', errorMessages);
    };

    var showErrorMessages = function() {
        if (_.isEmpty(errorMessages)) {
            msgContainer.html('');
            currentFieldBlocks.each(function() {
                $(this).removeClass(errorClassName);
            });

            return;
        }

        var messages = '';
        for (key in errorMessages) {
            messages = messages + '<li>'+ errorMessages[key] +'</li>';
        }

        var msg = '<div class="alert inline issue">\n' +
            '\t\t<h3>Invalid Bloq Tree</h3>\n' +
            '\t\t<ul>'+ messages +'</ul>\t\t\t<a class="close" href=""></a>\n' +
            '\t</div>';

        msgContainer.html(msg);
    };

    var clearErrorMessages = function() {
        if (_.isEmpty(errorMessages)) {
            return;
        }

        block.removeClass(errorClassName);

        delete errorMessages[blockId];
        saveErrorMessages();
        showErrorMessages();
    };

    return {
        'validate': validate
    };
}
