<?php

namespace App\Http\Controllers;

use App\Models\StockOpnameCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class KategoriStockOpnameController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();
        $perPage = min(max($request->integer('per_page', 10), 1), 100);

        $categories = StockOpnameCategory::query()
            ->withCount('items')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('nama_kategori', 'like', "%{$search}%")
                        ->orWhere('keterangan', 'like', "%{$search}%");
                });
            })
            ->orderBy('nama_kategori')
            ->paginate($perPage)
            ->withQueryString();

        return view('kategori-stock-opname.index', [
            'categories' => $categories,
            'search' => $search,
            'selectedPerPage' => $perPage,
            'canCreate' => $request->user()?->hasPermission('kategori_stock_opname.create') ?? false,
            'canUpdate' => $request->user()?->hasPermission('kategori_stock_opname.update') ?? false,
            'canDelete' => $request->user()?->hasPermission('kategori_stock_opname.delete') ?? false,
            'canExport' => $request->user()?->hasPermission('kategori_stock_opname.export') ?? false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $this->ensureUniqueName($data['nama_kategori']);

        StockOpnameCategory::query()->create($data);

        return redirect()
            ->route('kategori-stock-opname.index')
            ->with('success', 'Kategori stock opname berhasil ditambahkan.');
    }

    public function update(Request $request, StockOpnameCategory $kategoriStockOpname): RedirectResponse
    {
        $data = $this->validatedData($request);
        $this->ensureUniqueName($data['nama_kategori'], $kategoriStockOpname->id);

        $kategoriStockOpname->update($data);

        return redirect()
            ->route('kategori-stock-opname.index', $request->only('search', 'page', 'per_page'))
            ->with('success', 'Kategori stock opname berhasil diperbarui.');
    }

    public function destroy(Request $request, StockOpnameCategory $kategoriStockOpname): RedirectResponse
    {
        $itemCount = $kategoriStockOpname->items()->count();
        if ($itemCount > 0) {
            return back()
                ->withInput($request->query())
                ->with('error', "Kategori tidak dapat dihapus karena masih digunakan oleh {$itemCount} barang persediaan.");
        }

        $kategoriStockOpname->delete();

        return redirect()
            ->route('kategori-stock-opname.index', $request->only('search', 'page', 'per_page'))
            ->with('success', 'Kategori stock opname berhasil dihapus.');
    }

    /** @return array{nama_kategori: string, keterangan: ?string} */
    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'nama_kategori' => ['required', 'string', 'max:120'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ]);

        return [
            'nama_kategori' => trim((string) $data['nama_kategori']),
            'keterangan' => blank($data['keterangan'] ?? null) ? null : trim((string) $data['keterangan']),
        ];
    }

    private function ensureUniqueName(string $name, ?int $ignoreId = null): void
    {
        $exists = StockOpnameCategory::query()
            ->whereRaw('LOWER(nama_kategori) = ?', [mb_strtolower($name)])
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'nama_kategori' => 'Nama kategori stock opname sudah digunakan.',
            ]);
        }
    }

}
