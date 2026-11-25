@extends('layouts.child')

@section('title', 'Misi')

@section('content')
    <h1 class="text-4xl font-extrabold">Halo, {{ auth()->user()->name }}! {{ auth()->user()->avatar }}</h1>
@endsection
