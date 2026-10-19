<?php
/**
 * Plugin Name:       AKDISI Headless
 * Plugin URI:        https://akdisi.my.id
 * Description:       Menampilkan konten "Info Website" (Proyek, Klien, FAQ, Testimoni) ke frontend headless Next.js lewat REST API — plus alat seed sekali jalankan. Upload satu file ini, aktifkan, selesai.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            AKDISI
 * License:           GPL-2.0-or-later
 * Text Domain:       akdisi
 *
 * @package AKDISI_Headless
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ==========================================================================
 * 1. Pastikan 4 CPT terdaftar (aman dijalankan walau plugin lain tidak aktif)
 * ========================================================================== */

function akdisi_headless_register_cpts() {
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
add_action( 'init', 'akdisi_headless_register_cpts', 5 );

/* ==========================================================================
 * 2. Dukungan `custom-fields` — tanpa ini field `meta` tidak muncul di REST
 * ========================================================================== */

function akdisi_headless_post_type_support() {
	foreach ( array( 'akdisi_project', 'akdisi_client', 'akdisi_faq', 'akdisi_testimonial' ) as $type ) {
		if ( post_type_exists( $type ) ) {
			add_post_type_support( $type, 'custom-fields' );
		}
	}
}
add_action( 'init', 'akdisi_headless_post_type_support', 20 );

/* ==========================================================================
 * 3. Daftar meta yang dibaca frontend (show_in_rest => true)
 * ========================================================================== */

function akdisi_headless_register_meta() {
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
		// `content` adalah meta key reserved (memetakan ke post_content), jadi
		// daftar struktur halaman memakai `content_list`.
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
	}
}
add_action( 'init', 'akdisi_headless_register_meta', 20 );

/* ==========================================================================
 * 4. Route REST: info perusahaan (opsi `akdisi_info_contact`)
 * ========================================================================== */

function akdisi_headless_info_payload() {
	$raw = get_option( 'akdisi_info_contact' );
	if ( ! is_array( $raw ) ) {
		$raw = array();
	}

	$defaults = array(
		'email'     => '',
		'phone'     => '',
		'whatsapp'  => '',
		'location'  => '',
		'hours'     => '',
		'address'   => '',
		'site_name' => get_bloginfo( 'name' ),
		'tagline'   => get_bloginfo( 'description' ),
	);

	$info = array_merge( $defaults, array_intersect_key( $raw, $defaults ) );

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
					return rest_ensure_response( akdisi_headless_info_payload() );
				},
				'permission_callback' => '__return_true',
			)
		);
	}
);

/* ==========================================================================
 * 5. Alat seed sekali jalankan (Tools -> Seed Headless Meta)
 * ========================================================================== */

