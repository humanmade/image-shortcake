<?php
/**
 * An image shortcode.
 *
 * Creates and renders an [img] shortcode.
 */

class Img_Shortcode {


	/**
	 * Shortcode UI attributes.
	 *
	 * To modify these attributes (for example to limit this shortcode to certain
	 * post types), this object can be filtered with the `img_shortcode_ui_args`
	 * filter.
	 */
	public static function get_shortcode_ui_args() {

		$shortcode_ui_args = array(

				'label' => esc_attr__( 'Image', 'image-shortcake' ),

				'listItemImage' => 'dashicons-format-image',

				'attrs' => array(

					array(
						'label' => esc_attr__( 'Choose Attachment', 'image-shortcake' ),
						'attr'  => 'attachment',
						'type'  => 'attachment',
						'libraryType' => array( 'image' ),
						'addButton'   => esc_attr__( 'Select Image', 'image-shortcake' ),
						'frameTitle'  => esc_attr__( 'Select Image', 'image-shortcake' ),
					),

					array(
						'label'       => esc_attr__( 'Image size', 'image-shortcake' ),
						'attr'        => 'size',
						'type'        => 'select',
						'options' => array(
							'thumbnail' => esc_attr(
								sprintf(
									__( 'Thumbnail (%s px)', 'image-shortcake' ),
									get_option('thumbnail_size_w')
								)
							),
							'medium' => esc_attr(
								sprintf(
									__( 'Medium (%s px)', 'image-shortcake' ),
									get_option('medium_size_w')
								)
							),
							'large' => esc_attr(
								sprintf(
									__( 'Large (%s px)', 'image-shortcake' ),
									get_option('large_size_w')
								)
							),
							'full' => esc_attr__( 'Full size', 'image-shortcake' )
						),
					),

					array(
						'label' => esc_attr__( 'Alt', 'image-shortcake' ),
						'attr'  => 'alt',
						'type'  => 'text',
						'placeholder' => esc_attr__( 'Alt text for the image', 'image-shortcake' ),
					),

					array(
						'label'       => esc_attr__( 'Caption', 'image-shortcake' ),
						'attr'        => 'caption',
						'type'        => 'text',
						'placeholder' => esc_attr__( 'Caption for the image', 'image-shortcake' ),
					),

					array(
						'label'       => esc_attr__( 'Alignment', 'image-shortcake' ),
						'attr'        => 'align',
						'type'        => 'select',
						'options' => array(
							'alignnone'   => esc_attr__( 'None (inline)', 'image-shortcake' ),
							'alignleft'   => esc_attr__( 'Float left',    'image-shortcake' ),
							'alignright'  => esc_attr__( 'Float right',   'image-shortcake' ),
							'aligncenter' => esc_attr__( 'Center',        'image-shortcake' ),
						),
					),

					array(
						'label'       => esc_attr__( 'Link to', 'image-shortcake' ),
						'attr'        => 'linkto',
						'type'        => 'select',
						'options' => array(
							'none'       => esc_attr__( 'None (no link)',          'image-shortcake' ),
							'attachment' => esc_attr__( 'Link to attachment file', 'image-shortcake' ),
							'file'       => esc_attr__( 'Link to file',            'image-shortcake' ),
							'custom'     => esc_attr__( 'Custom link',             'image-shortcake' ),
						),
					),

				),
			);

		/**
		 * Filter the shortcode UI definition arguments
		 *
		 * @param array Shortcode UI arguments
		 */
		$shortcode_ui_args = apply_filters( 'img_shortcode_ui_args', $shortcode_ui_args );

		return $shortcode_ui_args;
	}


	/**
	 * Take content containing existing image tags or [caption] shortcodes,
	 * turn it into the shortcodes we want, so all images can be processed in
	 * the same way.
	 *
	 */
	public static function reversal( $content ) {
		/**
		 * TODO: detect images in content. If we can, try and replace them with
		 * the [img] shortcode.
		 */
		return $content;
	}


	/**
	 * Render output from this shortcode.
	 *
	 * Can be filtered at several different points.
	 *
	 * @param array $attrs Shortcode attributes. See definitions in
	 *                     @function `get_shortcode_ui_args()`
	 * @return string
	 */
	public static function callback( $attr ) {

		$attr = wp_parse_args( $attr, array(
			'attachment' => 0,
			'size'       => 'full',
			'alt'        => '',
			'classes'    => '',
			'caption'    => '',
			'align'      => 'alignnone',
			'linkto'     => '',
		) );

		/**
		 *
		 *
		 * @param
		 */
		$attr = apply_filters( 'img_shortcode_attrs', $attr );

		$image_html = '<img ';

		$image_classes = explode( ' ', $attr['classes'] );
		$image_classes[] = 'size-' . $attr['size'];
		$image_classes[] = $attr['align'];

		$image_attr = array(
			'alt' => $attr['alt'],
			'class' => trim( implode( ' ', $image_classes ) ),
		);

		if ( isset( $attr['attachment'] ) &&
				$attachment = wp_get_attachment_image_src( (int) $attr['attachment'], $attr['size'] ) ) {
			$image_attr['src'] = esc_url( $attachment[0] );
			$image_attr['width'] = intval( $attachment[1] );
			$image_attr['height'] = intval( $attachment[2] );
		} else if ( ! empty( $attr['src'] ) ) {
			$image_attr['src'] = esc_url( $attr['src'] );
		} else {
			return; // An image without a src isn't much of an image
		}

		foreach ( $image_attr as $attr_name => $attr_value ) {
			if ( ! empty( $attr_value ) ) {
				$image_html .= sanitize_key( $attr_name ) . '="' . esc_attr( $attr_value ) . '" ';
			}
		}

		$image_html .= '/>';

		/**
		 * Filter the output of the <img> tag before wrapping it in link or caption
		 *
		 * @param string HTML markup of the image tag
		 * @param array Shortcode attributes
		 */
		$image_html = apply_filters( 'img_shortcode_output_img_tag', $image_html, $attr );

		// If a link is specified, wrap the image in a link tag
		if ( ! empty( $attr['linkto'] ) || ! empty( $attr['url'] ) ) {
			$image_html = self::linkify( $image_html, $attr );
		}

		/**
		 * Filter the output of the <img> tag after wrapping in link
		 *
		 * @param string HTML markup of the image tag, possibly wrapped in a link
		 * @param array Shortcode attributes
		 */
		$image_html = apply_filters( 'img_shortcode_output_after_linkify', $image_html, $attr );

		// If a caption is specified, wrap the image in the appropriat caption markup.
		if ( ! empty( $attr['caption'] ) ) {

			// The WP caption element requires a width defined
			if ( empty( $attr['width'] ) ) {
				$attr['width'] = $image_attr['width'];
			}

			$image_html = self::captionify( $image_html, $attr );
		}

		/**
		 * Filter the output of the <img> tag after wrapping in link and attaching caption
		 *
		 * @param string HTML markup of the image tag, possibly wrapped in a link and caption
		 * @param array Shortcode attributes
		 */
		$image_html = apply_filters( 'img_shortcode_output_after_captionify', $image_html, $attr );

		return $image_html;
	}


