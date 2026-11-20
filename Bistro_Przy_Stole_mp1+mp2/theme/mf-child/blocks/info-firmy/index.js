/* Bez npm: zwykłe globalne pakiety wp.* WordPress. */
( function (blocks, element, blockEditor, components, ServerSideRender, i18n) {
    'use strict';
    var el = element.createElement;
    blocks.registerBlockType('mf-child/info-firmy', {
        apiVersion: 3,
        title: i18n.__('Informacja o firmie', 'mf-child'),
        category: 'widgets',
        attributes: { pokazTelefon: { type: 'boolean', default: true } },
        edit: function (props) {
            return el('div', blockEditor.useBlockProps(),
                el(blockEditor.InspectorControls, {},
                    el(components.PanelBody, {title: i18n.__('Dane bistro', 'mf-child'), initialOpen: true},
                        el(components.ToggleControl, {
                            label: i18n.__('Pokaż numer telefonu', 'mf-child'),
                            checked: props.attributes.pokazTelefon,
                            onChange: function (value) {props.setAttributes({pokazTelefon: !!value});}
                        })
                    )
                ),
                el(ServerSideRender, {block: 'mf-child/info-firmy', attributes: props.attributes})
            );
        },
        save: function () { return null; }
    });
} )(window.wp.blocks, window.wp.element, window.wp.blockEditor,
    window.wp.components, window.wp.serverSideRender, window.wp.i18n);
