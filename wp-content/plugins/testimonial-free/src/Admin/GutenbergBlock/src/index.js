import { escapeHTML } from "@wordpress/escape-html";
import testimonialEdit from "./testimonials/testimonial";
import testimonialEditForm from "./testimonialForms/form";
import { CategoryIcon, TestimonialFormIcon, TestimonialIcon } from "../assets/testimonialIcons";

const { __ } = wp.i18n;
const { registerBlockType, updateCategory } = wp.blocks;
/**
 * Register: Gutenberg Blocks.
 */
updateCategory('testimonial-free', { icon: < CategoryIcon /> });

const dynamicBlockGenerator = (name, title, description, icon, edit) => {
  registerBlockType(name, {
    title: escapeHTML(title),
    description: escapeHTML(description),
    icon: icon,
    category: escapeHTML("testimonial-free"),
    supports: {
      html: true,
    },
    edit: edit,
    save() {
      // Rendering in PHP
      return null;
    },
  });
};

dynamicBlockGenerator("sp-testimonial-pro/shortcode", __('Real Testimonials', 'testimonial-free'), __('Use Real Testimonials to insert a view shortcode (testimonials) in your page', 'testimonial-free'), TestimonialIcon, testimonialEdit);

dynamicBlockGenerator("sp-testimonial-pro/form", __('Testimonial Form', 'testimonial-free'), __('Use Testimonials Form to insert a view shortcode (testimonial Form) in your page', 'testimonial-free'), TestimonialFormIcon, testimonialEditForm);