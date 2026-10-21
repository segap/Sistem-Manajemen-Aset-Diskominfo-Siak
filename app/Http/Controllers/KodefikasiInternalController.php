<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Services\KodeInternalService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KodefikasiInternalController extends Controller
{
    public function index(Request $request, KodeInternalService $service): View
    {
        $perPage = min(max($request->integer('per_page', 10), 1), 100);
        $baseQuery = Asset::query()->whereNull('tanggal_penghapusan');

        $assets = Asset::query()
            ->with(['category', 'kodeBarang'])
            ->whereNull('tanggal_penghapusan')
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(fn (Builder $query) => $query
                    ->where('kode_internal', 'like', "%{$search}%")
                    ->orWhere('nama_aset', 'like', "%{$search}%")
                    ->orWhereHas('kodeBarang', fn (Builder $query) => $query
                        ->where('kode_barang', 'like', "%{$search}%")
                        ->orWhere('nama_barang', 'like', "%{$search}%")));
            })
            ->when($request->input('status_kode') === 'sudah', fn (Builder $query) => $query->whereNotNull('kode_internal'))
            ->when($request->input('status_kode') === 'belum', fn (Builder $query) => $query->whereNull('kode_internal'))
            ->when($request->integer('kategori_id'), fn (Builder $query, int $categoryId) => $query->where('kategori_aset_id', $categoryId))
            ->when($request->filled('lokasi'), fn (Builder $query) => $query->where('lokasi_penyimpanan', $request->string('lokasi')))
            ->orderByRaw('kode_internal IS NULL DESC')
            ->orderBy('nama_aset')
            ->paginate($perPage)
            ->withQueryString();

        $previews = $assets->getCollection()->mapWithKeys(fn (Asset $asset) => [
            $asset->id => $asset->kode_internal ? null : $service->preview($asset),
        ]);

        return view('kodefikasi-internal.index', [
            'assets' => $assets,
            'previews' => $previews,
            'categories' => AssetCategory::query()->orderBy('nama_kategori')->get(),
            'locations' => Asset::query()
                ->whereNull('tanggal_penghapusan')
                ->whereNotNull('lokasi_penyimpanan')
                ->where('lokasi_penyimpanan', '!=', '')
                ->distinct()
                ->orderBy('lokasi_penyimpanan')
                ->pluck('lokasi_penyimpanan'),
            'totalAssets' => (clone $baseQuery)->count(),
            'codedAssets' => (clone $baseQuery)->whereNotNull('kode_internal')->count(),
            'uncodedAssets' => (clone $baseQuery)->whereNull('kode_internal')->count(),
        ]);
    }

    public function generate(Request $request, Asset $asset, KodeInternalService $service): RedirectResponse
    {
        $this->authorizeManage($request);
        $data = $request->validate(['prefix' => ['nullable', 'string', 'max:10']]);
        $code = $service->generate($asset, $data['prefix'] ?? null);

        return back()->with('success', 'Kode internal '.$code.' berhasil dibuat.');
    }

    public function update(Request $request, Asset $asset, KodeInternalService $service): RedirectResponse
    {
        $this->authorizeManage($request);
        $data = $request->validate([
            'kode_internal' => ['required', 'string', 'regex:/^[A-Z]{2,5}\/[0-9]{3,}$/'],
        ], [
            'kode_internal.regex' => 'Format kode internal harus seperti PRN/001.',
        ]);

        $code = $service->update($asset, $data['kode_internal']);

        return back()->with('success', 'Kode internal berhasil diperbarui menjadi '.$code.'.');
    }

    public function reset(Request $request, Asset $asset, KodeInternalService $service): RedirectResponse
    {
        $this->authorizeManage($request);
        $service->reset($asset);

        return back()->with('success', 'Kode internal aset berhasil direset.');
    }

    public function bulkGenerate(Request $request, KodeInternalService $service): RedirectResponse
    {
        $this->authorizeManage($request);
        $result = $service->bulkGenerate();
        $message = 'Generate massal selesai. Berhasil: '.$result['success'].', perlu ditinjau: '.count($result['review']).'.';

        return back()
            ->with('success', $message)
            ->with('bulk_review', $result['review']);
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless(in_array($request->user()?->role, ['admin', 'super_admin'], true), 403);
    }
}
