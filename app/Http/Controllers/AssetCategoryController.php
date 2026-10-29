<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssetCategoryRequest;
use App\Models\AssetCategory;
use App\Support\CategoryAssetExportPermissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = AssetCategory::query()
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('kode_kategori', 'like', "%{$search}%")
                        ->orWhere('nama_kategori', 'like', "%{$search}%");
                });
            })
            ->orderBy('nama_kategori')
            ->paginate(10)
            ->withQueryString();

        return view('kategori-aset.index', [
            'categories' => $categories,
            'search' => $request->string('search')->toString(),
            'exportFormats' => CategoryAssetExportPermissions::allowedFormats($request->user()),
        ]);
    }

    public function store(AssetCategoryRequest $request): RedirectResponse
    {
        AssetCategory::query()->create($this->normalizedData($request->validated()));

        return redirect()
            ->route('web.kategori-aset.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(AssetCategoryRequest $request, AssetCategory $asset_category): RedirectResponse
    {
        $asset_category->update($this->normalizedData($request->validated()));

        return redirect()
            ->route('web.kategori-aset.index', $request->only('search', 'page'))
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Request $request, AssetCategory $asset_category): RedirectResponse
    {
        if ($asset_category->assets()->exists()) {
            return back()
                ->withInput($request->query())
                ->with('error', 'Kategori yang sudah digunakan oleh aset tidak dapat dihapus.');
        }

        $asset_category->delete();

        return redirect()
            ->route('web.kategori-aset.index', $request->only('search', 'page'))
            ->with('success', 'Kategori berhasil dihapus.');
    }

    /** @param  array<string, mixed>  $data */
    private function normalizedData(array $data): array
    {
        $data['ikon'] = blank($data['ikon'] ?? null) ? null : $data['ikon'];
        $data['deskripsi'] = blank($data['deskripsi'] ?? null) ? null : $data['deskripsi'];

        return $data;
    }
}
