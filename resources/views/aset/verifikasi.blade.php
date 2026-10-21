@extends('layouts.app')

@section('title', 'Verifikasi Barang - SIMIVA')

@section('content')
    @include('aset.partials.list', [
        'title' => 'Verifikasi Barang',
        'description' => 'Tinjau aset baru yang diinput admin sebelum QR dan stoknya aktif.',
        'routeName' => 'web.aset.verifikasi.index',
        'verificationMode' => true,
    ])
@endsection
