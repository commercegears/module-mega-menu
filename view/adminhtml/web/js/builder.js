define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'mage/translate',
    'jquery/ui-modules/widgets/sortable'
], function ($, confirm, $t) {
    'use strict';

    /**
     * Menu Builder: nested drag and drop (jQuery UI sortable), move up/down buttons, and saving the order.
     */
    return function (config, element) {
        var $root = $(element);
        var $tree = $root.find('.sw-mmb-root');
        var $bar = $root.find('.sw-mmb-savebar');
        var original = $tree.html();

        $root.find('#sw-mmb-store').on('change', function () {
            this.form.submit();
        });

        function depthOf($li) {
            return $li.parents('.sw-mmb-item').length + 1;
        }

        function subtreeHeight($li) {
            var max = 0;
            $li.find('.sw-mmb-item').each(function () {
                max = Math.max(max, $(this).parentsUntil($li, '.sw-mmb-item').length + 1);
            });
            return max + 1;
        }

        function changed() {
            $bar.prop('hidden', false);
        }

        function initSortable() {
            $root.find('.sw-mmb-tree').sortable({
                connectWith: '.sw-mmb-tree',
                handle: '.sw-mmb-handle',
                placeholder: 'sw-mmb-placeholder',
                tolerance: 'pointer',
                cursor: 'grabbing',
                start: function () {
                    $root.addClass('is-dragging');
                },
                stop: function (event, ui) {
                    $root.removeClass('is-dragging');
                    var $parentItem = ui.item.parent().closest('.sw-mmb-item');
                    var tooDeep = depthOf(ui.item) - 1 + subtreeHeight(ui.item) > config.maxLevels;
                    if (tooDeep || ($parentItem.length && $parentItem.data('leaf'))) {
                        $(this).sortable('cancel');
                        return;
                    }
                    changed();
                }
            });
        }

        function serialise() {
            var rows = [];
            $tree.find('.sw-mmb-item').each(function () {
                var $li = $(this);
                var $parent = $li.parent().closest('.sw-mmb-item');
                rows.push({
                    id: $li.data('id'),
                    parent: $parent.length ? $parent.data('id') : null,
                    position: ($li.index() + 1) * 10
                });
            });
            return rows;
        }

        initSortable();

        $root.on('click', '.sw-mmb-move', function () {
            var $li = $(this).closest('.sw-mmb-item');
            var up = $(this).data('move') === 'up';
            var $sibling = up ? $li.prev('.sw-mmb-item') : $li.next('.sw-mmb-item');
            if (!$sibling.length) {
                return;
            }
            if (up) {
                $li.insertBefore($sibling);
            } else {
                $li.insertAfter($sibling);
            }
            $(this).trigger('focus');
            changed();
        });

        $root.on('click', '.sw-mmb-cancel', function () {
            $tree.html(original);
            initSortable();
            $bar.prop('hidden', true);
        });

        $root.on('click', '.sw-mmb-save', function () {
            var $button = $(this).prop('disabled', true);
            $.ajax({
                url: config.saveUrl,
                type: 'POST',
                dataType: 'json',
                data: {tree: JSON.stringify(serialise()), form_key: window.FORM_KEY},
                showLoader: true
            }).done(function (response) {
                if (response.success) {
                    original = $tree.html();
                    $bar.prop('hidden', true);
                }
                $('body').notification('clear').notification('add', {
                    error: !response.success,
                    message: response.message,
                    insertMethod: function (message) {
                        $('.page-main-actions').length
                            ? $(message).insertAfter('.page-main-actions')
                            : $root.before(message);
                    }
                });
            }).always(function () {
                $button.prop('disabled', false);
            });
        });

        $root.on('click', '.sw-mmb-delete', function () {
            var url = $(this).data('url');
            confirm({
                title: $t('Delete Item'),
                content: $('<div/>').text(config.confirmDelete.replace('%1', $(this).data('title'))).html(),
                actions: {
                    confirm: function () {
                        $('<form method="post"/>').attr('action', url)
                            .append($('<input type="hidden" name="form_key"/>').val(window.FORM_KEY))
                            .appendTo('body').trigger('submit');
                    }
                }
            });
        });
    };
});
