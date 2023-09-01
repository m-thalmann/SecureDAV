@extends('layouts._base', ['title' => $title, 'bodyClass' => 'flex flex-col sm:justify-center items-center pt-6 sm:pt-0 max-sm:bg-base-200'])

@section('htmlBody')
    {{ $slot }}
@endsection