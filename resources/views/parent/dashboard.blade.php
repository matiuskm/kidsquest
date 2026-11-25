@extends('layouts.parent')

@section('title', 'Beranda')

@section('content')
    <h1 class="text-xl font-bold">Halo, {{ auth()->user()->name }}</h1>
@endsection
