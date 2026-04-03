<h1>Nove odeslani formulare: {{ $form->title }}</h1>

<ul>
@foreach (($submission->data ?? []) as $key => $value)
    <li><strong>{{ $key }}:</strong> {{ is_scalar($value) ? $value : json_encode($value) }}</li>
@endforeach
</ul>
