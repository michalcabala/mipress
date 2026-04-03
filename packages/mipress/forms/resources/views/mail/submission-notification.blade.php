<h1>Nove odeslani formulare: {{ $form->title }}</h1>

@php
    $labels = collect($form->fields ?? [])->mapWithKeys(fn ($field) => [($field['handle'] ?? '') => ($field['label'] ?? $field['handle'] ?? '')]);
@endphp

<ul>
@foreach (($submission->data ?? []) as $key => $value)
    <li><strong>{{ $labels->get($key, $key) }}:</strong> {{ is_scalar($value) ? $value : json_encode($value) }}</li>
@endforeach
</ul>
