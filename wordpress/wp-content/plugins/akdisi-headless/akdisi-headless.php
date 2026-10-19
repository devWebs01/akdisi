<?php
/**
 * Plugin Name:       AKDISI Headless
 * Plugin URI:        https://akdisi.my.id
 * Description:       Satu plugin untuk seluruh konten "Info Website" — Proyek, Klien, FAQ, Testimoni. Register CPT, ekspos REST API untuk frontend Next.js, halaman Info Perusahaan, Gmail SMTP untuk form kontak, dan alat seed. Upload satu file, aktifkan, selesai.
 * Version:           2.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            AKDISI
 * License:           GPL-2.0-or-later
 * Text Domain:       akdisi
 *
 * Menggantikan plugin "AKDISI Info Manager" (v2) + paparan headless dari
 * patch terpisah. Aman diaktifkan bersama tema akdisi.
 *
 * @package AKDISI_Headless
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ==========================================================================
 * 1. Register CPT (Proyek, Klien, FAQ, Testimoni) + taksonomi kategori
 *    Cek post_type_exists() agar aman bila tema/plugin lain sudah mendaftarkan.
 * ========================================================================== */

function akdisi_hl_register_cpts() {
	if ( ! post_type_exists( 'akdisi_faq' ) ) {
		register_post_type(
			'akdisi_faq',
			array(
				'labels'       => array(
					'name'          => __( 'FAQ', 'akdisi' ),
					'singular_name' => __( 'FAQ', 'akdisi' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'menu_icon'    => 'dashicons-editor-help',
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor' ),
			)
		);
	}

	if ( ! post_type_exists( 'akdisi_testimonial' ) ) {
		register_post_type(
			'akdisi_testimonial',
			array(
				'labels'       => array(
					'name'          => __( 'Testimoni', 'akdisi' ),
					'singular_name' => __( 'Testimoni', 'akdisi' ),
				),
				'public'       => true,
				'has_archive'  => false,
				'menu_icon'    => 'dashicons-format-quote',
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor' ),
			)
		);
	}

	if ( ! post_type_exists( 'akdisi_project' ) ) {
		register_post_type(
			'akdisi_project',
			array(
				'labels'       => array(
					'name'          => __( 'Proyek', 'akdisi' ),
					'singular_name' => __( 'Proyek', 'akdisi' ),
				),
				'public'       => true,
				'has_archive'  => true,
				'menu_icon'    => 'dashicons-portfolio',
				'rewrite'      => array( 'slug' => 'projects', 'with_front' => false ),
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			)
		);
		register_taxonomy(
			'akdisi_project_cat',
			'akdisi_project',
			array(
				'labels'            => array( 'name' => __( 'Kategori Proyek', 'akdisi' ) ),
				'hierarchical'      => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'project-category' ),
			)
		);
	}

	if ( ! post_type_exists( 'akdisi_client' ) ) {
		register_post_type(
			'akdisi_client',
			array(
				'labels'       => array(
					'name'          => __( 'Klien', 'akdisi' ),
					'singular_name' => __( 'Klien', 'akdisi' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_rest' => true,
				'has_archive'  => false,
				'menu_icon'    => 'dashicons-groups',
				'supports'     => array( 'title', 'page-attributes' ),
			)
		);
	}
}
add_action( 'init', 'akdisi_hl_register_cpts', 5 );

/* ==========================================================================
 * 2. Dukungan `custom-fields` — tanpa ini field `meta` tidak muncul di REST
 * ========================================================================== */

function akdisi_hl_post_type_support() {
	foreach ( array( 'akdisi_project', 'akdisi_client', 'akdisi_faq', 'akdisi_testimonial' ) as $type ) {
		if ( post_type_exists( $type ) ) {
			add_post_type_support( $type, 'custom-fields' );
		}
	}
}
add_action( 'init', 'akdisi_hl_post_type_support', 20 );

/* ==========================================================================
 * 3. Daftar meta yang dibaca frontend (show_in_rest => true)
 * ========================================================================== */

function akdisi_hl_register_meta() {
	if ( post_type_exists( 'akdisi_testimonial' ) ) {
		register_post_meta(
			'akdisi_testimonial',
			'role',
			array(
				'type'         => 'string',
				'single'       => true,
				'show_in_rest' => true,
			)
		);
	}

	if ( post_type_exists( 'akdisi_project' ) ) {
		foreach ( array( 'client', 'role', 'status', 'frontend_slug' ) as $key ) {
			register_post_meta(
				'akdisi_project',
				$key,
				array(
					'type'         => 'string',
					'single'       => true,
					'show_in_rest' => true,
				)
			);
		}
		// `content` reserved (post_content) — daftar struktur pakai `content_list`.
		foreach ( array( 'work', 'content_list' ) as $key ) {
			register_post_meta(
				'akdisi_project',
				$key,
				array(
					'type'         => 'string',
					'single'       => true,
					'show_in_rest' => true,
				)
			);
		}
		register_post_meta(
			'akdisi_project',
			'visit_url',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		register_post_meta(
			'akdisi_project',
			'akdisi_project_gallery',
			array(
				'type'         => 'array',
				'single'       => true,
				'show_in_rest' => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'integer' ),
					),
				),
				'sanitize_callback' => 'akdisi_hl_sanitize_gallery',
			)
		);
	}
}
add_action( 'init', 'akdisi_hl_register_meta', 20 );

function akdisi_hl_sanitize_gallery( $value ) {
	if ( is_array( $value ) ) {
		$ids = $value;
	} elseif ( is_string( $value ) && '' !== trim( $value, " \t\n\r[]" ) ) {
		$ids = preg_split( '/[\s,]+/', trim( $value, " \t\n\r[]" ) );
	} else {
		return absint( $value );
	}
	return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
}

/* ==========================================================================
 * 4. Metabox "Detail Proyek" (UI admin untuk meta proyek)
 * ========================================================================== */

function akdisi_hl_add_project_meta_box() {
	add_meta_box(
		'akdisi_hl_project_details',
		__( 'Detail Proyek', 'akdisi' ),
		'akdisi_hl_render_project_meta_box',
		'akdisi_project',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'akdisi_hl_add_project_meta_box' );

function akdisi_hl_render_project_meta_box( $post ) {
	wp_nonce_field( 'akdisi_hl_project_save', 'akdisi_hl_project_nonce' );
	$gallery = akdisi_hl_sanitize_gallery( get_post_meta( $post->ID, 'akdisi_project_gallery', true ) );
	?>
	<style>
		.akdisi-hl-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
		.akdisi-hl-field{margin-bottom:14px}
		.akdisi-hl-field label{display:block;font-weight:600;margin-bottom:4px}
		.akdisi-hl-field input[type=text],.akdisi-hl-field input[type=url],.akdisi-hl-field textarea{width:100%}
		.akdisi-hl-hint{color:#78716c;font-size:12px;margin-top:2px}
		.akdisi-hl-gallery{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:8px;margin-top:8px}
		.akdisi-hl-gallery img{width:100%;height:80px;object-fit:cover;border-radius:6px;display:block}
	</style>
	<div class="akdisi-hl-grid">
		<div class="akdisi-hl-field">
			<label for="akdisi_hl_excerpt"><?php esc_html_e( 'Deskripsi singkat', 'akdisi' ); ?></label>
			<textarea id="akdisi_hl_excerpt" name="akdisi_hl_excerpt" rows="3"><?php echo esc_textarea( get_the_excerpt( $post ) ); ?></textarea>
			<p class="akdisi-hl-hint"><?php esc_html_e( 'Dipakai untuk meta description, kutipan di hero, dan arsip.', 'akdisi' ); ?></p>
		</div>
		<div class="akdisi-hl-field">
			<label for="akdisi_hl_visit_url"><?php esc_html_e( 'URL kunjungan situs', 'akdisi' ); ?></label>
			<input type="url" id="akdisi_hl_visit_url" name="akdisi_hl_visit_url" value="<?php echo esc_url( get_post_meta( $post->ID, 'visit_url', true ) ); ?>" placeholder="https://contoh-demo.com">
			<p class="akdisi-hl-hint"><?php esc_html_e( 'Tombol "Kunjungi situs" di halaman detail proyek.', 'akdisi' ); ?></p>
		</div>
		<div class="akdisi-hl-field">
			<label for="akdisi_hl_client"><?php esc_html_e( 'Klien', 'akdisi' ); ?></label>
			<input type="text" id="akdisi_hl_client" name="akdisi_hl_client" value="<?php echo esc_attr( get_post_meta( $post->ID, 'client', true ) ); ?>">
		</div>
		<div class="akdisi-hl-field">
			<label for="akdisi_hl_role"><?php esc_html_e( 'Peran', 'akdisi' ); ?></label>
			<input type="text" id="akdisi_hl_role" name="akdisi_hl_role" value="<?php echo esc_attr( get_post_meta( $post->ID, 'role', true ) ); ?>" placeholder="Desain + Bangun">
		</div>
		<div class="akdisi-hl-field">
			<label for="akdisi_hl_status"><?php esc_html_e( 'Status', 'akdisi' ); ?></label>
			<input type="text" id="akdisi_hl_status" name="akdisi_hl_status" value="<?php echo esc_attr( get_post_meta( $post->ID, 'status', true ) ); ?>" placeholder="Tayang">
		</div>
		<div class="akdisi-hl-field">
			<label for="akdisi_hl_frontend_slug"><?php esc_html_e( 'Slug frontend (URL akdisi.my.id)', 'akdisi' ); ?></label>
			<input type="text" id="akdisi_hl_frontend_slug" name="akdisi_hl_frontend_slug" value="<?php echo esc_attr( get_post_meta( $post->ID, 'frontend_slug', true ) ); ?>" placeholder="akar-solution-website">
			<p class="akdisi-hl-hint"><?php esc_html_e( 'Slug pendek yang dipakai di URL frontend. Kosongkan = pakai slug WP.', 'akdisi' ); ?></p>
		</div>
	</div>
	<div class="akdisi-hl-field">
		<label for="akdisi_hl_work"><?php esc_html_e( 'Apa yang dikerjakan (satu per baris)', 'akdisi' ); ?></label>
		<textarea id="akdisi_hl_work" name="akdisi_hl_work" rows="5"><?php echo esc_textarea( get_post_meta( $post->ID, 'work', true ) ); ?></textarea>
	</div>
	<div class="akdisi-hl-field">
		<label for="akdisi_hl_content_list"><?php esc_html_e( 'Isi konten website (satu per baris)', 'akdisi' ); ?></label>
		<textarea id="akdisi_hl_content_list" name="akdisi_hl_content_list" rows="6"><?php echo esc_textarea( get_post_meta( $post->ID, 'content_list', true ) ); ?></textarea>
	</div>
	<div class="akdisi-hl-field">
		<label><?php esc_html_e( 'Galeri gambar', 'akdisi' ); ?></label>
		<button type="button" class="button" id="akdisi_hl_gallery_open"><?php esc_html_e( 'Pilih / Kelola gambar', 'akdisi' ); ?></button>
		<button type="button" class="button" id="akdisi_hl_gallery_clear"<?php echo $gallery ? '' : ' style="display:none"'; ?>><?php esc_html_e( 'Kosongkan', 'akdisi' ); ?></button>
		<input type="hidden" id="akdisi_hl_gallery" name="akdisi_project_gallery" value="<?php echo esc_attr( implode( ',', $gallery ) ); ?>">
		<div class="akdisi-hl-gallery" id="akdisi_hl_gallery_preview"></div>
		<p class="akdisi-hl-hint"><?php esc_html_e( 'Tampil sebagai grid di halaman detail proyek.', 'akdisi' ); ?></p>
	</div>
	<?php
}

function akdisi_hl_project_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	if ( 'akdisi_project' !== get_post_type() ) {
		return;
	}
	wp_enqueue_media();
	wp_add_inline_script(
		'jquery',
		'(function($){
			function render(ids){
				var $prev=$("#akdisi_hl_gallery_preview");
				$prev.empty();
				$.each(ids,function(i,id){
					var att=wp.media.attachment(id);
					var $img=$("<img>").attr("alt","").attr("loading","lazy");
					$img.appendTo($prev);
					att.fetch().done(function(){
						var u=att.get("sizes")&&att.get("sizes").thumbnail?att.get("sizes").thumbnail.url:att.get("url");
						$img.attr("src",u);
					});
				});
			}
			function ids(){return ($("#akdisi_hl_gallery").val()||"").split(",").map(Number).filter(Boolean);}
			function set(ids){$("#akdisi_hl_gallery").val(ids.join(","));render(ids);$("#akdisi_hl_gallery_clear")[ids.length?"show":"hide"]();}
			$(function(){
				render(ids());
				$("#akdisi_hl_gallery_open").on("click",function(e){e.preventDefault();
					var frame=wp.media({title:"Pilih Gambar Galeri",button:{text:"Pilih"},multiple:true,library:{type:"image"}});
					frame.on("select",function(){
						var ids=[];
						frame.state().get("selection").each(function(a){ids.push(parseInt(a.id,10));});
						set(ids);
					});
					frame.open();
				});
				$("#akdisi_hl_gallery_clear").on("click",function(e){e.preventDefault();set([]);});
			});
		})(jQuery);',
		'after'
	);
}
add_action( 'admin_enqueue_scripts', 'akdisi_hl_project_admin_assets' );

