<?php
/**
 * AKDISI — One-off seed for the headless meta fields.
 *
 * Run once from wp-admin (Tools -> Run Seed) or via WP-CLI:
 *     wp eval-file seed/seed-headless-meta.php
 *
 * What it does:
 *   1. Links each WordPress project to its canonical frontend slug
 *      (`frontend_slug`) so /projects/<slug>/ URLs never change.
 *   2. Copies the editorial `work` / `content` lists into project meta,
 *      making WordPress the single source of truth for project pages.
 *   3. Fills the testimonial `role` meta (job title + company).
 *
 * Idempotent — safe to run repeatedly.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function akdisi_seed_headless_meta() {
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
        'SEO lokal Jambi & sekitarnya',
    ),
			'content'       => array(
        'Beranda — pencarian vendor',
        'Halaman vendor — portofolio, harga & ulasan',
        'Kategori layanan: venue, dekorasi, katering, MUA',
        'Galeri & ulasan pasangan',
        'Kontak & konsultasi',
    ),
		),
		array(
			'wp_id'          => 130,
			'frontend_slug' => 'expression-hairsalon',
			'badge'         => 'Platform Web',
			'work'          => array(
        'Struktur konten multi-halaman',
        'Desain visual bernuansa premium',
        'Pengembangan & optimasi kecepatan',
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
		$updated++;
	}

	$testimonials = array(
		array( 'slug' => 'fajar-rizki', 'role' => 'Founder, Gelora Events' ),
		array( 'slug' => 'devi-anggraini', 'role' => 'CFO, TokoHemat' ),
		array( 'slug' => 'budi-pratama', 'role' => 'Mitra, Arah Properti' ),
		array( 'slug' => 'nita-haryanto', 'role' => 'Direktur, Sehati Network' ),
		array( 'slug' => 'andri-setiawan', 'role' => 'Lead Operasional, Tokobersama' ),
	);

	foreach ( $testimonials as $row ) {
		$posts = get_posts( array(
			'post_type'      => 'akdisi_testimonial',
			'posts_per_page' => 1,
			'name'            => $row['slug'],
			'fields'         => 'ids',
		) );
		if ( $posts ) {
			update_post_meta( $posts[0], 'role', $row['role'] );
			$updated++;
		}
	}

	return array( 'updated' => $updated );
}

/**
 * Expose the seed behind an admin page so it can be run by hand.
 */
add_action( 'admin_menu', function () {
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
			$result = akdisi_seed_headless_meta();
			printf( '<div class="notice notice-success"><p>Selesai — %d record diperbarui.</p></div>', (int) $result['updated'] );
		}
		?>
		<form method="post">
			<?php wp_nonce_field( 'akdisi_seed_headless', 'akdisi_seed_nonce' ); ?>
			<p>Isi meta <code>frontend_slug</code>, <code>work</code>, <code>content_list</code> untuk 15 proyek dan <code>role</code> untuk 5 testimoni. Idempoten — aman diulang.</p>
			<p><button type="submit" name="akdisi_run_seed" class="button button-primary">Jalankan Seed</button></p>
		</form>
		<?php
	},
	);
} );
