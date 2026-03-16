(function () {
    var addFilter         = wp.hooks.addFilter;
    var createElement     = wp.element.createElement;
    var Fragment          = wp.element.Fragment;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody         = wp.components.PanelBody;
    var ToggleControl     = wp.components.ToggleControl;
    var createHOC         = wp.compose.createHigherOrderComponent;

    /* 1. Attribut zum core/latest-posts Block hinzufügen */
    addFilter(
        'blocks.registerBlockType',
        'nk/event-meta-attr',
        function (settings, name) {
            if (name !== 'core/latest-posts') return settings;
            return Object.assign({}, settings, {
                attributes: Object.assign({}, settings.attributes, {
                    nkShowEventMeta: {
                        type: 'boolean',
                        default: true,
                    },
                }),
            });
        }
    );

    /* 2. Toggle in der InspectorControls-Seitenleiste einblenden */
    addFilter(
        'editor.BlockEdit',
        'nk/event-meta-control',
        createHOC(function (BlockEdit) {
            return function (props) {
                if (props.name !== 'core/latest-posts') {
                    return createElement(BlockEdit, props);
                }

                var showMeta = props.attributes.nkShowEventMeta !== false;

                return createElement(
                    Fragment,
                    null,
                    createElement(BlockEdit, props),
                    createElement(
                        InspectorControls,
                        null,
                        createElement(
                            PanelBody,
                            { title: 'Veranstaltungsinfo', initialOpen: true },
                            createElement(ToggleControl, {
                                label: 'Veranstaltungsinfo anzeigen',
                                help: showMeta
                                    ? 'Datum, Uhrzeit und Ort werden angezeigt.'
                                    : 'Veranstaltungsinfo ist ausgeblendet.',
                                checked: showMeta,
                                onChange: function (val) {
                                    props.setAttributes({ nkShowEventMeta: val });
                                },
                            })
                        )
                    )
                );
            };
        }, 'nkWithEventMetaControl')
    );
})();
