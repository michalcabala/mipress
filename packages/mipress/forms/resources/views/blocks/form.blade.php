@php
    /** @var string|null $formHandle */
    $resolvedForm = null;
    $requiresRecaptcha = false;

    if (filled($formHandle ?? null)) {
        $resolvedForm = \MiPress\Forms\Models\Form::query()
            ->where('handle', $formHandle)
            ->where('is_active', true)
            ->first();

        $requiresRecaptcha = in_array($resolvedForm?->spam_protection, ['recaptcha', 'both'], true)
            && filled($resolvedForm?->recaptcha_site_key);
    }
@endphp

@if (! $resolvedForm)
    <p>Formular neni dostupny.</p>
@else
    @if ($errors->has('form'))
        <p>{{ $errors->first('form') }}</p>
    @endif

    <form method="POST" action="{{ route('mipress.form.submit', ['form' => $resolvedForm->handle]) }}" enctype="multipart/form-data">
        @csrf

        <input type="hidden" name="_form_started_at" value="{{ time() }}">

        <div style="display:none;">
            <label for="website">Website</label>
            <input id="website" type="text" name="website" value="">
        </div>

        @foreach ($resolvedForm->fields as $field)
            @php
                $handle = (string) ($field['handle'] ?? '');
                $type = (string) ($field['type'] ?? 'text');
                $label = (string) ($field['label'] ?? $handle);
                $required = (bool) ($field['required'] ?? false);
                $config = (array) ($field['config'] ?? []);
            @endphp

            @continue($handle === '')

            <div>
                @if ($type !== 'hidden')
                    <label for="{{ $handle }}">{{ $label }}@if($required) *@endif</label>
                @endif

                @if (in_array($type, ['text', 'email', 'phone', 'hidden'], true))
                    <input
                        id="{{ $handle }}"
                        type="{{ $type === 'phone' ? 'tel' : $type }}"
                        name="{{ $handle }}"
                        value="{{ old($handle, $config['value'] ?? '') }}"
                        placeholder="{{ $config['placeholder'] ?? '' }}"
                        @if($required) required @endif
                    >
                @elseif ($type === 'textarea')
                    <textarea
                        id="{{ $handle }}"
                        name="{{ $handle }}"
                        rows="{{ $config['rows'] ?? 4 }}"
                        placeholder="{{ $config['placeholder'] ?? '' }}"
                        @if($required) required @endif
                    >{{ old($handle) }}</textarea>
                @elseif ($type === 'select')
                    <select id="{{ $handle }}" name="{{ $handle }}" @if($required) required @endif>
                        <option value="">{{ $config['placeholder'] ?? 'Vyberte...' }}</option>
                        @foreach (($config['options'] ?? []) as $value => $optionLabel)
                            <option value="{{ $value }}" @selected(old($handle) == $value)>{{ $optionLabel }}</option>
                        @endforeach
                    </select>
                @elseif ($type === 'checkbox')
                    <input id="{{ $handle }}" type="checkbox" name="{{ $handle }}" value="1" @checked(old($handle))>
                @elseif ($type === 'radio')
                    @foreach (($config['options'] ?? []) as $value => $optionLabel)
                        <label>
                            <input type="radio" name="{{ $handle }}" value="{{ $value }}" @checked(old($handle) == $value)>
                            {{ $optionLabel }}
                        </label>
                    @endforeach
                @elseif ($type === 'file')
                    <input id="{{ $handle }}" type="file" name="{{ $handle }}" @if($required) required @endif>
                @endif

                @error($handle)
                    <p>{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        @if ($requiresRecaptcha)
            <input type="hidden" id="mipress-forms-recaptcha-token-{{ $resolvedForm->handle }}" name="g-recaptcha-response" value="">
        @endif

        @if (session('mipress_form_success'))
            <p>{{ session('mipress_form_success') }}</p>
        @endif

        <button type="submit">Odeslat</button>
    </form>

    @if ($requiresRecaptcha)
        <script src="https://www.google.com/recaptcha/api.js?render={{ $resolvedForm->recaptcha_site_key }}"></script>
        <script>
            grecaptcha.ready(function () {
                grecaptcha.execute('{{ $resolvedForm->recaptcha_site_key }}', { action: 'form_submit' }).then(function (token) {
                    const input = document.getElementById('mipress-forms-recaptcha-token-{{ $resolvedForm->handle }}');

                    if (input) {
                        input.value = token;
                    }
                });
            });
        </script>
    @endif
@endif