	/**
	 * Wrap an image in a link, if required.
	 *
	 * Returns either the img tag passed in, if no link is specified, or the
	 * img wrapped in a link if we know the link to build.
	 *
	 * @param string $img_tag string representing an HTML <img> element
	 * @param array $attributes Shortcode attributes from the [img] shortcode.
	 * @return string HTML representing an `<a>` element surrounding an image.
	 */
	private static function linkify( $img_tag, $attributes ) {

		$_id = intval( $attributes['attachment'] );

		$link_attrs = array();

		if ( isset( $attributes['url'] ) ) {
			$link_attrs['href'] = esc_url( $attributes['url'] );
		} else if ( ! empty( $attributes['linkto'] ) && 'attachment' === $attributes['linkto'] ) {
			$link_attrs['href'] = get_permalink( $_id );
		} elseif ( ! empty( $attributes['linkto'] ) && 'file' === $attributes['linkto'] ) {
			$attachment_src = wp_get_attachment_image_src( $_id, 'full', false, $attributes );
			$link_attrs['href'] = $attachment_src[0];
		} else {
			// No link is defined, or its in a format that's not implemented yet.
			return $img_tag;
		}

		$html = '<a ';

		foreach ( $link_attrs as $attr_name => $attr_value ) {
			$html .= sanitize_key( $attr_name ) . '="' . esc_attr( $attr_value ) . '" ';
		}

		$html .= '>' . $img_tag .'</a>';

		return $html;
	}


	/**
	 * Wrap an image in the markup for a caption.
	 *
	 * Uses the `img_caption_shortcode` function from WP core for compatibility
	 * with themes and plugins that already filter caption markup through filters there.
	 *
	 * @attr string $img_tag HTML markup for the <img> tag.
	 * @attr string $caption Caption text.
	 * @attr array $attributes The attributes set on the shortcode.
	 * @return string HTML `<dl>` element representing the image and caption
	 */
	private static function captionify( $img_tag, $attributes ) {

		$attributes = wp_parse_args( $attributes,
			array(
				'id' => null,
				'caption' => '',
				'title' => '',
				'align' => '',
				'url' => '',
				'size' => '',
				'alt' => '',
			)
		);

		$html = img_caption_shortcode( $attributes, $img_tag );

		return $html;
	}


	/**
	 * Catch images inserted through the media library, and convert them to the
	 * shortcode format introduced by this plugin.
	 *
	 * @filter media_send_to_editor
	 * @param string $html Generated by `wp_ajax_send_attachment_to_editor()`
	 * @param int $id Attachment ID
	 * @param array $attachment Attributes selected in the media editor
	 * @return string
	 */
	public static function filter_media_send_to_editor( $html, $attachment_id, $attachment ) {

		$shortcode_attrs = array();

		if ( $attachment_id = intval( $attachment_id ) ) {
			$shortcode_attrs['attachment'] = $attachment_id;
		}

		if ( ! empty( $attachment['align'] ) ) {
			$shortcode_attrs['align'] = 'align' . $attachment['align'];
		}

		$allowed_attrs = array(
			'image-size' => 'size',
			'image_alt' => 'alt',
			'post_excerpt' => 'caption',
		);

		foreach ( $allowed_attrs as $attachment_attr => $shortcode_attr ) {
			if ( ! empty( $attachment[ $attachment_attr ] ) ) {
				$shortcode_attrs[ $shortcode_attr ] = $attachment[ $attachment_attr ];
			}
		}

		/**
		 * Filter the shortcode attributes when inserting image from the media library.
		 *
		 * @param array Shortcode attributes, as generated by the plugin
		 * @param string $html Generated by `wp_ajax_send_attachment_to_editor()`
		 * @param int $id Attachment ID
		 * @param array $attachment Attributes selected in the media editor
		 */
		$shortcode_attrs = apply_filters( 'img_shortcode_send_to_editor_attrs', $shortcode_attrs, $html, $attachment_id, $attachment );

		$shortcode = '[img ';

		foreach ( $shortcode_attrs as $attr_name => $attr_value ) {
			$shortcode .= sanitize_key( $attr_name ) . '="' . esc_attr( $attr_value ) . '" ';
		}

		$shortcode .= '/]';

		return $shortcode;
	}

}
