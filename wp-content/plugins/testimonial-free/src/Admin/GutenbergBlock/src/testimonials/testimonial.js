import DynamicShortcodeInput from "../shortcode/dynamicShortcode";
import { escapeAttribute, escapeHTML } from "@wordpress/escape-html";
import { InspectorControls } from '@wordpress/block-editor';
import { TestimonialPreviewImage } from "../../assets/testimonialIcons";

const { __ } = wp.i18n;
const { PanelBody, PanelRow } = wp.components;
const { Fragment } = wp.element;
const ServerSideRender = wp.serverSideRender;
const el = wp.element.createElement;

const testimonialEdit = ({ attributes, setAttributes }) => {
    var shortCodeList = sp_testimonial_free.shortCodeList;

    let scriptLoad = (shortcodeId) => {
        let sprtfBlockLoaded = false;
        let sprtfBlockLoadedInterval = setInterval(function () {
            let uniqId = jQuery("#sp-testimonial-free-wrapper-" + shortcodeId)
                .parents()
                .attr("id");
            if (document.getElementById(uniqId)) {
                jQuery.getScript(sp_testimonial_free.loadScript);

                jQuery('#sp-testimonial-preloader-' + shortcodeId).css({ 'opacity': 0, 'display': 'none' });
                jQuery('#sp-testimonial-free-' + shortcodeId).animate({ opacity: 1 }, 600);
                sprtfBlockLoaded = true;
                uniqId = "";
            }
            if (sprtfBlockLoaded) {
                clearInterval(sprtfBlockLoadedInterval);
            }
            if (0 == shortcodeId) {
                clearInterval(sprtfBlockLoadedInterval);
            }
        }, 10);
    };

    let updateShortcode = (updateShortcode) => {
        setAttributes({ shortcode: escapeAttribute(updateShortcode.target.value) });
    };

    let shortcodeUpdate = (e) => {
        updateShortcode(e);
        let shortcodeId = escapeAttribute(e.target.value);
        scriptLoad(shortcodeId);
    };

    document.addEventListener("readystatechange", (event) => {
        if (event.target.readyState === "complete") {
            let shortcodeId = escapeAttribute(attributes.shortcode);
            scriptLoad(shortcodeId);
        }
    });

    if (attributes.preview) {
        return <TestimonialPreviewImage />;
    };

    if (shortCodeList.length === 0) {
        return (
            <Fragment>
                {el(
                    "div",
                    { className: "components-placeholder components-placeholder is-large sprtf_block_shortcode" },
                    el(
                        "div",
                        { className: "components-placeholder__label" },
                        el("img", {
                            className: 'block-editor-block-icon',
                            src: escapeAttribute(sp_testimonial_free.url + 'Admin/GutenbergBlock/assets/real-testimonials-logo.svg'),
                        }),
                        el("h4", {}, escapeHTML(__("Real Testimonials", "testimonial-free")))
                    ),
                    el(
                        "div",
                        { className: "sprtf_block_shortcode_text" },
                        escapeHTML(__("No view shortcode found. ", "testimonial-free")),
                        el(
                            "a",
                            { href: escapeAttribute(sp_testimonial_free.link) },
                            escapeHTML(__("Create a view now!", "testimonial-free"))
                        )
                    )
                )}
            </Fragment>
        );
    }

    if (!attributes.shortcode || attributes.shortcode == 0) {
        return (
            <Fragment>
                <InspectorControls>
                    <PanelBody title="Select a view (shortcode)">
                        <PanelRow>
                            <DynamicShortcodeInput
                                attributes={attributes}
                                shortCodeList={shortCodeList}
                                shortcodeUpdate={shortcodeUpdate}
                            />
                        </PanelRow>
                    </PanelBody>
                </InspectorControls>
                {
                    el('div', { className: 'components-placeholder components-placeholder is-large sprtf_block_shortcode' },
                        el('div', { className: 'components-placeholder__label' },
                            el('img', { className: 'block-editor-block-icon', src: escapeAttribute(sp_testimonial_free.url + 'Admin/GutenbergBlock/assets/real-testimonials-logo.svg') }),
                            escapeHTML(__("Real Testimonial", "testimonial-free"))
                        ),
                        el('div', { className: 'components-placeholder__instructions' }, escapeHTML(__("Select a view (shortcode)", "testimonial-free"))),
                        <DynamicShortcodeInput
                            attributes={attributes}
                            shortCodeList={shortCodeList}
                            shortcodeUpdate={shortcodeUpdate}
                        />
                    )
                }
            </Fragment>
        );
    }

    return (
        <Fragment>

            <InspectorControls>
                <PanelBody title="Real Testimonials Block Settings">
                    <PanelRow>
                        <DynamicShortcodeInput
                            attributes={attributes}
                            shortCodeList={shortCodeList}
                            shortcodeUpdate={shortcodeUpdate}
                        />
                    </PanelRow>
                </PanelBody>
            </InspectorControls>
            <ServerSideRender
                block="sp-testimonial-pro/shortcode"
                attributes={attributes}
            />
        </Fragment>
    );
}

export default testimonialEdit;