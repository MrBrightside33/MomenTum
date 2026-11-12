@extends('layouts.marketing')

@section('content')
    {{-- Hero section --}}
    @include('landing.hero')

    {{-- Feature section --}}
    @include('landing.features')

    {{-- Call to action section --}}
    @include('landing.cta')
@endsection
