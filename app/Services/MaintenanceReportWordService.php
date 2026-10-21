<?php

namespace App\Services;

use App\Enums\MaintenanceFinalResult;
use App\Models\AssetMaintenance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpWord\TemplateProcessor;

class MaintenanceReportWordService
{
    public const TEMPLATE_PATH = 'app/templates/Berita Acara Pemeliharaan.docx';

    /** @return array{path:string, filename:string} */
    public function generate(AssetMaintenance $maintenance): array
    {
        $templatePath = storage_path(self::TEMPLATE_PATH);
        if (! File::exists($templatePath)) {
            throw ValidationException::withMessages([
                'berita_acara' => 'Template Word resmi belum ditemukan di storage/app/templates/Berita Acara Pemeliharaan.docx.',
            ]);
        }

        $maintenance->loadMissing(['asset.category', 'asset.kodeBarang', 'applicant', 'verifier', 'completedBy', 'documents']);
        if (! $maintenance->hasCompleteReportData()) {
            throw ValidationException::withMessages([
                'berita_acara' => 'Data Berita Acara belum lengkap. Silakan lengkapi data Berita Acara terlebih dahulu.',
            ]);
        }

        $values = $this->values($maintenance);
        $temporaryFiles = [];

        try {
            $template = new TemplateProcessor($this->preparedTemplatePath($templatePath));
            $this->applyDocumentationSection($template, $maintenance, $temporaryFiles);

            $missingVariables = array_values(array_diff($template->getVariables(), array_keys($values)));
            if ($missingVariables !== []) {
                throw ValidationException::withMessages([
                    'berita_acara' => 'Mapping template Berita Acara belum lengkap: '.implode(', ', $missingVariables),
                ]);
            }

            foreach ($values as $key => $value) {
                $template->setValue($key, $this->escape($value));
            }

            $outputDir = storage_path('app/generated/maintenance-reports');
            File::ensureDirectoryExists($outputDir);

            $filename = $this->filename($maintenance);
            $outputPath = $outputDir.DIRECTORY_SEPARATOR.$filename;
            $template->saveAs($outputPath);
            $this->applyStaticCheckboxes($outputPath, $maintenance->hasil_akhir_pemeliharaan);
            $this->ensureNoPlaceholderLeft($outputPath);

            return ['path' => $outputPath, 'filename' => $filename];
        } finally {
            $this->deleteTemporaryFiles($temporaryFiles);
        }
    }

    /** @return array<string, string> */
    public function values(AssetMaintenance $maintenance): array
    {
        $asset = $maintenance->asset;
        $date = $maintenance->tanggal_berita_acara instanceof Carbon
            ? $maintenance->tanggal_berita_acara
            : Carbon::parse($maintenance->tanggal_berita_acara);
        $final = $maintenance->hasil_akhir_pemeliharaan;

        return [
            'nomor_berita_acara' => $maintenance->nomor_berita_acara,
            'hari' => $this->dayName($date),
            'tanggal' => $this->dateLong($date),
            'lokasi_pelaksanaan' => $maintenance->lokasi_pelaksanaan,
            'nama_barang' => $asset?->nama_aset ?: '-',
            'kode_internal' => $asset?->kode_internal ?: '-',
            'kode_barang' => $asset?->kodeBarang?->kode_barang ?: '-',
            'kode_barang_id_aset' => trim(($asset?->kodeBarang?->kode_barang ?: '-').' / '.($asset?->kode_internal ?: '-')),
            'merk_tipe' => $asset?->merek_tipe ?: '-',
            'nomor_seri' => '-',
            'lokasi_barang' => $asset?->lokasi_penyimpanan ?: '-',
            'jenis_pemeliharaan' => $maintenance->jenis_pemeliharaan?->label() === 'Lainnya'
                ? ($maintenance->jenis_pemeliharaan_lainnya ?: 'Lainnya')
                : $maintenance->jenis_pemeliharaan->label(),
            'tanggal_mulai' => $maintenance->tanggal_mulai ? $this->dateLong($maintenance->tanggal_mulai) : '-',
            'tanggal_selesai' => $maintenance->tanggal_selesai ? $this->dateLong($maintenance->tanggal_selesai) : '-',
            'nama_pelaksana' => $maintenance->nama_pelaksana,
            'dasar_pelaksanaan' => $maintenance->dasar_pelaksanaan ?: 'Berdasarkan pengajuan pemeliharaan aset pada aplikasi SIMIVA.',
            'kondisi_sebelum' => $maintenance->kondisi_sebelum?->label() ?: '-',
            'tindakan_pemeliharaan' => $maintenance->tindakan_servis ?: '-',
            'komponen_material' => $maintenance->komponen_material ?: 'Tidak ada',
            'hasil_pemeliharaan' => $maintenance->catatan_hasil ?: '-',
            'check_berfungsi_baik' => $final === MaintenanceFinalResult::Good ? '☑' : '☐',
            'check_berfungsi_sebagian' => $final === MaintenanceFinalResult::Partial ? '☑' : '☐',
            'check_belum_dapat_digunakan' => $final === MaintenanceFinalResult::Unusable ? '☑' : '☐',
            'kondisi_fisik' => $maintenance->kondisi_setelah?->label() ?: '-',
            'fungsi_utama' => $maintenance->fungsi_utama->label(),
            'hasil_pengujian' => $maintenance->hasil_pengujian->label(),
            'catatan_hasil' => $maintenance->catatan_hasil ?: '-',
            'kesimpulan' => $final->conclusionPhrase(),
            'tempat_ttd' => $maintenance->tempat_ttd,
            'tanggal_ttd' => $this->dateLong($date),
            'nama_pelaksana_ttd' => $maintenance->nama_pelaksana,
            'identitas_pelaksana' => $maintenance->identitas_pelaksana ?: '-',
            'jabatan_penanggung_jawab' => $maintenance->jabatan_penanggung_jawab,
            'nama_penanggung_jawab' => $maintenance->nama_penanggung_jawab,
            'nip_penanggung_jawab' => $maintenance->nip_penanggung_jawab,
        ];
    }

