<?php
/**
 * Banner design tab.
 *
 * @package ConsentGate
 *
 * @var ConsentGate_Admin_Ui         $ui
 * @var array<string,mixed> $options
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Template included from ConsentGate_Admin::render_page().

$texts_de      = is_array( $options['texts_de'] ?? null ) ? $options['texts_de'] : ConsentGate_Options::default_texts_de();
$texts_en      = is_array( $options['texts_en'] ?? null ) ? $options['texts_en'] : ConsentGate_Options::default_texts_en();
$preview_texts = 'en' === ConsentGate_Options::frontend_locale() ? $texts_en : $texts_de;

$logo_id      = ConsentGate_Options::selected_logo_id();
$logo_preview = $logo_id > 0
	? (string) wp_get_attachment_image_url( $logo_id, 'medium' )
	: (string) ( $options['logo_url'] ?? '' );
$privacy_page_id = ConsentGate_Options::selected_privacy_page_id();
?>
<form method="post" action="options.php">
	<?php settings_fields( 'consentgate_settings_group' ); ?>

	<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
		<?php $ui->heading( __( 'Appearance', 'wiedenroth-technologies-consentgate' ), __( 'Position, size, colors, and logo. The preview on the right updates as you change them.', 'wiedenroth-technologies-consentgate' ) ); ?>

		<div class="<?php echo esc_attr( $ui->cls( 'field' ) ); ?>">
			<label class="<?php echo esc_attr( $ui->cls( 'field__label' ) ); ?>" for="<?php echo esc_attr( ConsentGate_Admin::field_id( 'position' ) ); ?>"><?php esc_html_e( 'Position', 'wiedenroth-technologies-consentgate' ); ?></label>
			<div class="<?php echo esc_attr( $ui->cls( 'field__control' ) ); ?>">
				<select class="<?php echo esc_attr( $ui->cls( 'select' ) ); ?>" id="<?php echo esc_attr( ConsentGate_Admin::field_id( 'position' ) ); ?>" name="<?php echo esc_attr( ConsentGate_Admin::option_name( 'position' ) ); ?>" data-cg-position>
					<option value="bottom" <?php selected( $options['position'], 'bottom' ); ?>><?php esc_html_e( 'Bottom (bar)', 'wiedenroth-technologies-consentgate' ); ?></option>
					<option value="corner" <?php selected( $options['position'], 'corner' ); ?>><?php esc_html_e( 'Corner, right', 'wiedenroth-technologies-consentgate' ); ?></option>
					<option value="corner-left" <?php selected( $options['position'], 'corner-left' ); ?>><?php esc_html_e( 'Corner, left', 'wiedenroth-technologies-consentgate' ); ?></option>
					<option value="modal" <?php selected( $options['position'], 'modal' ); ?>><?php esc_html_e( 'Modal (centered)', 'wiedenroth-technologies-consentgate' ); ?></option>
				</select>
				<?php $ui->help( __( 'The page stays usable without a choice. Modal only dims the background and does not block clicks on the site.', 'wiedenroth-technologies-consentgate' ) ); ?>
			</div>
		</div>

		<div class="<?php echo esc_attr( $ui->cls( 'field' ) ); ?>">
			<label class="<?php echo esc_attr( $ui->cls( 'field__label' ) ); ?>" for="<?php echo esc_attr( ConsentGate_Admin::field_id( 'banner_size' ) ); ?>"><?php esc_html_e( 'Size', 'wiedenroth-technologies-consentgate' ); ?></label>
			<div class="<?php echo esc_attr( $ui->cls( 'field__control' ) ); ?>">
				<select class="<?php echo esc_attr( $ui->cls( 'select' ) ); ?>" id="<?php echo esc_attr( ConsentGate_Admin::field_id( 'banner_size' ) ); ?>" name="<?php echo esc_attr( ConsentGate_Admin::option_name( 'banner_size' ) ); ?>" data-cg-size>
					<option value="compact" <?php selected( $options['banner_size'] ?? 'normal', 'compact' ); ?>><?php esc_html_e( 'Compact', 'wiedenroth-technologies-consentgate' ); ?></option>
					<option value="normal" <?php selected( $options['banner_size'] ?? 'normal', 'normal' ); ?>><?php esc_html_e( 'Normal', 'wiedenroth-technologies-consentgate' ); ?></option>
					<option value="wide" <?php selected( $options['banner_size'] ?? 'normal', 'wide' ); ?>><?php esc_html_e( 'Wide', 'wiedenroth-technologies-consentgate' ); ?></option>
				</select>
				<?php $ui->help( __( 'Width of the bar, corner panel, and modal.', 'wiedenroth-technologies-consentgate' ) ); ?>
			</div>
		</div>

		<div class="<?php echo esc_attr( $ui->cls( 'field' ) ); ?>">
			<span class="<?php echo esc_attr( $ui->cls( 'field__label' ) ); ?>"><?php esc_html_e( 'Colors', 'wiedenroth-technologies-consentgate' ); ?></span>
			<div class="<?php echo esc_attr( $ui->cls( 'field__control' ) ); ?>">
				<div class="<?php echo esc_attr( $ui->cls( 'inline-fields' ) ); ?>">
					<label><?php esc_html_e( 'Primary', 'wiedenroth-technologies-consentgate' ); ?> <?php ConsentGate_Admin::color( $ui, 'primary_color', $options['primary_color'] ); ?></label>
					<label><?php esc_html_e( 'Background', 'wiedenroth-technologies-consentgate' ); ?> <?php ConsentGate_Admin::color( $ui, 'background_color', $options['background_color'] ); ?></label>
					<label><?php esc_html_e( 'Text', 'wiedenroth-technologies-consentgate' ); ?> <?php ConsentGate_Admin::color( $ui, 'text_color', $options['text_color'] ); ?></label>
					<label><?php esc_html_e( 'Accept', 'wiedenroth-technologies-consentgate' ); ?> <?php ConsentGate_Admin::color( $ui, 'accept_bg', $options['accept_bg'] ); ?></label>
					<label><?php esc_html_e( 'Accept text', 'wiedenroth-technologies-consentgate' ); ?> <?php ConsentGate_Admin::color( $ui, 'accept_text', $options['accept_text'] ); ?></label>
					<label><?php esc_html_e( 'Reject', 'wiedenroth-technologies-consentgate' ); ?> <?php ConsentGate_Admin::color( $ui, 'reject_bg', $options['reject_bg'] ); ?></label>
					<label><?php esc_html_e( 'Reject text', 'wiedenroth-technologies-consentgate' ); ?> <?php ConsentGate_Admin::color( $ui, 'reject_text', $options['reject_text'] ); ?></label>
				</div>
			</div>
		</div>

		<div class="<?php echo esc_attr( $ui->cls( 'field' ) ); ?>">
			<span class="<?php echo esc_attr( $ui->cls( 'field__label' ) ); ?>"><?php esc_html_e( 'Logo (optional)', 'wiedenroth-technologies-consentgate' ); ?></span>
			<div class="<?php echo esc_attr( $ui->cls( 'field__control' ) ); ?>">
				<div class="consentgate-logo-picker" data-cg-logo-picker>
					<input type="hidden" name="<?php echo esc_attr( ConsentGate_Admin::option_name( 'logo_id' ) ); ?>" value="<?php echo esc_attr( (string) $logo_id ); ?>" data-cg-logo-id />
					<input type="hidden" name="<?php echo esc_attr( ConsentGate_Admin::option_name( 'logo_cleared' ) ); ?>" value="0" data-cg-logo-cleared />
					<img
						class="consentgate-logo-picker__preview"
						data-cg-logo-preview
						<?php echo '' !== $logo_preview ? 'src="' . esc_url( $logo_preview ) . '"' : ''; ?>
						alt=""
						<?php echo '' === $logo_preview ? 'hidden' : ''; ?>
					/>
					<div class="consentgate-logo-picker__actions">
						<button type="button" class="<?php echo esc_attr( $ui->cls( 'btn', 'secondary' ) ); ?>" data-cg-logo-pick>
							<?php echo '' !== $logo_preview ? esc_html__( 'Change logo', 'wiedenroth-technologies-consentgate' ) : esc_html__( 'Choose from Media Library', 'wiedenroth-technologies-consentgate' ); ?>
						</button>
						<button type="button" class="<?php echo esc_attr( $ui->cls( 'btn', 'ghost' ) ); ?>" data-cg-logo-remove <?php echo '' === $logo_preview ? 'hidden' : ''; ?>>
							<?php esc_html_e( 'Remove', 'wiedenroth-technologies-consentgate' ); ?>
						</button>
					</div>
				</div>
				<?php $ui->help( __( 'Image from the Media Library. Shown at the top of the banner.', 'wiedenroth-technologies-consentgate' ) ); ?>
			</div>
		</div>

		<div class="<?php echo esc_attr( $ui->cls( 'field' ) ); ?>">
			<span class="<?php echo esc_attr( $ui->cls( 'field__label' ) ); ?>" id="<?php echo esc_attr( ConsentGate_Admin::field_id( 'logo_height' ) ); ?>"><?php esc_html_e( 'Logo height', 'wiedenroth-technologies-consentgate' ); ?></span>
			<div class="<?php echo esc_attr( $ui->cls( 'field__control' ) ); ?>">
				<?php $logo_height = ConsentGate_Options::logo_height(); ?>
				<div class="consentgate-logo-size">
					<input type="range" min="16" max="200" step="1" value="<?php echo esc_attr( (string) $logo_height ); ?>" aria-labelledby="<?php echo esc_attr( ConsentGate_Admin::field_id( 'logo_height' ) ); ?>" data-cg-logo-height-range />
					<button type="button" class="consentgate-logo-size__value" data-cg-logo-height-open><?php echo esc_html( (string) $logo_height ); ?> px</button>
					<input type="hidden" name="<?php echo esc_attr( ConsentGate_Admin::option_name( 'logo_height' ) ); ?>" value="<?php echo esc_attr( (string) $logo_height ); ?>" data-cg-logo-height />
				</div>
				<dialog class="consentgate-logo-dialog" data-cg-logo-height-dialog>
					<p class="consentgate-logo-dialog__title"><?php esc_html_e( 'Logo height', 'wiedenroth-technologies-consentgate' ); ?></p>
					<label class="consentgate-logo-dialog__label" for="<?php echo esc_attr( ConsentGate_Admin::field_id( 'logo_height_manual' ) ); ?>"><?php esc_html_e( 'Pixels', 'wiedenroth-technologies-consentgate' ); ?></label>
					<input class="<?php echo esc_attr( $ui->cls( 'input' ) ); ?>" type="number" min="16" max="200" step="1" id="<?php echo esc_attr( ConsentGate_Admin::field_id( 'logo_height_manual' ) ); ?>" value="<?php echo esc_attr( (string) $logo_height ); ?>" data-cg-logo-height-manual />
					<div class="consentgate-logo-dialog__actions">
						<button type="button" class="<?php echo esc_attr( $ui->cls( 'btn', 'ghost' ) ); ?>" data-cg-logo-height-cancel><?php esc_html_e( 'Cancel', 'wiedenroth-technologies-consentgate' ); ?></button>
						<button type="button" class="<?php echo esc_attr( $ui->cls( 'btn' ) ); ?>" data-cg-logo-height-apply><?php esc_html_e( 'Apply', 'wiedenroth-technologies-consentgate' ); ?></button>
					</div>
				</dialog>
				<?php $ui->help( __( 'Drag the slider, or open the pixel value and type a height between 16 and 200.', 'wiedenroth-technologies-consentgate' ) ); ?>
			</div>
		</div>

		<div class="<?php echo esc_attr( $ui->cls( 'field' ) ); ?>">
			<label class="<?php echo esc_attr( $ui->cls( 'field__label' ) ); ?>" for="<?php echo esc_attr( ConsentGate_Admin::field_id( 'logo_align' ) ); ?>"><?php esc_html_e( 'Logo alignment', 'wiedenroth-technologies-consentgate' ); ?></label>
			<div class="<?php echo esc_attr( $ui->cls( 'field__control' ) ); ?>">
				<select class="<?php echo esc_attr( $ui->cls( 'input' ) ); ?>" id="<?php echo esc_attr( ConsentGate_Admin::field_id( 'logo_align' ) ); ?>" name="<?php echo esc_attr( ConsentGate_Admin::option_name( 'logo_align' ) ); ?>" data-cg-logo-align>
					<option value="left" <?php selected( ConsentGate_Options::logo_align(), 'left' ); ?>><?php esc_html_e( 'Left', 'wiedenroth-technologies-consentgate' ); ?></option>
					<option value="center" <?php selected( ConsentGate_Options::logo_align(), 'center' ); ?>><?php esc_html_e( 'Center', 'wiedenroth-technologies-consentgate' ); ?></option>
				</select>
				<?php $ui->help( __( 'Place the logo on the left or in the center of the banner.', 'wiedenroth-technologies-consentgate' ) ); ?>
			</div>
		</div>

		<div class="<?php echo esc_attr( $ui->cls( 'field' ) ); ?>">
			<label class="<?php echo esc_attr( $ui->cls( 'field__label' ) ); ?>" for="<?php echo esc_attr( ConsentGate_Admin::field_id( 'privacy_page_id' ) ); ?>"><?php esc_html_e( 'Privacy policy', 'wiedenroth-technologies-consentgate' ); ?></label>
			<div class="<?php echo esc_attr( $ui->cls( 'field__control' ) ); ?>">
				<?php ConsentGate_Admin::page_dropdown( $ui, 'privacy_page_id', $privacy_page_id ); ?>
				<?php
				if ( $privacy_page_id > 0 ) {
					$edit = get_edit_post_link( $privacy_page_id );
					if ( is_string( $edit ) && '' !== $edit ) {
						printf(
							'<p class="%1$s"><a href="%2$s">%3$s</a></p>',
							esc_attr( $ui->cls( 'field__help' ) ),
							esc_url( $edit ),
							esc_html__( 'Edit page', 'wiedenroth-technologies-consentgate' )
						);
					}
				}
				?>
				<?php $ui->help( __( 'This page is linked from the banner. Leave it empty to use the WordPress privacy page.', 'wiedenroth-technologies-consentgate' ) ); ?>
			</div>
		</div>

		<?php
		ConsentGate_Admin::field_toggle(
			$ui,
			'reopen_button',
			$options['reopen_button'] ?? true,
			__( 'Cookie icon', 'wiedenroth-technologies-consentgate' ),
			__( 'Show a button to reopen the banner', 'wiedenroth-technologies-consentgate' ),
			__( 'If the icon is off, add the “Cookie settings” item to a WordPress menu or the Navigation block.', 'wiedenroth-technologies-consentgate' )
		);
		?>
	</div>

	<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
		<?php
		$ui->heading(
			__( 'Support us', 'wiedenroth-technologies-consentgate' ),
			__( 'Wiedenroth Technologies ConsentGate stays free. A discreet link in the banner is optional and helps us keep developing it.', 'wiedenroth-technologies-consentgate' )
		);
		ConsentGate_Admin::field_toggle(
			$ui,
			'show_branding',
			$options['show_branding'] ?? false,
			__( 'Note in the banner', 'wiedenroth-technologies-consentgate' ),
			__( 'Show “ConsentGate” in the banner', 'wiedenroth-technologies-consentgate' ),
			__( 'Only after you turn it on. Off by default, and you can change it anytime. For the WordPress.org directory this must stay an opt-in.', 'wiedenroth-technologies-consentgate' )
		);
		?>
		<div class="consentgate-setup-brand">
			<p class="<?php echo esc_attr( $ui->cls( 'field__help' ) ); ?>"><?php esc_html_e( 'Preview of the note:', 'wiedenroth-technologies-consentgate' ); ?></p>
			<a class="cg-brand" href="<?php echo esc_url( CONSENTGATE_PRODUCT_URL ); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo ConsentGate_Options::brand_icon_svg( 'cgNote' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugin SVG, gradient ids rewritten. ?>
				<span>ConsentGate</span>
			</a>
		</div>
	</div>

	<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
		<?php $ui->heading( __( 'Categories in the banner', 'wiedenroth-technologies-consentgate' ), __( 'Necessary is always on. Preferences are optional.', 'wiedenroth-technologies-consentgate' ) ); ?>
		<?php
		ConsentGate_Admin::field_toggle( $ui, 'statistics_enabled', $options['statistics_enabled'], __( 'Statistics', 'wiedenroth-technologies-consentgate' ), __( 'Show the statistics category', 'wiedenroth-technologies-consentgate' ) );
		ConsentGate_Admin::field_toggle( $ui, 'marketing_enabled', $options['marketing_enabled'], __( 'Marketing', 'wiedenroth-technologies-consentgate' ), __( 'Show the marketing category', 'wiedenroth-technologies-consentgate' ) );
		ConsentGate_Admin::field_toggle( $ui, 'preferences_enabled', $options['preferences_enabled'], __( 'Preferences', 'wiedenroth-technologies-consentgate' ), __( 'Show the preferences category', 'wiedenroth-technologies-consentgate' ) );
		?>
	</div>

	<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
		<?php $ui->heading( __( 'Texts (German)', 'wiedenroth-technologies-consentgate' ), __( 'Shown when the banner language is German.', 'wiedenroth-technologies-consentgate' ) ); ?>
		<?php foreach ( $texts_de as $key => $value ) : ?>
			<div class="<?php echo esc_attr( $ui->cls( 'field' ) ); ?>">
				<label class="<?php echo esc_attr( $ui->cls( 'field__label' ) ); ?>" for="cg-de-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $key ); ?></label>
				<div class="<?php echo esc_attr( $ui->cls( 'field__control' ) ); ?>">
					<?php if ( in_array( $key, array( 'description', 'necessaryDescription', 'statisticsDescription', 'marketingDescription', 'preferencesDescription' ), true ) ) : ?>
						<textarea class="<?php echo esc_attr( $ui->cls( 'input' ) ); ?>" id="cg-de-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( CONSENTGATE_OPTION_KEY ); ?>[texts_de][<?php echo esc_attr( $key ); ?>]" rows="3"><?php echo esc_textarea( (string) $value ); ?></textarea>
					<?php else : ?>
						<input type="text" class="<?php echo esc_attr( $ui->cls( 'input' ) ); ?>" id="cg-de-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( CONSENTGATE_OPTION_KEY ); ?>[texts_de][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( (string) $value ); ?>" />
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>">
		<?php $ui->heading( __( 'Texts (English)', 'wiedenroth-technologies-consentgate' ), __( 'Shown when the banner language is English.', 'wiedenroth-technologies-consentgate' ) ); ?>
		<?php foreach ( $texts_en as $key => $value ) : ?>
			<div class="<?php echo esc_attr( $ui->cls( 'field' ) ); ?>">
				<label class="<?php echo esc_attr( $ui->cls( 'field__label' ) ); ?>" for="cg-en-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $key ); ?></label>
				<div class="<?php echo esc_attr( $ui->cls( 'field__control' ) ); ?>">
					<?php if ( in_array( $key, array( 'description', 'necessaryDescription', 'statisticsDescription', 'marketingDescription', 'preferencesDescription' ), true ) ) : ?>
						<textarea class="<?php echo esc_attr( $ui->cls( 'input' ) ); ?>" id="cg-en-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( CONSENTGATE_OPTION_KEY ); ?>[texts_en][<?php echo esc_attr( $key ); ?>]" rows="3"><?php echo esc_textarea( (string) $value ); ?></textarea>
					<?php else : ?>
						<input type="text" class="<?php echo esc_attr( $ui->cls( 'input' ) ); ?>" id="cg-en-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( CONSENTGATE_OPTION_KEY ); ?>[texts_en][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( (string) $value ); ?>" />
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="<?php echo esc_attr( $ui->cls( 'card' ) ); ?>" data-cg-preview-card>
		<?php $ui->heading( __( 'Preview', 'wiedenroth-technologies-consentgate' ), __( 'For checking in the admin only. The real banner appears on the front end.', 'wiedenroth-technologies-consentgate' ) ); ?>
		<div class="consentgate-preview" data-cg-preview>
			<div class="cg-panel cg-panel--<?php echo esc_attr( (string) $options['position'] ); ?> cg-size-<?php echo esc_attr( ConsentGate_Options::banner_size() ); ?>" data-cg-preview-locale="<?php echo esc_attr( ConsentGate_Options::frontend_locale() ); ?>" style="<?php echo esc_attr( '--cg-bg:' . $options['background_color'] . ';--cg-fg:' . $options['text_color'] . ';--cg-accept-bg:' . $options['accept_bg'] . ';--cg-accept-fg:' . $options['accept_text'] . ';--cg-reject-bg:' . $options['reject_bg'] . ';--cg-reject-fg:' . $options['reject_text'] . ';--cg-primary:' . $options['primary_color'] . ';--cg-logo-height:' . ConsentGate_Options::logo_height() . 'px' ); ?>">
				<div class="cg-panel-inner">
				<div class="cg-logo-wrap cg-logo-wrap--<?php echo esc_attr( ConsentGate_Options::logo_align() ); ?>" data-cg-preview-logo-wrap <?php echo '' === $logo_preview ? 'hidden' : ''; ?>>
				<img class="cg-logo" data-cg-preview-logo style="height:<?php echo esc_attr( (string) ConsentGate_Options::logo_height() ); ?>px" <?php echo '' !== $logo_preview ? 'src="' . esc_url( $logo_preview ) . '"' : ''; ?> alt="" <?php echo '' === $logo_preview ? 'hidden' : ''; ?> />
				</div>
				<p class="cg-title" data-cg-preview-title><?php echo esc_html( (string) $preview_texts['title'] ); ?></p>
				<p class="cg-desc" data-cg-preview-desc><?php echo esc_html( (string) $preview_texts['description'] ); ?></p>
				<div class="cg-actions">
					<button type="button" class="cg-btn cg-btn--accept" tabindex="-1"><?php echo esc_html( (string) $preview_texts['acceptAll'] ); ?></button>
					<button type="button" class="cg-btn cg-btn--reject" tabindex="-1"><?php echo esc_html( (string) $preview_texts['rejectAll'] ); ?></button>
					<button type="button" class="cg-btn cg-btn--ghost" tabindex="-1"><?php echo esc_html( (string) $preview_texts['customize'] ); ?></button>
				</div>
				<a class="cg-brand" href="<?php echo esc_url( CONSENTGATE_PRODUCT_URL ); ?>" target="_blank" rel="noopener noreferrer" data-cg-preview-brand <?php echo empty( $options['show_branding'] ) ? 'hidden' : ''; ?>>
					<?php echo ConsentGate_Options::brand_icon_svg( 'cgPreview' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugin SVG, gradient ids rewritten. ?>
					<span>ConsentGate</span>
				</a>
				</div>
			</div>
		</div>
	</div>

	<?php ConsentGate_Admin::save_actions( $ui, __( 'Save banner', 'wiedenroth-technologies-consentgate' ) ); ?>
</form>
