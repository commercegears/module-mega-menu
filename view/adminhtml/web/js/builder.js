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
        var $tree = $root.find('.commercegears-mmb-root');
        var $bar = $root.find('.commercegears-mmb-savebar');
        var original = $tree.html();

        $root.find('#commercegears-mmb-store').on('change', function () {
            this.form.submit();
        });

        function depthOf($li) {
            return $li.parents('.commercegears-mmb-item').length + 1;
        }

        function subtreeHeight($li) {
            var max = 0;
            $li.find('.commercegears-mmb-item').each(function () {
                max = Math.max(max, $(this).parentsUntil($li, '.commercegears-mmb-item').length + 1);
            });
            return max + 1;
        }

        function changed() {
            $bar.prop('hidden', false);
        }

        function initSortable() {
            $root.find('.commercegears-mmb-tree').sortable({
                connectWith: '.commercegears-mmb-tree',
                handle: '.commercegears-mmb-handle',
                placeholder: 'commercegears-mmb-placeholder',
                tolerance: 'pointer',
                cursor: 'grabbing',
                start: function () {
                    $root.addClass('is-dragging');
                },
                stop: function (event, ui) {
                    $root.removeClass('is-dragging');
                    var $parentItem = ui.item.parent().closest('.commercegears-mmb-item');
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
            $tree.find('.commercegears-mmb-item').each(function () {
                var $li = $(this);
                var $parent = $li.parent().closest('.commercegears-mmb-item');
                rows.push({
                    id: $li.data('id'),
                    parent: $parent.length ? $parent.data('id') : null,
                    position: ($li.index() + 1) * 10
                });
            });
            return rows;
        }

        initSortable();

        $root.on('click', '.commercegears-mmb-move', function () {
            var $li = $(this).closest('.commercegears-mmb-item');
            var up = $(this).data('move') === 'up';
            var $sibling = up ? $li.prev('.commercegears-mmb-item') : $li.next('.commercegears-mmb-item');
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

        $root.on('click', '.commercegears-mmb-cancel', function () {
            $tree.html(original);
            initSortable();
            $bar.prop('hidden', true);
        });

        $root.on('click', '.commercegears-mmb-save', function () {
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

        $root.on('click', '.commercegears-mmb-delete', function () {
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
