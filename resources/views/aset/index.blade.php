@extends('layouts.app')

@section('title', 'Master Data - SIMIVA')

@section('content')
    @include('aset.partials.list', [
        'title' => 'Master Data',
        'description' => 'Pantau data aset inventaris dan barang persediaan dalam satu halaman.',
        'routeName' => 'web.aset.index',
        'verificationMode' => false,
    ])
@endsection