    private function filename(AssetMaintenance $maintenance): string
    {
        $code = $maintenance->asset?->kode_internal ?: 'aset-'.$maintenance->id;
        $safeCode = Str::of($code)->replace(['/', '\\'], '-')->replaceMatches('/[^A-Za-z0-9._-]/', '-')->trim('-');
        $date = ($maintenance->tanggal_berita_acara ?: now())->format('Y-m-d');

        return "Berita-Acara-Pemeliharaan-{$safeCode}-{$date}.docx";
    }

    /** @param list<string> $temporaryFiles */
    private function applyDocumentationSection(TemplateProcessor $template, AssetMaintenance $maintenance, array &$temporaryFiles): void
    {
        if (! in_array('dokumentasi_block', $template->getVariables(), true)) {
            return;
        }

        $photos = $this->documentationPhotos($maintenance, $temporaryFiles);
        $link = trim((string) $maintenance->link_drive_dokumentasi);

        if ($photos === [] && $link === '') {
            $template->cloneBlock('dokumentasi_block', 0, true, false);

            return;
        }

        $template->cloneBlock('dokumentasi_block', 1, true, false);

        if ($photos === []) {
            $template->cloneBlock('dokumentasi_item', 0, true, false);
        } else {
            $template->cloneBlock('dokumentasi_item', count($photos), true, true);

            foreach ($photos as $index => $photo) {
                $number = $index + 1;
                $template->setImageValue('foto_dokumentasi#'.$number, [
                    'path' => $photo['path'],
                    'width' => $photo['width'],
                    'height' => $photo['height'],
                    'ratio' => true,
                ]);
                $template->setValue('keterangan_foto#'.$number, $this->escape($photo['caption']));
            }
        }

        $template->setValue('link_dokumentasi', $link === '' ? '' : $this->escape('Link Dokumentasi: '.$link));
    }

    /**
     * @param list<string> $temporaryFiles
     * @return list<array{path:string, caption:string, width:int, height:int}>
     */
    private function documentationPhotos(AssetMaintenance $maintenance, array &$temporaryFiles): array
    {
        $maintenance->loadMissing('documents');

        $photos = [];
        $seenPaths = [];

        $this->appendDocumentationPhoto($photos, $seenPaths, $temporaryFiles, $maintenance->foto_sebelum, 'Foto Sebelum Pemeliharaan');
        $this->appendDocumentationPhoto($photos, $seenPaths, $temporaryFiles, $maintenance->foto_setelah, 'Foto Setelah Pemeliharaan');

        foreach ($maintenance->documents->where('jenis_dokumen', 'dokumentasi_servis')->sortBy('id') as $document) {
            $label = $document->nama_file ?: 'Dokumentasi Servis';
            $this->appendDocumentationPhoto($photos, $seenPaths, $temporaryFiles, $document->path_file, 'Dokumentasi Servis - '.$label);
        }

        return $photos;
    }

