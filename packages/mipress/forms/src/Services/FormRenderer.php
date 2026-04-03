<?php

declare(strict_types=1);

namespace MiPress\Forms\Services;

use Illuminate\Validation\Rule;
use MiPress\Forms\Models\Form;
use MiPress\Forms\Models\FormField;

class FormRenderer
{
    public function resolveForm(Form|string $form): Form
    {
        if ($form instanceof Form) {
            return $form;
        }

        return Form::query()->where('handle', $form)->firstOrFail();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(Form $form): array
    {
        $rules = [];

        foreach ($form->fields as $field) {
            $handle = (string) ($field['handle'] ?? '');
            $type = (string) ($field['type'] ?? FormField::TYPE_TEXT);
            $required = (bool) ($field['required'] ?? false);

            if ($handle === '') {
                continue;
            }

            $definition = [$required ? 'required' : 'nullable'];

            match ($type) {
                FormField::TYPE_EMAIL => $definition[] = 'email',
                FormField::TYPE_FILE => $definition[] = 'file',
                FormField::TYPE_SELECT, FormField::TYPE_RADIO => $definition[] = Rule::in(array_keys((array) ($field['config']['options'] ?? []))),
                default => $definition[] = 'string',
            };

            $rules[$handle] = $definition;
        }

        return $rules;
    }
}
