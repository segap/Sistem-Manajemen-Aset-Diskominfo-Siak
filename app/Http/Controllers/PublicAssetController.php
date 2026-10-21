<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

class PublicAssetController extends Controller
{
    public function show(string $publicToken): View|Response
    {
        $asset = Asset::query()
            ->with(['category', 'kodeBarang'])
            ->where('token_publik', $publicToken)
            ->where('status_verifikasi', 'disetujui')
            ->first();

        return response()->view('aset.public', [
            'asset' => $asset,
            'error' => $asset ? null : 'Aset tidak ditemukan atau belum tersedia untuk publik.',
        ], $asset ? Response::HTTP_OK : Response::HTTP_NOT_FOUND);
    }
}