    /**
     * @param list<array{path:string, caption:string, width:int, height:int}> $photos
     * @param array<string, bool> $seenPaths
     * @param list<string> $temporaryFiles
     */
    private function appendDocumentationPhoto(array &$photos, array &$seenPaths, array &$temporaryFiles, ?string $path, string $caption): void
    {
        $path = str_replace('\\', '/', trim((string) $path));
        if ($path === '' || isset($seenPaths[$path]) || ! Storage::disk('public')->exists($path)) {
            return;
        }

        $physicalPath = Storage::disk('public')->path($path);
        $image = $this->imageForWord($physicalPath, $temporaryFiles);
        if ($image === null) {
            return;
        }

        $seenPaths[$path] = true;
        $photos[] = [
            'path' => $image['path'],
            'caption' => $caption,
            'width' => $image['width'],
            'height' => $image['height'],
        ];
    }

    /**
     * @param list<string> $temporaryFiles
     * @return array{path:string, width:int, height:int}|null
     */
    private function imageForWord(string $path, array &$temporaryFiles): ?array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension === 'webp') {
            $path = $this->convertWebpToPng($path, $temporaryFiles);
            if ($path === null) {
                return null;
            }

            $extension = 'png';
        }

        if (! in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            return null;
        }

        $dimensions = @getimagesize($path);
        if ($dimensions === false) {
            return null;
        }

        [$actualWidth, $actualHeight] = $dimensions;
        [$width, $height] = $this->documentationImageSize((int) $actualWidth, (int) $actualHeight);

        return ['path' => $path, 'width' => $width, 'height' => $height];
    }

    /** @return array{int, int} */
    private function documentationImageSize(int $actualWidth, int $actualHeight): array
    {
        if ($actualWidth <= 0 || $actualHeight <= 0) {
            return [450, 300];
        }

        $isPortrait = $actualHeight > $actualWidth;
        $maxWidth = $isPortrait ? 380 : 450;
        $maxHeight = 500;
        $scale = min($maxWidth / $actualWidth, $maxHeight / $actualHeight, 1);

        return [
            max(1, (int) round($actualWidth * $scale)),
            max(1, (int) round($actualHeight * $scale)),
        ];
    }

    /** @param list<string> $temporaryFiles */
    private function convertWebpToPng(string $path, array &$temporaryFiles): ?string
    {
        if (! function_exists('imagecreatefromwebp') || ! function_exists('imagepng')) {
            return null;
        }

        $image = @imagecreatefromwebp($path);
        if ($image === false) {
            return null;
        }

        $outputDir = storage_path('app/generated/maintenance-reports/temp');
        File::ensureDirectoryExists($outputDir);

        $outputPath = $outputDir.DIRECTORY_SEPARATOR.'documentation-'.Str::uuid().'.png';
        $saved = @imagepng($image, $outputPath);
        imagedestroy($image);

        if (! $saved) {
            File::delete($outputPath);

            return null;
        }

        $temporaryFiles[] = $outputPath;

        return $outputPath;
    }

    /** @param list<string> $paths */
    private function deleteTemporaryFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if (File::exists($path)) {
                File::delete($path);
            }
        }
    }

    private function preparedTemplatePath(string $templatePath): string
    {
        $outputDir = storage_path('app/generated/maintenance-reports/templates');
        File::ensureDirectoryExists($outputDir);

        $preparedPath = $outputDir.DIRECTORY_SEPARATOR.'prepared-'.Str::uuid().'.docx';
        if (! copy($templatePath, $preparedPath)) {
            throw ValidationException::withMessages([
                'berita_acara' => 'Template Word gagal disiapkan. Silakan coba download ulang.',
            ]);
        }

        $zip = new \ZipArchive;
        if ($zip->open($preparedPath) !== true) {
            return $preparedPath;
        }

        $xml = $zip->getFromName('word/document.xml');
        if (is_string($xml)) {
            $normalized = preg_replace(
                '/<w:r\b[^>]*>(?:(?!<\/w:r>).)*<w:t>\$\{<\/w:t>(?:(?!<\/w:r>).)*<\/w:r>\s*(?:<w:proofErr\b[^>]*\/>\s*)?<w:r\b[^>]*>(?:(?!<\/w:r>).)*<w:t>nip_penanggung_jawab<\/w:t>(?:(?!<\/w:r>).)*<\/w:r>\s*(?:<w:proofErr\b[^>]*\/>\s*)?(?:<w:r\b[^>]*>(?:(?!<\/w:r>).)*<w:t>\}<\/w:t>(?:(?!<\/w:r>).)*<\/w:r>)?/s',
                '<w:r><w:t>${nip_penanggung_jawab}</w:t></w:r>',
                $xml,
                1
            ) ?? $xml;

            if ($normalized !== $xml) {
                $zip->addFromString('word/document.xml', $normalized);
            }
        }

        $zip->close();

        return $preparedPath;
    }

    private function applyStaticCheckboxes(string $path, MaintenanceFinalResult $result): void
    {
        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages([
                'berita_acara' => 'File Berita Acara gagal diproses.',
            ]);
        }

        $xml = $zip->getFromName('word/document.xml');
        if (! is_string($xml)) {
            $zip->close();

            throw ValidationException::withMessages([
                'berita_acara' => 'Isi dokumen Berita Acara tidak dapat dibaca.',
            ]);
        }

        $checkboxes = match ($result) {
            MaintenanceFinalResult::Good => ['☑', '☐', '☐'],
            MaintenanceFinalResult::Partial => ['☐', '☑', '☐'],
            MaintenanceFinalResult::Unusable => ['☐', '☐', '☑'],
        };

        foreach ($checkboxes as $checkbox) {
            $xml = preg_replace('/<w:t>☐<\/w:t>/', '<w:t>'.$checkbox.'</w:t>', $xml, 1) ?? $xml;
        }

        $zip->addFromString('word/document.xml', $xml);
        $zip->close();
    }

    private function ensureNoPlaceholderLeft(string $path): void
    {
        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages([
                'berita_acara' => 'File Berita Acara gagal divalidasi.',
            ]);
        }

        $leftovers = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! str_ends_with((string) $name, '.xml')) {
                continue;
            }

            $xml = $zip->getFromIndex($i);
            if (! is_string($xml)) {
                continue;
            }

            preg_match_all('/\$\{[^}]+\}/', $xml, $matches);
            $leftovers = [...$leftovers, ...$matches[0]];
        }

        $zip->close();

        $leftovers = array_values(array_unique($leftovers));
        if ($leftovers !== []) {
            throw ValidationException::withMessages([
                'berita_acara' => 'Masih ada placeholder yang belum terganti: '.implode(', ', $leftovers),
            ]);
        }
    }

    /** @param array<string, string> $values */
    private function postProcessGeneratedDocument(string $path, array $values, AssetMaintenance $maintenance): void
    {
        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            return;
        }

        $checkboxes = $this->checkboxValues($maintenance->hasil_akhir_pemeliharaan);

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! str_ends_with($name, '.xml')) {
                continue;
            }

            $xml = $zip->getFromIndex($i);
            if (! is_string($xml)) {
                continue;
            }

            $xml = $this->normalizeBrokenPlaceholders($xml, array_keys($values));

            foreach ($values as $key => $value) {
                $xml = preg_replace(
                    '/<w:t>\$\{<\/w:t>(?:(?!<w:t>).)*<w:t>'.preg_quote($key, '/').'<\/w:t>/s',
                    '<w:t>'.$this->escape($value).'</w:t>',
                    $xml
                ) ?? $xml;
                $xml = str_replace('${'.$key.'}', $this->escape($value), $xml);
                $xml = str_replace('${'.$key, $this->escape($value), $xml);
            }

            $checkboxIndex = 0;
            $xml = preg_replace_callback('/☐/', function () use (&$checkboxIndex, $checkboxes): string {
                $value = $checkboxes[$checkboxIndex] ?? '☐';
                $checkboxIndex++;

                return $value;
            }, $xml) ?? $xml;

            $zip->addFromString($name, $xml);
        }

        $zip->close();
    }

    /** @return list<string> */
    private function checkboxValues(?MaintenanceFinalResult $result): array
    {
        return match ($result) {
            MaintenanceFinalResult::Good => ['☑', '☐', '☐'],
            MaintenanceFinalResult::Partial => ['☐', '☑', '☐'],
            MaintenanceFinalResult::Unusable => ['☐', '☐', '☑'],
            default => ['☐', '☐', '☐'],
        };
    }

    private function escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function dayName(Carbon $date): string
    {
        return ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][(int) $date->format('w')];
    }

    private function dateLong(Carbon $date): string
    {
        $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        return $date->format('j').' '.$months[(int) $date->format('n')].' '.$date->format('Y');
    }
}
