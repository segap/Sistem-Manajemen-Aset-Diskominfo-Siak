<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('loan_requests', function (Blueprint $table): void {
            $table->dropForeign(['requester_id']);
            $table->dropForeign(['asset_id']);
            $table->dropForeign(['reviewed_by']);
            $table->dropForeign(['handed_over_by']);
            $table->dropForeign(['returned_by']);
        });

        Schema::table('procurement_proposals', function (Blueprint $table): void {
            $table->dropForeign(['requester_id']);
            $table->dropForeign(['intended_user_id']);
            $table->dropForeign(['responsible_person_id']);
            $table->dropForeign(['asset_team_reviewer_id']);
            $table->dropForeign(['supervisor_reviewer_id']);
        });

        Schema::table('procurements', function (Blueprint $table): void {
            $table->dropForeign(['procurement_proposal_id']);
            $table->dropForeign(['asset_category_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['received_by']);
            $table->dropForeign(['verified_by']);
            $table->dropForeign(['asset_id']);
        });

        Schema::table('asset_disposal_requests', function (Blueprint $table): void {
            $table->dropForeign(['requester_id']);
            $table->dropForeign(['asset_id']);
            $table->dropForeign(['reviewed_by']);
        });

        Schema::table('assets', function (Blueprint $table): void {
            $table->dropForeign(['asset_category_id']);
            $table->dropForeign(['verified_by']);
        });

        Schema::table('asset_categories', function (Blueprint $table): void {
            $table->renameColumn('code', 'kode_kategori');
            $table->renameColumn('name', 'nama_kategori');
            $table->renameColumn('icon', 'ikon');
            $table->renameColumn('description', 'deskripsi');
        });

        Schema::table('assets', function (Blueprint $table): void {
            $table->renameColumn('asset_category_id', 'kategori_aset_id');
            $table->renameColumn('public_token', 'token_publik');
            $table->renameColumn('inventory_code', 'kode_inventaris');
            $table->renameColumn('name', 'nama_aset');
            $table->renameColumn('brand_type', 'merek_tipe');
            $table->renameColumn('specifications', 'spesifikasi');
            $table->renameColumn('quantity', 'jumlah');
            $table->renameColumn('acquisition_year', 'tahun_perolehan');
            $table->renameColumn('condition', 'kondisi');
            $table->renameColumn('verification_status', 'status_verifikasi');
            $table->renameColumn('verified_at', 'tanggal_verifikasi');
            $table->renameColumn('verified_by', 'diverifikasi_oleh');
            $table->renameColumn('verification_notes', 'catatan_verifikasi');
            $table->renameColumn('supplier', 'pemasok');
            $table->renameColumn('storage_location', 'lokasi_penyimpanan');
            $table->renameColumn('disposed_at', 'tanggal_penghapusan');
        });

        Schema::table('loan_requests', function (Blueprint $table): void {
            $table->renameColumn('requester_id', 'pemohon_id');
            $table->renameColumn('asset_id', 'aset_id');
            $table->renameColumn('quantity', 'jumlah');
            $table->renameColumn('borrow_date', 'tanggal_pinjam');
            $table->renameColumn('return_date', 'tanggal_kembali');
            $table->renameColumn('reason', 'alasan');
            $table->renameColumn('reviewed_by', 'diperiksa_oleh');
            $table->renameColumn('reviewed_at', 'tanggal_pemeriksaan');
            $table->renameColumn('rejection_reason', 'alasan_penolakan');
            $table->renameColumn('handed_over_by', 'diserahkan_oleh');
            $table->renameColumn('handed_over_at', 'tanggal_penyerahan');
            $table->renameColumn('returned_by', 'diterima_kembali_oleh');
            $table->renameColumn('returned_at', 'tanggal_pengembalian');
            $table->renameColumn('return_condition', 'kondisi_kembali');
            $table->renameColumn('return_notes', 'catatan_pengembalian');
        });

        Schema::table('procurement_proposals', function (Blueprint $table): void {
            $table->renameColumn('requester_id', 'pengusul_id');
            $table->renameColumn('item_name', 'nama_barang');
            $table->renameColumn('specifications', 'spesifikasi');
            $table->renameColumn('quantity', 'jumlah');
            $table->renameColumn('estimated_unit_price', 'estimasi_harga_satuan');
            $table->renameColumn('technical_justification', 'alasan_kebutuhan');
            $table->renameColumn('intended_user_id', 'pengguna_barang_id');
            $table->renameColumn('responsible_person_id', 'penanggung_jawab_id');
            $table->renameColumn('image_reference_url', 'url_referensi_gambar');
            $table->renameColumn('ecatalog_url', 'url_ekatalog');
            $table->renameColumn('photo_path', 'path_foto');
            $table->renameColumn('asset_team_reviewer_id', 'pemeriksa_tim_aset_id');
            $table->renameColumn('asset_team_reviewed_at', 'tanggal_pemeriksaan_tim_aset');
            $table->renameColumn('asset_team_notes', 'catatan_tim_aset');
            $table->renameColumn('supervisor_reviewer_id', 'pemeriksa_kasubbag_id');
            $table->renameColumn('supervisor_reviewed_at', 'tanggal_pemeriksaan_kasubbag');
            $table->renameColumn('supervisor_notes', 'catatan_kasubbag');
            $table->renameColumn('rejected_at_stage', 'tahap_penolakan');
            $table->renameColumn('rejection_reason', 'alasan_penolakan');
        });

        Schema::table('procurements', function (Blueprint $table): void {
            $table->renameColumn('procurement_proposal_id', 'usulan_pengadaan_id');
            $table->renameColumn('asset_category_id', 'kategori_aset_id');
            $table->renameColumn('created_by', 'dibuat_oleh');
            $table->renameColumn('inventory_code', 'kode_inventaris');
            $table->renameColumn('item_name', 'nama_barang');
            $table->renameColumn('brand_type', 'merek_tipe');
            $table->renameColumn('specifications', 'spesifikasi');
            $table->renameColumn('ordered_quantity', 'jumlah_dipesan');
            $table->renameColumn('unit_price', 'harga_satuan');
            $table->renameColumn('supplier', 'pemasok');
            $table->renameColumn('order_date', 'tanggal_pesan');
            $table->renameColumn('expected_delivery_date', 'perkiraan_tanggal_tiba');
            $table->renameColumn('procurement_reason', 'alasan_pengadaan');
            $table->renameColumn('received_quantity', 'jumlah_diterima');
            $table->renameColumn('received_at', 'tanggal_penerimaan');
            $table->renameColumn('received_by', 'diterima_oleh');
            $table->renameColumn('receipt_notes', 'catatan_penerimaan');
            $table->renameColumn('verified_by', 'diverifikasi_oleh');
            $table->renameColumn('verified_at', 'tanggal_verifikasi');
            $table->renameColumn('condition', 'kondisi');
            $table->renameColumn('storage_location', 'lokasi_penyimpanan');
            $table->renameColumn('verification_notes', 'catatan_verifikasi');
            $table->renameColumn('rejection_reason', 'alasan_penolakan');
            $table->renameColumn('asset_id', 'aset_id');
        });

        Schema::table('asset_disposal_requests', function (Blueprint $table): void {
            $table->renameColumn('requester_id', 'pengusul_id');
            $table->renameColumn('asset_id', 'aset_id');
            $table->renameColumn('reason', 'alasan');
            $table->renameColumn('notes', 'catatan');
            $table->renameColumn('document_path', 'path_dokumen');
            $table->renameColumn('document_name', 'nama_dokumen');
            $table->renameColumn('document_mime', 'mime_dokumen');
            $table->renameColumn('reviewed_by', 'diperiksa_oleh');
            $table->renameColumn('reviewed_at', 'tanggal_pemeriksaan');
            $table->renameColumn('rejection_reason', 'alasan_penolakan');
        });

        Schema::rename('asset_categories', 'kategori_aset');
        Schema::rename('assets', 'aset');
        Schema::rename('loan_requests', 'peminjaman');
        Schema::rename('procurement_proposals', 'usulan_pengadaan');
        Schema::rename('procurements', 'pengadaan');
        Schema::rename('asset_disposal_requests', 'pengajuan_penghapusan_aset');

        Schema::table('aset', function (Blueprint $table): void {
            $table->foreign('kategori_aset_id')->references('id')->on('kategori_aset')->restrictOnDelete();
            $table->foreign('diverifikasi_oleh')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('peminjaman', function (Blueprint $table): void {
            $table->foreign('pemohon_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('aset_id')->references('id')->on('aset')->restrictOnDelete();
            $table->foreign('diperiksa_oleh')->references('id')->on('users')->nullOnDelete();
            $table->foreign('diserahkan_oleh')->references('id')->on('users')->nullOnDelete();
            $table->foreign('diterima_kembali_oleh')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('usulan_pengadaan', function (Blueprint $table): void {
            $table->foreign('pengusul_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('pengguna_barang_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('penanggung_jawab_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('pemeriksa_tim_aset_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('pemeriksa_kasubbag_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('pengadaan', function (Blueprint $table): void {
            $table->foreign('usulan_pengadaan_id')->references('id')->on('usulan_pengadaan')->restrictOnDelete();
            $table->foreign('kategori_aset_id')->references('id')->on('kategori_aset')->restrictOnDelete();
            $table->foreign('dibuat_oleh')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('diterima_oleh')->references('id')->on('users')->nullOnDelete();
            $table->foreign('diverifikasi_oleh')->references('id')->on('users')->nullOnDelete();
            $table->foreign('aset_id')->references('id')->on('aset')->nullOnDelete();
        });

        Schema::table('pengajuan_penghapusan_aset', function (Blueprint $table): void {
            $table->foreign('pengusul_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('aset_id')->references('id')->on('aset')->restrictOnDelete();
            $table->foreign('diperiksa_oleh')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuan_penghapusan_aset', function (Blueprint $table): void {
            $table->dropForeign(['pengusul_id']);
            $table->dropForeign(['aset_id']);
            $table->dropForeign(['diperiksa_oleh']);
        });

        Schema::table('pengadaan', function (Blueprint $table): void {
            $table->dropForeign(['usulan_pengadaan_id']);
            $table->dropForeign(['kategori_aset_id']);
            $table->dropForeign(['dibuat_oleh']);
            $table->dropForeign(['diterima_oleh']);
            $table->dropForeign(['diverifikasi_oleh']);
            $table->dropForeign(['aset_id']);
        });

        Schema::table('usulan_pengadaan', function (Blueprint $table): void {
            $table->dropForeign(['pengusul_id']);
            $table->dropForeign(['pengguna_barang_id']);
            $table->dropForeign(['penanggung_jawab_id']);
            $table->dropForeign(['pemeriksa_tim_aset_id']);
            $table->dropForeign(['pemeriksa_kasubbag_id']);
        });

        Schema::table('peminjaman', function (Blueprint $table): void {
            $table->dropForeign(['pemohon_id']);
            $table->dropForeign(['aset_id']);
            $table->dropForeign(['diperiksa_oleh']);
            $table->dropForeign(['diserahkan_oleh']);
            $table->dropForeign(['diterima_kembali_oleh']);
        });

        Schema::table('aset', function (Blueprint $table): void {
            $table->dropForeign(['kategori_aset_id']);
            $table->dropForeign(['diverifikasi_oleh']);
        });

        Schema::rename('pengajuan_penghapusan_aset', 'asset_disposal_requests');
        Schema::rename('pengadaan', 'procurements');
        Schema::rename('usulan_pengadaan', 'procurement_proposals');
        Schema::rename('peminjaman', 'loan_requests');
        Schema::rename('aset', 'assets');
        Schema::rename('kategori_aset', 'asset_categories');

        Schema::table('asset_disposal_requests', function (Blueprint $table): void {
            $table->renameColumn('pengusul_id', 'requester_id');
            $table->renameColumn('aset_id', 'asset_id');
            $table->renameColumn('alasan', 'reason');
            $table->renameColumn('catatan', 'notes');
            $table->renameColumn('path_dokumen', 'document_path');
            $table->renameColumn('nama_dokumen', 'document_name');
            $table->renameColumn('mime_dokumen', 'document_mime');
            $table->renameColumn('diperiksa_oleh', 'reviewed_by');
            $table->renameColumn('tanggal_pemeriksaan', 'reviewed_at');
            $table->renameColumn('alasan_penolakan', 'rejection_reason');
        });

        Schema::table('procurements', function (Blueprint $table): void {
            $table->renameColumn('usulan_pengadaan_id', 'procurement_proposal_id');
            $table->renameColumn('kategori_aset_id', 'asset_category_id');
            $table->renameColumn('dibuat_oleh', 'created_by');
            $table->renameColumn('kode_inventaris', 'inventory_code');
            $table->renameColumn('nama_barang', 'item_name');
            $table->renameColumn('merek_tipe', 'brand_type');
            $table->renameColumn('spesifikasi', 'specifications');
            $table->renameColumn('jumlah_dipesan', 'ordered_quantity');
            $table->renameColumn('harga_satuan', 'unit_price');
            $table->renameColumn('pemasok', 'supplier');
            $table->renameColumn('tanggal_pesan', 'order_date');
            $table->renameColumn('perkiraan_tanggal_tiba', 'expected_delivery_date');
            $table->renameColumn('alasan_pengadaan', 'procurement_reason');
            $table->renameColumn('jumlah_diterima', 'received_quantity');
            $table->renameColumn('tanggal_penerimaan', 'received_at');
            $table->renameColumn('diterima_oleh', 'received_by');
            $table->renameColumn('catatan_penerimaan', 'receipt_notes');
            $table->renameColumn('diverifikasi_oleh', 'verified_by');
            $table->renameColumn('tanggal_verifikasi', 'verified_at');
            $table->renameColumn('kondisi', 'condition');
            $table->renameColumn('lokasi_penyimpanan', 'storage_location');
            $table->renameColumn('catatan_verifikasi', 'verification_notes');
            $table->renameColumn('alasan_penolakan', 'rejection_reason');
            $table->renameColumn('aset_id', 'asset_id');
        });

        Schema::table('procurement_proposals', function (Blueprint $table): void {
            $table->renameColumn('pengusul_id', 'requester_id');
            $table->renameColumn('nama_barang', 'item_name');
            $table->renameColumn('spesifikasi', 'specifications');
            $table->renameColumn('jumlah', 'quantity');
            $table->renameColumn('estimasi_harga_satuan', 'estimated_unit_price');
            $table->renameColumn('alasan_kebutuhan', 'technical_justification');
            $table->renameColumn('pengguna_barang_id', 'intended_user_id');
            $table->renameColumn('penanggung_jawab_id', 'responsible_person_id');
            $table->renameColumn('url_referensi_gambar', 'image_reference_url');
            $table->renameColumn('url_ekatalog', 'ecatalog_url');
            $table->renameColumn('path_foto', 'photo_path');
            $table->renameColumn('pemeriksa_tim_aset_id', 'asset_team_reviewer_id');
            $table->renameColumn('tanggal_pemeriksaan_tim_aset', 'asset_team_reviewed_at');
            $table->renameColumn('catatan_tim_aset', 'asset_team_notes');
            $table->renameColumn('pemeriksa_kasubbag_id', 'supervisor_reviewer_id');
            $table->renameColumn('tanggal_pemeriksaan_kasubbag', 'supervisor_reviewed_at');
            $table->renameColumn('catatan_kasubbag', 'supervisor_notes');
            $table->renameColumn('tahap_penolakan', 'rejected_at_stage');
            $table->renameColumn('alasan_penolakan', 'rejection_reason');
        });

        Schema::table('loan_requests', function (Blueprint $table): void {
            $table->renameColumn('pemohon_id', 'requester_id');
            $table->renameColumn('aset_id', 'asset_id');
            $table->renameColumn('jumlah', 'quantity');
            $table->renameColumn('tanggal_pinjam', 'borrow_date');
            $table->renameColumn('tanggal_kembali', 'return_date');
            $table->renameColumn('alasan', 'reason');
            $table->renameColumn('diperiksa_oleh', 'reviewed_by');
            $table->renameColumn('tanggal_pemeriksaan', 'reviewed_at');
            $table->renameColumn('alasan_penolakan', 'rejection_reason');
            $table->renameColumn('diserahkan_oleh', 'handed_over_by');
            $table->renameColumn('tanggal_penyerahan', 'handed_over_at');
            $table->renameColumn('diterima_kembali_oleh', 'returned_by');
            $table->renameColumn('tanggal_pengembalian', 'returned_at');
            $table->renameColumn('kondisi_kembali', 'return_condition');
            $table->renameColumn('catatan_pengembalian', 'return_notes');
        });

        Schema::table('assets', function (Blueprint $table): void {
            $table->renameColumn('kategori_aset_id', 'asset_category_id');
            $table->renameColumn('token_publik', 'public_token');
            $table->renameColumn('kode_inventaris', 'inventory_code');
            $table->renameColumn('nama_aset', 'name');
            $table->renameColumn('merek_tipe', 'brand_type');
            $table->renameColumn('spesifikasi', 'specifications');
            $table->renameColumn('jumlah', 'quantity');
            $table->renameColumn('tahun_perolehan', 'acquisition_year');
            $table->renameColumn('kondisi', 'condition');
            $table->renameColumn('status_verifikasi', 'verification_status');
            $table->renameColumn('tanggal_verifikasi', 'verified_at');
            $table->renameColumn('diverifikasi_oleh', 'verified_by');
            $table->renameColumn('catatan_verifikasi', 'verification_notes');
            $table->renameColumn('pemasok', 'supplier');
            $table->renameColumn('lokasi_penyimpanan', 'storage_location');
            $table->renameColumn('tanggal_penghapusan', 'disposed_at');
        });

        Schema::table('asset_categories', function (Blueprint $table): void {
            $table->renameColumn('kode_kategori', 'code');
            $table->renameColumn('nama_kategori', 'name');
            $table->renameColumn('ikon', 'icon');
            $table->renameColumn('deskripsi', 'description');
        });

        Schema::table('assets', function (Blueprint $table): void {
            $table->foreign('asset_category_id')->references('id')->on('asset_categories')->restrictOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('loan_requests', function (Blueprint $table): void {
            $table->foreign('requester_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('asset_id')->references('id')->on('assets')->restrictOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('handed_over_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('returned_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('procurement_proposals', function (Blueprint $table): void {
            $table->foreign('requester_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('intended_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('responsible_person_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('asset_team_reviewer_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('supervisor_reviewer_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('procurements', function (Blueprint $table): void {
            $table->foreign('procurement_proposal_id')->references('id')->on('procurement_proposals')->restrictOnDelete();
            $table->foreign('asset_category_id')->references('id')->on('asset_categories')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('received_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('asset_id')->references('id')->on('assets')->nullOnDelete();
        });

        Schema::table('asset_disposal_requests', function (Blueprint $table): void {
            $table->foreign('requester_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('asset_id')->references('id')->on('assets')->restrictOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }
};