function akdisi_headless_seed() {
	$projects = array(
		array(
			'wp_id'          => 134,
			'frontend_slug' => 'akar-solution-website',
			'badge'         => 'Platform Web',
			'work'          => array(
				'Riset konten & struktur informasi',
				'Desain UI/UX dengan identitas visual baru',
				'Pengembangan situs multi-halaman berperforma cepat',
				'SEO teknis, halaman layanan, dan artikel insight',
				'Pelatihan pengelolaan konten untuk tim internal',
			),
			'content'       => array(
				'Beranda — positioning layanan & bukti kerja',
				'Layanan — satu halaman detail per layanan',
				'Harga & paket yang transparan',
				'Portofolio proyek & studi kasus',
				'Blog / insight untuk mesin pencari',
				'Kontak & formulir brief proyek',
			),
		),
		array(
			'wp_id'          => 133,
			'frontend_slug' => 'glad2glow',
			'badge'         => 'Platform Web',
			'work'          => array(
				'Struktur katalog & kategori produk',
				'Desain antarmuka ramah pembeli muda',
				'Alur belanja & checkout',
				'Integrasi pembayaran & pengiriman',
				'Konten produk: kandungan, cara pakai, ulasan',
			),
			'content'       => array(
				'Beranda — kampanye & produk unggulan',
				'Katalog per kategori & masalah kulit',
				'Detail produk — kandungan, cara pakai, ulasan',
				'Rutinitas per tipe kulit',
				'Keranjang & checkout',
				'Promo & bundling',
			),
		),
		array(
			'wp_id'          => 132,
			'frontend_slug' => 'sistem-kas-tanah-adat',
			'badge'         => 'Data & Analitik',
			'work'          => array(
				'Pemetaan alur kas & kebutuhan laporan',
				'Desain pencatatan yang sederhana untuk pengurus',
				'Pengembangan sistem & uji coba bersama',
				'Pendampingan penggunaan harian',
			),
			'content'       => array(
				'Dashboard saldo & arus kas',
				'Pencatatan transaksi masuk & keluar',
				'Bukti digital — nota & foto',
				'Laporan periodik siap audit',
				'Hak akses pengurus & bendahara',
			),
		),
		array(
			'wp_id'          => 131,
			'frontend_slug' => 'wedding-gallery',
			'badge'         => 'Teknologi Acara',
			'work'          => array(
				'Kurasi & struktur katalog vendor',
				'Desain halaman portofolio vendor',
				'Alur tanya-jawab & permintaan penawaran',
			),
			'content'       => array(
				'Beranda — layanan unggulan & suasana acara',
				'Katalog vendor per kategori',
				'Portofolio & studi kasus',
				'Blog & artikel',
				'Kontak & penawaran',
			),
		),
		array(
			'wp_id'          => 130,
			'frontend_slug' => 'expression-hairsalon',
			'badge'         => 'Platform Web',
			'work'          => array(
				'Pemetaan layanan & harga salon',
				'Desain identitas visual & katalog',
				'Pengembangan situs & reservasi',
				'SEO lokal untuk pencarian salon',
				'Pelatihan pengelolaan artikel',
			),
			'content'       => array(
				'Beranda — layanan unggulan & suasana salon',
				'Layanan & daftar harga',
				'Cerita brand / tentang',
				'Blog — tips perawatan rambut',
				'Reservasi & kontak',
			),
		),
		array(
			'wp_id'          => 129,
			'frontend_slug' => 'klinik-dokter-abc',
			'badge'         => 'Teknologi Kesehatan',
			'work'          => array(
				'Pemetaan alur layanan pasien',
				'Desain pintu masuk aman & hak akses',
				'Pengembangan modul inti administrasi',
				'Uji coba bersama staf klinik',
			),
			'content'       => array(
				'Login aman & manajemen peran',
				'Data pasien & riwayat kunjungan',
				'Jadwal dokter & pendaftaran',
				'Antrean & status layanan',
				'Laporan harian & bulanan',
			),
		),
		array(
			'wp_id'          => 128,
			'frontend_slug' => 'nusantara-kost',
			'badge'         => 'Teknologi Properti',
			'work'          => array(
				'Katalog kamar & fasilitas',
				'Alur pemesanan yang ringkas',
				'Integrasi kontak WhatsApp',
				'Optimasi informasi lokasi & akses',
			),
			'content'       => array(
				'Beranda — kamar yang tersedia',
				'Detail kamar & fasilitas',
				'Lokasi & peta akses',
				'Pemesanan via WhatsApp',
				'FAQ penghuni',
			),
		),
		array(
			'wp_id'          => 127,
			'frontend_slug' => 'futsalku',
			'badge'         => 'Platform Web',
			'work'          => array(
				'Desain portal & katalog lapangan',
				'Alur pemesanan & akun anggota',
				'Informasi jadwal & lokasi',
				'Pengembangan & peluncuran',
			),
			'content'       => array(
				'Beranda & ketersediaan jadwal',
				'Halaman lapangan — harga & fasilitas',
				'Akun anggota & riwayat pemesanan',
				'Lokasi & kontak',
				'Promo & turnamen',
			),
		),
		array(
			'wp_id'          => 126,
			'frontend_slug' => 'laundry-al-farizi',
			'badge'         => 'Platform Web',
			'work'          => array(
				'Digitalisasi alur order',
				'Desain login aman & peran staf',
				'Pengembangan pelacakan pesanan',
				'Pendampingan operasional harian',
			),
			'content'       => array(
				'Login & hak akses staf',
				'Input pesanan & data pelanggan',
				'Pelacakan status: dicuci, disetrika, siap',
				'Nota & pembayaran',
				'Laporan harian',
			),
		),
		array(
			'wp_id'          => 125,
			'frontend_slug' => 'sibanyu',
			'badge'         => 'Platform Web',
			'work'          => array(
				'Arsitektur kategori & navigasi',
				'Desain beranda dinamis',
				'Pengembangan pencarian & optimasi performa',
				'Slot iklan yang bisa dikelola sendiri',
				'Pelatihan alur kerja redaksi',
			),
			'content'       => array(
				'Beranda — headline & berita terbaru',
				'Kategori berita daerah',
				'Halaman artikel & profil penulis',
				'Pencarian',
				'Manajemen iklan',
				'Redaksi & kontak',
			),
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
				if ( isset( $_POST['akdisi_run_seed'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['akdisi_seed_nonce'] ) ), 'akdisi_seed_headless' ) ) {
					$result = akdisi_headless_seed();
					printf( '<div class="notice notice-success"><p>Selesai — %d record diperbarui.</p></div>', (int) $result['updated'] );
				}
				?>
				<div class="wrap">
					<h1>Seed Headless Meta</h1>
					<p>Isi meta <code>frontend_slug</code>, <code>work</code>, <code>content_list</code> untuk 10 proyek dan <code>role</code> untuk 5 testimoni. Idempoten — aman diulang.</p>
					<form method="post">
						<?php wp_nonce_field( 'akdisi_seed_headless', 'akdisi_seed_nonce' ); ?>
						<p><button type="submit" name="akdisi_run_seed" class="button button-primary">Jalankan Seed</button></p>
					</form>
				</div>
				<?php
			}
		);
	}
);
