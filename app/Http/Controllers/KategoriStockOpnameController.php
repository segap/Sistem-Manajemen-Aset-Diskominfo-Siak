<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
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
            'canManage' => $this->canManage($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $data = $this->validatedData($request);
        $this->ensureUniqueName($data['nama_kategori']);

        StockOpnameCategory::query()->create($data);

        return redirect()
            ->route('kategori-stock-opname.index')
            ->with('success', 'Kategori stock opname berhasil ditambahkan.');
    }

    public function update(Request $request, StockOpnameCategory $kategoriStockOpname): RedirectResponse
    {
        $this->authorizeManage($request);

        $data = $this->validatedData($request);
        $this->ensureUniqueName($data['nama_kategori'], $kategoriStockOpname->id);

        $kategoriStockOpname->update($data);

        return redirect()
            ->route('kategori-stock-opname.index', $request->only('search', 'page', 'per_page'))
            ->with('success', 'Kategori stock opname berhasil diperbarui.');
    }

    public function destroy(Request $request, StockOpnameCategory $kategoriStockOpname): RedirectResponse
    {
        $this->authorizeManage($request);

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

    private function authorizeManage(Request $request): void
    {
        abort_if(! $this->canManage($request), 403, 'Aksi ini hanya dapat dilakukan oleh Admin.');
    }

    private function canManage(Request $request): bool
    {
        $role = $request->user()?->role;
        $roleValue = $role instanceof UserRole ? $role->value : $role;

        return in_array($roleValue, [UserRole::Admin->value, UserRole::SuperAdmin->value], true);
    }
}
