(function (blocks, element, components, blockEditor, serverSideRender, i18n) {
    var el = element.createElement;
    var __ = i18n.__;
    var Fragment = element.Fragment;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;
    var ToggleControl = components.ToggleControl;
    var ServerSideRender = serverSideRender && (serverSideRender.default || serverSideRender);
    var setOptions = [{ label: __('Seitenzuordnung / Standard verwenden', 'itn-teaser'), value: '' }].concat(((window.ITNTeaserBlockData && window.ITNTeaserBlockData.sets) || []));

    blocks.registerBlockType('itn/teaser', {
        title: __('ITN Teaser', 'itn-teaser'),
        icon: 'images-alt2',
        category: 'widgets',
        description: __('Zeigt ein Teasermodul mit realistischer Vorschau im Editor an.', 'itn-teaser'),
        attributes: {
            setId: {
                type: 'string',
                default: ''
            },
            usePageAssignment: {
                type: 'boolean',
                default: true
            }
        },
        edit: function (props) {
            var attributes = props.attributes;

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: __('Teasermodul-Einstellungen', 'itn-teaser'), initialOpen: true },
                        el(SelectControl, {
                            label: __('Teasermodul', 'itn-teaser'),
                            value: attributes.setId || '',
                            options: setOptions,
                            onChange: function (value) {
                                props.setAttributes({ setId: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Seitenzuordnung verwenden, wenn kein Teasermodul ausgewählt ist', 'itn-teaser'),
                            checked: !!attributes.usePageAssignment,
                            onChange: function (value) {
                                props.setAttributes({ usePageAssignment: !!value });
                            }
                        })
                    )
                ),
                el('div', { className: 'itn-teaser-block-preview' },
                    el(ServerSideRender, {
                        block: 'itn/teaser',
                        attributes: attributes
                    })
                )
            );
        },
        save: function () {
            return null;
        }
    });
})(window.wp.blocks, window.wp.element, window.wp.components, window.wp.blockEditor, window.wp.serverSideRender, window.wp.i18n);
