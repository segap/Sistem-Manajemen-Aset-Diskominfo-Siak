<?php

namespace App\Http\Controllers;

use App\Enums\RuanganStatus;
use App\Http\Requests\RuanganRequest;
use App\Models\Ruangan;
use App\Models\KodeLokasi;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RuanganController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = min(max($request->integer('per_page', 10), 1), 100);
        $status = RuanganStatus::tryFrom($request->string('status')->toString());

        $ruangan = Ruangan::query()
            ->with('penanggungJawab')
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('kode_ruangan', 'like', "%{$search}%")
                        ->orWhere('nama_ruangan', 'like', "%{$search}%")
                        ->orWhereHas('penanggungJawab', fn (Builder $query) => $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->when($status, fn (Builder $query) => $query->where('status', $status->value))
            ->orderBy('nama_ruangan')
            ->paginate($perPage)
            ->withQueryString();

        return view('ruangan.index', [
            'ruangan' => $ruangan,
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email', 'nip']),
            'kodeLokasiOptions' => KodeLokasi::query()->orderBy('kode_lokasi')->limit(500)->get(['id', 'kode_lokasi', 'nama_lokasi']),
            'statuses' => RuanganStatus::cases(),
        ]);
    }

    public function store(RuanganRequest $request): RedirectResponse
    {
        Ruangan::query()->create($request->validated());

        return redirect()->route('ruangan.index')->with('success', 'Ruangan berhasil ditambahkan.');
    }

    public function update(RuanganRequest $request, Ruangan $ruangan): RedirectResponse
    {
        $ruangan->update($request->validated());

        return redirect()
            ->route('ruangan.index', $request->only('search', 'status', 'per_page', 'page'))
            ->with('success', 'Ruangan berhasil diperbarui.');
    }

    public function destroy(Request $request, Ruangan $ruangan): RedirectResponse
    {
        $ruangan->delete();

        return redirect()
            ->route('ruangan.index', $request->only('search', 'status', 'per_page', 'page'))
            ->with('success', 'Ruangan berhasil dihapus.');
    }
}
