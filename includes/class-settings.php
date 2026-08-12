<?php
/**
 * Plugin settings.
 *
 * @package SEOForGeneratePress
 */

namespace AngelaBlake\SEOForGeneratePress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and retrieves plugin settings.
 */
final class Settings {
	/**
	 * Option name used to store plugin settings.
	 */
	const OPTION_NAME = 'seogp_settings';

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register the plugin settings and General section.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'seogp_general',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => $this->get_defaults(),
			)
		);

		add_settings_section(
			'seogp_site_identity',
			__( 'Site identity', 'seo-for-generatepress' ),
			array( $this, 'render_site_identity_description' ),
			'seogp_general'
		);

		add_settings_field(
			'identity_type',
			__( 'Site represents', 'seo-for-generatepress' ),
			array( $this, 'render_identity_type_field' ),
			'seogp_general',
			'seogp_site_identity'
		);

		add_settings_field(
			'wordpress_identity',
			__( 'WordPress Site Identity', 'seo-for-generatepress' ),
			array( $this, 'render_wordpress_identity_field' ),
			'seogp_general',
			'seogp_site_identity'
		);

		add_settings_field(
			'person_photo_id',
			__( 'Person photo', 'seo-for-generatepress' ),
			array( $this, 'render_person_photo_field' ),
			'seogp_general',
			'seogp_site_identity',
			array( 'class' => 'seogp-person-only' )
		);

		add_settings_field(
			'social_urls',
			__( 'Social and profile URLs', 'seo-for-generatepress' ),
			array( $this, 'render_social_urls_field' ),
			'seogp_general',
			'seogp_site_identity'
		);

		add_settings_section(
			'seogp_data_management',
			__( 'Data management', 'seo-for-generatepress' ),
			array( $this, 'render_data_management_description' ),
			'seogp_general'
		);

		add_settings_field(
			'delete_data_on_uninstall',
			__( 'Plugin removal', 'seo-for-generatepress' ),
			array( $this, 'render_delete_data_field' ),
			'seogp_general',
			'seogp_data_management'
		);
	}

	/**
	 * Get default settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_defaults() {
		return array(
			'delete_data_on_uninstall' => false,
			'identity_type'             => 'organization',
			'person_photo_id'           => 0,
			'social_urls'               => array(),
		);
	}

	/**
	 * Get all settings merged with their defaults.
	 *
	 * @return array<string, mixed>
	 */
	public function get_all() {
		$settings = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return wp_parse_args( $settings, $this->get_defaults() );
	}

	/**
	 * Sanitize settings before storage.
	 *
	 * @param mixed $input Submitted settings.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();

		$previous    = $this->get_all();
		$identity    = isset( $input['identity_type'] ) && 'person' === $input['identity_type'] ? 'person' : 'organization';
		$photo_id    = isset( $input['person_photo_id'] ) ? absint( $input['person_photo_id'] ) : 0;
		$social_urls = array();

		if ( ! empty( $input['social_urls'] ) && is_array( $input['social_urls'] ) ) {
			foreach ( $input['social_urls'] as $url ) {
				$url = trim( (string) $url );

				if ( '' === $url ) {
					continue;
				}

				$clean_url = esc_url_raw( $url, array( 'http', 'https' ) );
				$scheme    = wp_parse_url( $clean_url, PHP_URL_SCHEME );

				if ( ! $clean_url || ! in_array( $scheme, array( 'http', 'https' ), true ) || ! wp_http_validate_url( $clean_url ) ) {
					add_settings_error(
						self::OPTION_NAME,
						'seogp_invalid_social_url',
						sprintf(
							/* translators: %s is the invalid URL. */
							__( '“%s” is not a valid public HTTP or HTTPS profile URL and was not saved.', 'seo-for-generatepress' ),
							esc_html( $url )
						),
						'error'
					);
					continue;
				}

				$social_urls[] = $clean_url;
			}
		}

		$social_urls = array_values( array_unique( $social_urls ) );

		if ( $photo_id && ! wp_attachment_is_image( $photo_id ) ) {
			$photo_id = isset( $previous['person_photo_id'] ) ? absint( $previous['person_photo_id'] ) : 0;
			add_settings_error( self::OPTION_NAME, 'seogp_invalid_person_photo', __( 'The selected person photo is not a valid image.', 'seo-for-generatepress' ), 'error' );
		}

		return array(
			'delete_data_on_uninstall' => ! empty( $input['delete_data_on_uninstall'] ),
			'identity_type'             => $identity,
			'person_photo_id'           => $photo_id,
			'social_urls'               => $social_urls,
		);
	}

	/** Render the site identity section description. */
	public function render_site_identity_description() {
		echo '<p>' . esc_html__( 'Describe the person or organization behind this website. SEO for GeneratePress uses your existing WordPress Site Identity wherever possible.', 'seo-for-generatepress' ) . '</p>';
	}

	/** Render the identity-type selector. */
	public function render_identity_type_field() {
		$settings = $this->get_all();
		?>
		<select id="seogp-identity-type" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[identity_type]">
			<option value="organization" <?php selected( $settings['identity_type'], 'organization' ); ?>><?php esc_html_e( 'Organization', 'seo-for-generatepress' ); ?></option>
			<option value="person" <?php selected( $settings['identity_type'], 'person' ); ?>><?php esc_html_e( 'Person', 'seo-for-generatepress' ); ?></option>
		</select>
		<?php
	}

	/** Render the WordPress-managed identity values. */
	public function render_wordpress_identity_field() {
		$logo_id   = get_theme_mod( 'custom_logo' );
		$logo      = $logo_id ? wp_get_attachment_image( $logo_id, array( 80, 80 ), false, array( 'class' => 'seogp-site-logo' ) ) : '';
		$customize = admin_url( 'customize.php?autofocus%5Bsection%5D=title_tagline' );
		?>
		<div class="seogp-identity-summary">
			<?php if ( $logo ) : ?><?php echo wp_kses_post( $logo ); ?><?php endif; ?>
			<div>
				<strong><?php echo esc_html( get_bloginfo( 'name' ) ); ?></strong><br>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( home_url( '/' ) ); ?></a>
			</div>
		</div>
		<p class="description">
			<a href="<?php echo esc_url( $customize ); ?>"><?php esc_html_e( 'Edit Site Identity in the Customizer', 'seo-for-generatepress' ); ?></a>
			<?php esc_html_e( ' to change the name or logo.', 'seo-for-generatepress' ); ?>
		</p>
		<?php
	}

	/** Render the optional Person photo media control. */
	public function render_person_photo_field() {
		$settings = $this->get_all();
		$photo_id = absint( $settings['person_photo_id'] );
		?>
		<div class="seogp-person-photo">
			<div class="seogp-person-photo__preview"><?php echo $photo_id ? wp_kses_post( wp_get_attachment_image( $photo_id, array( 120, 120 ) ) ) : ''; ?></div>
			<input type="hidden" id="seogp-person-photo-id" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[person_photo_id]" value="<?php echo esc_attr( $photo_id ); ?>">
			<button type="button" class="button seogp-select-photo"><?php echo $photo_id ? esc_html__( 'Replace photo', 'seo-for-generatepress' ) : esc_html__( 'Choose photo', 'seo-for-generatepress' ); ?></button>
			<button type="button" class="button-link-delete seogp-remove-photo" <?php echo $photo_id ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove photo', 'seo-for-generatepress' ); ?></button>
		</div>
		<p class="description"><?php esc_html_e( 'Optional. This image is used only in Person structured data. If omitted, the Site Logo is used instead.', 'seo-for-generatepress' ); ?></p>
		<?php
	}

	/** Render repeatable social/profile URL controls. */
	public function render_social_urls_field() {
		$settings = $this->get_all();
		$urls     = ! empty( $settings['social_urls'] ) ? $settings['social_urls'] : array( '' );
		?>
		<div class="seogp-profile-urls" data-name="<?php echo esc_attr( self::OPTION_NAME ); ?>[social_urls][]">
			<?php foreach ( $urls as $url ) : ?>
				<div class="seogp-profile-url">
					<input type="url" class="regular-text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[social_urls][]" value="<?php echo esc_attr( $url ); ?>" placeholder="https://example.com/profile" inputmode="url">
					<button type="button" class="button-link-delete seogp-remove-profile"><?php esc_html_e( 'Remove', 'seo-for-generatepress' ); ?></button>
				</div>
			<?php endforeach; ?>
		</div>
		<button type="button" class="button seogp-add-profile"><?php esc_html_e( 'Add profile', 'seo-for-generatepress' ); ?></button>
		<p class="description"><?php esc_html_e( 'Add complete public profile URLs, including https://. Duplicate and invalid URLs will not be saved.', 'seo-for-generatepress' ); ?></p>
		<?php
	}

	/**
	 * Explain the data-management section.
	 *
	 * @return void
	 */
	public function render_data_management_description() {
		echo '<p>' . esc_html__( 'Choose what happens to plugin data if SEO for GeneratePress is deleted. Deactivation always preserves settings.', 'seo-for-generatepress' ) . '</p>';
	}

	/**
	 * Render the uninstall-data field.
	 *
	 * @return void
	 */
	public function render_delete_data_field() {
		$settings = $this->get_all();
		?>
		<label for="seogp-delete-data-on-uninstall">
			<input
				type="checkbox"
				id="seogp-delete-data-on-uninstall"
				name="<?php echo esc_attr( self::OPTION_NAME ); ?>[delete_data_on_uninstall]"
				value="1"
				<?php checked( $settings['delete_data_on_uninstall'] ); ?>
			>
			<?php esc_html_e( 'Delete all SEO for GeneratePress data when the plugin is deleted', 'seo-for-generatepress' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Leave this unchecked if you may reinstall the plugin later.', 'seo-for-generatepress' ); ?>
		</p>
		<?php
	}
}
