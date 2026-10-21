<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class AssetQrController extends Controller
{
    public function showPublic(string $publicToken): JsonResponse
    {
        $asset = Asset::query()
            ->with('category')
            ->where('token_publik', $publicToken)
            ->where('status_verifikasi', 'disetujui')
            ->firstOrFail();

        return response()->json([
            'data' => [
                'display_code' => $asset->display_code,
                'kode_internal' => $asset->kode_internal,
                'nama_aset' => $asset->nama_aset,
                'kategori' => $asset->category->nama_kategori,
                'merek_tipe' => $asset->merek_tipe,
                'kondisi' => $asset->kondisi->value,
                'label_kondisi' => $asset->kondisi->label(),
                'lokasi_penyimpanan' => $asset->lokasi_penyimpanan,
                'status_siklus' => $asset->tanggal_penghapusan ? 'disposed' : 'active',
            ],
        ]);
    }

    public function svg(Request $request, Asset $asset): Response
    {
        abort_unless(
            ($asset->status_verifikasi ?? 'disetujui') === 'disetujui',
            JsonResponse::HTTP_NOT_FOUND,
            'QR Code tersedia setelah aset diverifikasi Tim Aset.',
        );

        $scanUrl = rtrim(config('app.frontend_url'), '/').'/aset/scan/'.$asset->token_publik;
        $qrCode = new QrCode(
            data: $scanUrl,
            encoding: new Encoding('ISO-8859-1'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 360,
            margin: 16,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );
        $svg = (new SvgWriter)->write($qrCode)->getString();
        $disposition = $request->boolean('download') ? 'attachment' : 'inline';
        $filename = 'qr-'.Str::slug(str_replace(['/', '.', '_'], '-', $asset->kode_internal ?: $asset->id)).'.svg';

        return response($svg, Response::HTTP_OK, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
