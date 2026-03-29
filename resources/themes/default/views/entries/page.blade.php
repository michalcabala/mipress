@extends('layouts.app')

@section('title', $entry->title)
@section('meta_description', $entry->data['meta_description'] ?? '')

@section('content')
    <article>
        <h1>{{ $entry->title }}</h1>

        {{-- Render blueprint field data --}}
        @foreach($entry->data ?? [] as $key => $value)
            @if(! in_array($key, ['meta_title', 'meta_description']) && filled($value))
                <div>
                    {!! is_string($value) ? nl2br(e($value)) : '' !!}
                </div>
            @endif
        @endforeach
    </article>
@endsection