function akdisi_hl_save_project( $post_id ) {
	if ( ! isset( $_POST['akdisi_hl_project_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['akdisi_hl_project_nonce'] ) ), 'akdisi_hl_project_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( array( 'client', 'role', 'status', 'frontend_slug', 'visit_url', 'work', 'content_list' ) as $key ) {
		if ( isset( $_POST[ 'akdisi_hl_' . $key ] ) ) {
			$value = sanitize_text_field( wp_unslash( $_POST[ 'akdisi_hl_' . $key ] ) );
			if ( 'visit_url' === $key ) {
				$value = esc_url_raw( $value );
			}
			update_post_meta( $post_id, $key, $value );
		}
	}
	if ( isset( $_POST['akdisi_project_gallery'] ) ) {
		$ids = array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['akdisi_project_gallery'] ) ) ) );
		$ids = array_values( array_filter( $ids ) );
		if ( $ids ) {
			update_post_meta( $post_id, 'akdisi_project_gallery', $ids );
		} else {
			delete_post_meta( $post_id, 'akdisi_project_gallery' );
		}
	}

	if ( isset( $_POST['akdisi_hl_excerpt'] ) ) {
		$excerpt = sanitize_textarea_field( wp_unslash( $_POST['akdisi_hl_excerpt'] ) );
		if ( $excerpt !== get_post_field( 'post_excerpt', $post_id ) ) {
			remove_action( 'save_post_akdisi_project', __FUNCTION__ );
			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_excerpt' => $excerpt,
				)
			);
			add_action( 'save_post_akdisi_project', __FUNCTION__ );
		}
	}
}
add_action( 'save_post_akdisi_project', 'akdisi_hl_save_project' );

