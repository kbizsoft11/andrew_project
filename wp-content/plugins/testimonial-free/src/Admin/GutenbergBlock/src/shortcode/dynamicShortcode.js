/**
 * Shortcode select component.
 */
 import { escapeAttribute, escapeHTML } from "@wordpress/escape-html";
 const { __ } = wp.i18n;
 const { Fragment } = wp.element;
 const el = wp.element.createElement;
 
 const DynamicShortcodeInput = ( { attributes : { shortcode }, shortCodeList, shortcodeUpdate } ) => (
    <Fragment>
        {el('div', {className: 'sprtf-gutenberg-shortcode editor-styles-wrapper'},
            el('select', {className: 'sprtf-shortcode-selector', onChange: e => shortcodeUpdate(e), value: escapeAttribute( shortcode ) },
                el('option', {value: escapeAttribute( '0' )}, escapeHTML(  __( '-- Select a view (shortcode) --', 'testimonial-pro' ))),
                shortCodeList.map( shortcode => {
                    var title = (shortcode.title.length > 30) ? shortcode.title.substring(0,25) + '.... #(' + shortcode.id + ')' : shortcode.title + ' #(' + shortcode.id + ')';
                    return el('option', {value: escapeAttribute( shortcode.id.toString() ), key: escapeAttribute( shortcode.id.toString() )}, escapeHTML( title ) )
                })
            )
        )}
    </Fragment>
 );
 
 export default DynamicShortcodeInput;