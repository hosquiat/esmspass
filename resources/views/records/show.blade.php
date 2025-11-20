@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div id="record-detail" data-record-id="{{ request()->route('record') }}"></div>
</div>
@endsection
