(function($){
    $(function(){
        var frame;
        var visibleMax = parseInt($('#itn-teaser-options-form').attr('data-max-visible') || '5', 10);
        if (isNaN(visibleMax) || visibleMax < 1) {
            visibleMax = 5;
        }
        // Initialize handlers for existing elements
        function initImageButtons(context) {
            context = context || $(document);
            context.on('click', '.sventes-select-image', function(e){
                e.preventDefault();
                var $btn = $(this);
                var $parent = $btn.closest('.sventes-teaser-item');

                // Create the media frame.
                frame = wp.media({
                    title: SventesTeaserAdmin.strings.choose_image,
                    button: { text: SventesTeaserAdmin.strings.choose_image },
                    multiple: false
                });

                frame.on('select', function(){
                    var attachment = frame.state().get('selection').first().toJSON();
                    $parent.find('.sventes-image-id').val(attachment.id);
                    $parent.find('.sventes-image-url').val(attachment.url);
                    $parent.find('.sventes-image-preview').html('<img src="' + attachment.url + '" style="max-width:200px;height:auto;" />');
                });

                frame.open();
            });

            context.on('click', '.sventes-remove-image', function(e){
                e.preventDefault();
                var $btn = $(this);
                var $parent = $btn.closest('.sventes-teaser-item');
                $parent.find('.sventes-image-id').val('');
                $parent.find('.sventes-image-url').val('');
                $parent.find('.sventes-image-preview').html('');
            });

            // If user edits the image URL manually, update preview
            context.on('change', '.sventes-image-url', function(){
                var $input = $(this);
                var url = $input.val();
                var $parent = $input.closest('.sventes-teaser-item');
                if (url) {
                    $parent.find('.sventes-image-preview').html('<img src="' + url + '" style="max-width:200px;height:auto;" />');
                    $parent.find('.sventes-image-id').val('');
                } else {
                    $parent.find('.sventes-image-preview').html('');
                }
            });
        }

        initImageButtons($(document));

        function updateTeaserHeadings() {
            var teaserLabel = (SventesTeaserAdmin.strings && SventesTeaserAdmin.strings.teaser_label) || 'Teaser';
            $('#sventes-teaser-list .sventes-teaser-item').each(function(index){
                var $heading = $(this).children('h3').first();
                if ($heading.length) {
                    $heading.text(teaserLabel + ' ' + (index + 1));
                }
            });
        }

        function initSortableTeasers() {
            var $list = $('#sventes-teaser-list');
            if (!$list.length || typeof $list.sortable !== 'function') {
                return;
            }

            $list.sortable({
                items: '.sventes-teaser-item',
                handle: 'h3',
                placeholder: 'sventes-teaser-placeholder',
                update: function() {
                    updateTeaserHeadings();
                }
            });
        }

        initSortableTeasers();
        updateTeaserHeadings();

        function updateTeaserSourceVisibility() {
            var mode = $('#itn-teaser-source-mode').val() || 'manual';
            var isAuto = mode === 'acf_posts';
            $('.js-itn-teaser-manual-fields').toggle(!isAuto);
            $('.js-itn-teaser-auto-fields').toggle(isAuto);
            $('#itn-teaser-acf-hint').toggle(isAuto);
            updateVisibleSelects();
        }

        $(document).on('change', '#itn-teaser-source-mode', updateTeaserSourceVisibility);
        updateTeaserSourceVisibility();


        $(document).on('submit', '.js-itn-teaser-delete-set', function(){
            var message = $(this).data('confirm-message') || (SventesTeaserAdmin.strings && SventesTeaserAdmin.strings.confirm_delete_set) || 'Dieses Teasermodul wirklich löschen?';
            return window.confirm(message);
        });

        // Remove teaser handler (works for both server-rendered and dynamic items)
        $(document).on('click', '.sventes-remove-teaser', function(e){
            e.preventDefault();
            var $btn = $(this);
            var $item = $btn.closest('.sventes-teaser-item');
            // If this item has a WP editor instance, try to remove it cleanly
            var textarea = $item.find('textarea[id^="sventes_teaser_content_"]');
            if (textarea.length && window.wp && window.wp.editor && typeof wp.editor.remove === 'function') {
                var id = textarea.attr('id');
                try {
                    wp.editor.remove(id);
                } catch (err) {
                    // ignore
                }
            }
            $item.remove();
            updateTeaserHeadings();
            updateVisibleSelects();
        });

        // Add teaser
        $('#sventes-add-teaser').on('click', function(e){
            e.preventDefault();
            var $tpl = $('#sventes-teaser-template').html();
            // unique index for name attributes (timestamp + random)
            var idx = 'new_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
            var editorId = 'sventes_teaser_content_' + idx;
            $tpl = $tpl.replace(/__INDEX__/g, idx).replace(/__EDITOR_ID__/g, editorId);
            var $node = $($tpl);
            $('#sventes-teaser-list').append($node);
            if ($('#sventes-teaser-list').data('ui-sortable')) {
                $('#sventes-teaser-list').sortable('refresh');
            }

            // initialize image handlers for appended node
            initImageButtons($node);

            // initialize WP editor for the new textarea if possible
            if (window.wp && window.wp.editor && typeof wp.editor.initialize === 'function') {
                try {
                    wp.editor.initialize(editorId, {
                        tinymce: {
                            wpautop: true,
                            plugins: 'wordpress,wpautoresize,lists,link',
                        },
                        quicktags: true,
                        mediaButtons: true
                    });
                } catch (err) {
                    // fallback: leave as plain textarea
                }
            } else {
                // If wp.editor unavailable, leave plain textarea (WYSIWYG not available)
            }

            updateTeaserHeadings();
            updateVisibleSelects();
            // focus on new editor/textarea
            var $ta = $('#' + editorId);
            if ($ta.length) {
                $ta.focus();
            }
        });

        // Keep select options for visible counts in sync with number of teasers
        function updateVisibleSelects() {
            var $desktop = $('#sventes-desktop-visible');
            var $tablet = $('#sventes-tablet-visible');
            var $mobile = $('#sventes-mobile-visible');

            if (!$desktop.length && !$tablet.length && !$mobile.length) {
                return;
            }

            var count = visibleMax;
            if (count < 1) count = 1;

            var curDesktop = parseInt($desktop.val(), 10);
            if ($desktop.length) {
                $desktop.empty();
                for (var i = 1; i <= count; i++) {
                    var sel = (i === curDesktop) ? ' selected' : '';
                    $desktop.append('<option value="'+i+'" '+sel+'>'+i+'</option>');
                }
                if ($desktop.val() === null) {
                    $desktop.val(String(Math.min(Math.max(curDesktop || 1, 1), count)));
                }
            }
            var curTablet = parseInt($tablet.val(), 10);
            if ($tablet.length) {
                $tablet.empty();
                for (var t = 1; t <= count; t++) {
                    var selt = (t === curTablet) ? ' selected' : '';
                    $tablet.append('<option value="'+t+'" '+selt+'>'+t+'</option>');
                }
                if ($tablet.val() === null) {
                    $tablet.val(String(Math.min(Math.max(curTablet || 1, 1), count)));
                }
            }
            var curMobile = parseInt($mobile.val(), 10);
            if ($mobile.length) {
                $mobile.empty();
                for (var j = 1; j <= count; j++) {
                    var selm = (j === curMobile) ? ' selected' : '';
                    $mobile.append('<option value="'+j+'" '+selm+'>'+j+'</option>');
                }
                if ($mobile.val() === null) {
                    $mobile.val(String(Math.min(Math.max(curMobile || 1, 1), count)));
                }
            }
        }

        // ensure visible selects correct on load
        updateVisibleSelects();
    });
})(jQuery);