/* ==========================================================================
 * 5. Info Perusahaan — opsi `akdisi_info_contact` + halaman admin
 * ========================================================================== */

function akdisi_hl_contact_defaults() {
	return array(
		'email'    => 'hello@akdisi.com',
		'phone'    => '+62 812-3456-7890',
		'whatsapp' => '6281234567890',
		'location' => 'Jambi, Indonesia',
		'hours'    => 'Senin–Jumat, 09.00–17.00 WIB. Permintaan singkat biasanya dibalas di hari yang sama.',
		'address'  => '',
	);
}

/**
 * Helper publik — dipakai tema lewat function_exists('akdisi_im_contact').
 *
 * @return array<string,string>
 */
function akdisi_im_contact() {
	$saved = get_option( 'akdisi_info_contact', array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return array_merge( akdisi_hl_contact_defaults(), $saved );
}

function akdisi_hl_sanitize_contact( $input ) {
	$input = is_array( $input ) ? $input : array();
	return array(
		'email'    => sanitize_email( $input['email'] ?? '' ),
		'phone'    => sanitize_text_field( $input['phone'] ?? '' ),
		'whatsapp' => preg_replace( '/[^0-9]/', '', (string) ( $input['whatsapp'] ?? '' ) ),
		'location' => sanitize_text_field( $input['location'] ?? '' ),
		'hours'    => sanitize_textarea_field( $input['hours'] ?? '' ),
		'address'  => sanitize_textarea_field( $input['address'] ?? '' ),
	);
}

function akdisi_hl_admin_menu() {
	add_menu_page(
		__( 'Info Website', 'akdisi' ),
		__( 'Info Website', 'akdisi' ),
		'manage_options',
		'akdisi-headless',
		'akdisi_hl_render_company_page',
		'dashicons-admin-settings',
		26
	);
	add_submenu_page(
		'akdisi-headless',
		__( 'Info Perusahaan', 'akdisi' ),
		__( 'Info Perusahaan', 'akdisi' ),
		'manage_options',
		'akdisi-headless',
		'akdisi_hl_render_company_page'
	);
	add_submenu_page(
		'akdisi-headless',
		__( 'Kirim Email', 'akdisi' ),
		__( 'Kirim Email', 'akdisi' ),
		'manage_options',
		'akdisi-hl-email',
		'akdisi_hl_render_email_page'
	);
}
add_action( 'admin_menu', 'akdisi_hl_admin_menu' );

function akdisi_hl_register_settings() {
	register_setting( 'akdisi_hl_contact_group', 'akdisi_info_contact', array( 'sanitize_callback' => 'akdisi_hl_sanitize_contact' ) );
	register_setting( 'akdisi_hl_email_group', 'akdisi_hl_smtp', array( 'sanitize_callback' => 'akdisi_hl_sanitize_smtp' ) );
}
add_action( 'admin_init', 'akdisi_hl_register_settings' );

function akdisi_hl_render_company_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$c = akdisi_im_contact();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Info Perusahaan', 'akdisi' ); ?></h1>
		<p class="description">
			<?php esc_html_e( 'Kontak ini tampil di footer, halaman Kontak, WhatsApp, dan data SEO situs.', 'akdisi' ); ?>
			<strong><?php esc_html_e( 'Nama & tagline perusahaan diatur di Settings → General.', 'akdisi' ); ?></strong>
		</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'akdisi_hl_contact_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="akdisi_hl_email"><?php esc_html_e( 'Email', 'akdisi' ); ?></label></th>
					<td><input type="email" id="akdisi_hl_email" name="akdisi_info_contact[email]" value="<?php echo esc_attr( $c['email'] ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="akdisi_hl_phone"><?php esc_html_e( 'Telepon (tampilan)', 'akdisi' ); ?></label></th>
					<td><input type="text" id="akdisi_hl_phone" name="akdisi_info_contact[phone]" value="<?php echo esc_attr( $c['phone'] ); ?>" class="regular-text" placeholder="+62 812-3456-7890"></td>
				</tr>
				<tr>
					<th scope="row"><label for="akdisi_hl_wa"><?php esc_html_e( 'WhatsApp (angka penuh, tanpa +)', 'akdisi' ); ?></label></th>
					<td><input type="text" id="akdisi_hl_wa" name="akdisi_info_contact[whatsapp]" value="<?php echo esc_attr( $c['whatsapp'] ); ?>" class="regular-text" placeholder="6281234567890"></td>
				</tr>
				<tr>
					<th scope="row"><label for="akdisi_hl_location"><?php esc_html_e( 'Lokasi', 'akdisi' ); ?></label></th>
					<td><input type="text" id="akdisi_hl_location" name="akdisi_info_contact[location]" value="<?php echo esc_attr( $c['location'] ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="akdisi_hl_hours"><?php esc_html_e( 'Jam kerja', 'akdisi' ); ?></label></th>
					<td><textarea id="akdisi_hl_hours" name="akdisi_info_contact[hours]" rows="2" class="large-text"><?php echo esc_textarea( $c['hours'] ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="akdisi_hl_address"><?php esc_html_e( 'Alamat lengkap (opsional)', 'akdisi' ); ?></label></th>
					<td><textarea id="akdisi_hl_address" name="akdisi_info_contact[address]" rows="3" class="large-text"><?php echo esc_textarea( $c['address'] ); ?></textarea></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/* ==========================================================================
 * 6. Kirim Email — Gmail SMTP gratis (App Password) untuk semua wp_mail()
 * ========================================================================== */

function akdisi_hl_smtp_opts() {
	$o = get_option( 'akdisi_hl_smtp', array() );
	if ( ! is_array( $o ) ) {
		$o = array();
	}
	$opts = array(
		'user'         => sanitize_email( $o['user'] ?? '' ),
		'app_password' => (string) ( $o['app_password'] ?? '' ),
		'from_name'    => sanitize_text_field( $o['from_name'] ?? '' ),
		'host'         => sanitize_text_field( $o['host'] ?? '' ) ?: 'smtp.gmail.com',
		'port'         => absint( $o['port'] ?? 0 ) ?: 587,
		'secure'       => in_array( $o['secure'] ?? 'tls', array( 'tls', 'ssl', 'none' ), true ) ? ( $o['secure'] ?? 'tls' ) : 'tls',
	);
	$opts['ready'] = '' !== $opts['user'] && '' !== $opts['app_password'];
	return $opts;
}

function akdisi_hl_sanitize_smtp( $input ) {
	$input = is_array( $input ) ? $input : array();
	$old   = (array) get_option( 'akdisi_hl_smtp', array() );
	$pass  = strtoupper( str_replace( ' ', '', sanitize_text_field( $input['app_password'] ?? '' ) ) );
	if ( '' === $pass ) {
		$pass = (string) ( $old['app_password'] ?? '' );
	}
	return array(
		'user'         => sanitize_email( $input['user'] ?? '' ),
		'app_password' => $pass,
		'from_name'    => sanitize_text_field( $input['from_name'] ?? '' ),
		'host'         => sanitize_text_field( $input['host'] ?? '' ) ?: 'smtp.gmail.com',
		'port'         => absint( $input['port'] ?? 0 ) ?: 587,
		'secure'       => in_array( $input['secure'] ?? 'tls', array( 'tls', 'ssl', 'none' ), true ) ? ( $input['secure'] ?? 'tls' ) : 'tls',
	);
}

function akdisi_hl_phpmailer( $phpmailer ) {
	$o = akdisi_hl_smtp_opts();
	if ( ! $o['ready'] ) {
		return;
	}
	$phpmailer->isSMTP();
	$phpmailer->Host       = $o['host'];
	$phpmailer->Port       = $o['port'];
	$phpmailer->SMTPAuth   = true;
	$phpmailer->Username   = $o['user'];
	$phpmailer->Password   = $o['app_password'];
	if ( 'ssl' === $o['secure'] ) {
		$phpmailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
	} elseif ( 'tls' === $o['secure'] ) {
		$phpmailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
	}
	$phpmailer->From     = $o['user'];
	$phpmailer->FromName = $o['from_name'] ?: get_bloginfo( 'name' );
}
add_action( 'phpmailer_init', 'akdisi_hl_phpmailer' );

function akdisi_hl_capture_mail_error() {
	add_action(
		'wp_mail_failed',
		static function ( $wp_error ) {
			if ( is_wp_error( $wp_error ) ) {
				$GLOBALS['akdisi_hl_mail_error'] = $wp_error->get_error_message();
			}
		}
	);
}

function akdisi_hl_handle_test_mail() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Akses ditolak.', 'akdisi' ) );
	}
	check_admin_referer( 'akdisi_hl_test_mail' );
	$o    = akdisi_hl_smtp_opts();
	$args = array( 'page' => 'akdisi-hl-email' );
	if ( ! $o['ready'] ) {
		$args['hl_test'] = 'unconfigured';
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}
	$to = sanitize_email( wp_unslash( $_POST['hl_test_to'] ?? '' ) );
	if ( ! is_email( $to ) ) {
		$to = get_option( 'admin_email' );
	}
	akdisi_hl_capture_mail_error();
	$subject = sprintf( '[%s] Email percobaan — konfigurasi SMTP berhasil', get_bloginfo( 'name' ) );
	$body    = "Konfigurasi Gmail SMTP situs Anda sudah benar.\n\n"
		. "Email ini dikirim otomatis dari halaman Info Website → Kirim Email.\n"
		. 'Situs: ' . home_url( '/' ) . "\n"
		. 'Waktu: ' . gmdate( 'Y-m-d H:i:s' ) . " UTC\n";
	$sent = wp_mail( $to, $subject, $body );
	$args['hl_test'] = $sent ? 'ok' : 'fail';
	if ( ! $sent && ! empty( $GLOBALS['akdisi_hl_mail_error'] ) ) {
		$args['hl_test_msg'] = rawurlencode( $GLOBALS['akdisi_hl_mail_error'] );
	}
	wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_akdisi_hl_test_mail', 'akdisi_hl_handle_test_mail' );

function akdisi_hl_render_email_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o     = akdisi_hl_smtp_opts();
	$notif = '';
	if ( isset( $_GET['hl_test'] ) ) {
		$state = sanitize_key( wp_unslash( $_GET['hl_test'] ) );
		if ( 'ok' === $state ) {
			$notif = '<div class="notice notice-success"><p><strong>' . esc_html__( 'Email percobaan terkirim. Cek inbox tujuan.', 'akdisi' ) . '</strong></p></div>';
		} elseif ( 'fail' === $state ) {
			$msg   = isset( $_GET['hl_test_msg'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['hl_test_msg'] ) ) ) : '';
			$notif = '<div class="notice notice-error"><p><strong>' . esc_html__( 'Gagal mengirim:', 'akdisi' ) . '</strong> ' . esc_html( $msg ?: __( 'periksa alamat & App Password.', 'akdisi' ) ) . '</p></div>';
		} elseif ( 'unconfigured' === $state ) {
			$notif = '<div class="notice notice-warning"><p>' . esc_html__( 'Isi Gmail & App Password dulu, simpan, lalu tes kirim.', 'akdisi' ) . '</p></div>';
		}
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Kirim Email', 'akdisi' ); ?></h1>
		<?php if ( $notif ) { echo wp_kses_post( $notif ); } ?>
		<?php if ( $o['ready'] ) : ?>
			<div class="notice notice-info"><p><?php printf( esc_html__( 'Aktif — semua email situs dikirim via %s.', 'akdisi' ), '<strong>' . esc_html( $o['user'] ) . '</strong>' ); ?></p></div>
		<?php else : ?>
			<div class="notice notice-warning"><p><?php esc_html_e( 'Belum terkonfigurasi — email dari form kontak belum terkirim ke inbox.', 'akdisi' ); ?></p></div>
		<?php endif; ?>
		<h2><?php esc_html_e( 'Cara membuat App Password Gmail (gratis)', 'akdisi' ); ?></h2>
		<ol style="max-width:640px">
			<li><?php esc_html_e( 'Buka myaccount.google.com → Keamanan.', 'akdisi' ); ?></li>
			<li><?php esc_html_e( 'Aktifkan Verifikasi 2 Langkah (wajib).', 'akdisi' ); ?></li>
			<li><?php esc_html_e( 'Cari "App passwords" → buat baru (nama bebas).', 'akdisi' ); ?></li>
			<li><?php esc_html_e( 'Salin password 16 karakter, tempel di kolom bawah.', 'akdisi' ); ?></li>
			<li><?php esc_html_e( 'Simpan, lalu klik "Kirim email percobaan".', 'akdisi' ); ?></li>
		</ol>
		<form method="post" action="options.php">
			<?php settings_fields( 'akdisi_hl_email_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="akdisi_hl_user"><?php esc_html_e( 'Alamat Gmail', 'akdisi' ); ?></label></th>
					<td>
						<input type="email" id="akdisi_hl_user" name="akdisi_hl_smtp[user]" value="<?php echo esc_attr( $o['user'] ); ?>" class="regular-text" placeholder="namasaya@gmail.com">
						<p class="description"><?php esc_html_e( 'Juga dipakai sebagai alamat pengirim (From).', 'akdisi' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="akdisi_hl_pass"><?php esc_html_e( 'App Password (16 karakter)', 'akdisi' ); ?></label></th>
					<td>
						<input type="password" id="akdisi_hl_pass" name="akdisi_hl_smtp[app_password]" value="" class="regular-text code" autocomplete="new-password" placeholder="<?php echo $o['app_password'] ? '•••• •••• •••• ••••' : 'abcd efgh ijkl mnop'; ?>">
						<p class="description"><?php esc_html_e( 'Biarkan kosong untuk memakai password tersimpan.', 'akdisi' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="akdisi_hl_from"><?php esc_html_e( 'Nama pengirim', 'akdisi' ); ?></label></th>
					<td><input type="text" id="akdisi_hl_from" name="akdisi_hl_smtp[from_name]" value="<?php echo esc_attr( $o['from_name'] ); ?>" class="regular-text" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="akdisi_hl_host"><?php esc_html_e( 'Host SMTP', 'akdisi' ); ?></label></th>
					<td>
						<input type="text" id="akdisi_hl_host" name="akdisi_hl_smtp[host]" value="<?php echo esc_attr( $o['host'] ); ?>" class="regular-text">
						<p class="description"><?php esc_html_e( 'Default Gmail: smtp.gmail.com, port 587, TLS.', 'akdisi' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="akdisi_hl_port"><?php esc_html_e( 'Port & enkripsi', 'akdisi' ); ?></label></th>
					<td>
						<input type="number" id="akdisi_hl_port" name="akdisi_hl_smtp[port]" value="<?php echo esc_attr( (string) $o['port'] ); ?>" class="small-text">
						<span style="margin-left:16px">
							<label for="akdisi_hl_secure"><?php esc_html_e( 'Enkripsi', 'akdisi' ); ?></label>
							<select id="akdisi_hl_secure" name="akdisi_hl_smtp[secure]">
								<option value="tls" <?php selected( $o['secure'], 'tls' ); ?>>TLS</option>
								<option value="ssl" <?php selected( $o['secure'], 'ssl' ); ?>>SSL</option>
								<option value="none" <?php selected( $o['secure'], 'none' ); ?>><?php esc_html_e( 'Tanpa', 'akdisi' ); ?></option>
							</select>
						</span>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<hr>
		<h2><?php esc_html_e( 'Kirim email percobaan', 'akdisi' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="akdisi_hl_test_mail">
			<?php wp_nonce_field( 'akdisi_hl_test_mail' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="akdisi_hl_test_to"><?php esc_html_e( 'Kirim ke', 'akdisi' ); ?></label></th>
					<td>
						<input type="email" id="akdisi_hl_test_to" name="hl_test_to" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" class="regular-text">
						<?php submit_button( __( 'Kirim email percobaan', 'akdisi' ), 'secondary', 'submit', false ); ?>
					</td>
				</tr>
			</table>
		</form>
	</div>
	<?php
}

/* ==========================================================================
 * 7. Route REST: info perusahaan (read-only)
 * ========================================================================== */

function akdisi_hl_info_payload() {
	$raw = get_option( 'akdisi_info_contact' );
	if ( ! is_array( $raw ) ) {
		$raw = array();
	}
	$info = array_merge( akdisi_hl_contact_defaults(), array_intersect_key( $raw, akdisi_hl_contact_defaults() ) );
	if ( '' !== $info['whatsapp'] && ! preg_match( '#^https?://#i', $info['whatsapp'] ) ) {
		$info['whatsapp'] = 'https://wa.me/' . preg_replace( '/\D/', '', $info['whatsapp'] );
	}
	return $info;
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'akdisi/v1',
			'/info',
			array(
				'methods'             => 'GET',
				'callback'            => function () {
					return rest_ensure_response( akdisi_hl_info_payload() );
				},
				'permission_callback' => '__return_true',
			)
		);
	}
);

/* ==========================================================================
 * 8. Alat seed sekali jalankan (Tools -> Seed Headless Meta)
 * ========================================================================== */

function akdisi_hl_seed() {
	$projects = array(
		array(
			'wp_id'          => 134,
			'frontend_slug' => 'akar-solution-website',
			'work'          => array( 'Riset konten & struktur informasi', 'Desain UI/UX dengan identitas visual baru', 'Pengembangan situs multi-halaman berperforma cepat', 'SEO teknis, halaman layanan, dan artikel insight', 'Pelatihan pengelolaan konten untuk tim internal' ),
			'content'       => array( 'Beranda — positioning layanan & bukti kerja', 'Layanan — satu halaman detail per layanan', 'Harga & paket yang transparan', 'Portofolio proyek & studi kasus', 'Blog / insight untuk mesin pencari', 'Kontak & formulir brief proyek' ),
		),
		array(
			'wp_id'          => 133,
			'frontend_slug' => 'glad2glow',
			'work'          => array( 'Struktur katalog & kategori produk', 'Desain antarmuka ramah pembeli muda', 'Alur belanja & checkout', 'Integrasi pembayaran & pengiriman', 'Konten produk: kandungan, cara pakai, ulasan' ),
			'content'       => array( 'Beranda — kampanye & produk unggulan', 'Katalog per kategori & masalah kulit', 'Detail produk — kandungan, cara pakai, ulasan', 'Rutinitas per tipe kulit', 'Keranjang & checkout', 'Promo & bundling' ),
		),
		array(
			'wp_id'          => 132,
			'frontend_slug' => 'sistem-kas-tanah-adat',
			'work'          => array( 'Pemetaan alur kas & kebutuhan laporan', 'Desain pencatatan yang sederhana untuk pengurus', 'Pengembangan sistem & uji coba bersama', 'Pendampingan penggunaan harian' ),
			'content'       => array( 'Dashboard saldo & arus kas', 'Pencatatan transaksi masuk & keluar', 'Bukti digital — nota & foto', 'Laporan periodik siap audit', 'Hak akses pengurus & bendahara' ),
		),
		array(
			'wp_id'          => 131,
			'frontend_slug' => 'wedding-gallery',
			'work'          => array( 'Kurasi & struktur katalog vendor', 'Desain halaman portofolio vendor', 'Alur tanya-jawab & permintaan penawaran' ),
			'content'       => array( 'Beranda — layanan unggulan & suasana acara', 'Katalog vendor per kategori', 'Portofolio & studi kasus', 'Blog & artikel', 'Kontak & penawaran' ),
		),
		array(
			'wp_id'          => 130,
			'frontend_slug' => 'expression-hairsalon',
			'work'          => array( 'Pemetaan layanan & harga salon', 'Desain identitas visual & katalog', 'Pengembangan situs & reservasi', 'SEO lokal untuk pencarian salon', 'Pelatihan pengelolaan artikel' ),
			'content'       => array( 'Beranda — layanan unggulan & suasana salon', 'Layanan & daftar harga', 'Cerita brand / tentang', 'Blog — tips perawatan rambut', 'Reservasi & kontak' ),
		),
		array(
			'wp_id'          => 129,
			'frontend_slug' => 'klinik-dokter-abc',
			'work'          => array( 'Pemetaan alur layanan pasien', 'Desain pintu masuk aman & hak akses', 'Pengembangan modul inti administrasi', 'Uji coba bersama staf klinik' ),
			'content'       => array( 'Login aman & manajemen peran', 'Data pasien & riwayat kunjungan', 'Jadwal dokter & pendaftaran', 'Antrean & status layanan', 'Laporan harian & bulanan' ),
		),
		array(
			'wp_id'          => 128,
			'frontend_slug' => 'nusantara-kost',
			'work'          => array( 'Katalog kamar & fasilitas', 'Alur pemesanan yang ringkas', 'Integrasi kontak WhatsApp', 'Optimasi informasi lokasi & akses' ),
			'content'       => array( 'Beranda — kamar yang tersedia', 'Detail kamar & fasilitas', 'Lokasi & peta akses', 'Pemesanan via WhatsApp', 'FAQ penghuni' ),
		),
		array(
			'wp_id'          => 127,
			'frontend_slug' => 'futsalku',
			'work'          => array( 'Desain portal & katalog lapangan', 'Alur pemesanan & akun anggota', 'Informasi jadwal & lokasi', 'Pengembangan & peluncuran' ),
			'content'       => array( 'Beranda & ketersediaan jadwal', 'Halaman lapangan — harga & fasilitas', 'Akun anggota & riwayat pemesanan', 'Lokasi & kontak', 'Promo & turnamen' ),
		),
		array(
			'wp_id'          => 126,
			'frontend_slug' => 'laundry-al-farizi',
			'work'          => array( 'Digitalisasi alur order', 'Desain login aman & peran staf', 'Pengembangan pelacakan pesanan', 'Pendampingan operasional harian' ),
			'content'       => array( 'Login & hak akses staf', 'Input pesanan & data pelanggan', 'Pelacakan status: dicuci, disetrika, siap', 'Nota & pembayaran', 'Laporan harian' ),
		),
		array(
			'wp_id'          => 125,
			'frontend_slug' => 'sibanyu',
			'work'          => array( 'Arsitektur kategori & navigasi', 'Desain beranda dinamis', 'Pengembangan pencarian & optimasi performa', 'Slot iklan yang bisa dikelola sendiri', 'Pelatihan alur kerja redaksi' ),
			'content'       => array( 'Beranda — headline & berita terbaru', 'Kategori berita daerah', 'Halaman artikel & profil penulis', 'Pencarian', 'Manajemen iklan', 'Redaksi & kontak' ),
		),
	);

	$updated = 0;
	foreach ( $projects as $row ) {
		$post = get_post( $row['wp_id'] );
		if ( ! $post || 'akdisi_project' !== $post->post_type ) {
			continue;
		}
		update_post_meta( $post->ID, 'frontend_slug', $row['frontend_slug'] );
		update_post_meta( $post->ID, 'work', implode( "\n", $row['work'] ) );
		update_post_meta( $post->ID, 'content_list', implode( "\n", $row['content'] ) );
		++$updated;
	}

	$testimonials = array(
		array( 'slug' => 'fajar-rizki', 'role' => 'Founder, Gelora Events' ),
		array( 'slug' => 'devi-anggraini', 'role' => 'CFO, TokoHemat' ),
		array( 'slug' => 'budi-pratama', 'role' => 'Mitra, Arah Properti' ),
		array( 'slug' => 'nita-haryanto', 'role' => 'Direktur, Sehati Network' ),
		array( 'slug' => 'andri-setiawan', 'role' => 'Lead Operasional, Tokobersama' ),
	);

	foreach ( $testimonials as $row ) {
		$posts = get_posts(
			array(
				'post_type'      => 'akdisi_testimonial',
				'posts_per_page' => 1,
				'name'            => $row['slug'],
				'fields'         => 'ids',
			)
		);
		if ( $posts ) {
			update_post_meta( $posts[0], 'role', $row['role'] );
			++$updated;
		}
	}

	return array( 'updated' => $updated );
}

add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'tools.php',
			'Seed Headless Meta',
			'Seed Headless Meta',
			'manage_options',
			'seed-headless-meta',
			function () {
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}
				if ( isset( $_POST['akdisi_run_seed'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['akdisi_seed_seed_nonce'] ) ), 'akdisi_seed_headless' ) ) {
					$result = akdisi_hl_seed();
					printf( '<div class="notice notice-success"><p>Selesai — %d record diperbarui.</p></div>', (int) $result['updated'] );
				}
				?>
				<div class="wrap">
					<h1>Seed Headless Meta</h1>
					<p>Isi meta <code>frontend_slug</code>, <code>work</code>, <code>content_list</code> untuk 10 proyek dan <code>role</code> untuk 5 testimoni. Idempoten.</p>
					<form method="post">
						<?php wp_nonce_field( 'akdisi_seed_headless', 'akdisi_seed_seed_nonce' ); ?>
						<p><button type="submit" name="akdisi_run_seed" class="button button-primary">Jalankan Seed</button></p>
					</form>
				</div>
				<?php
			}
		);
	}
);
