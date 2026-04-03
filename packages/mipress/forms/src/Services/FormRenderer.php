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

        foreach ($this->sortedFields($form) as $field) {
            $handle = (string) ($field['handle'] ?? '');
            $type = (string) ($field['type'] ?? FormField::TYPE_TEXT);
            $required = (bool) ($field['required'] ?? false);
            $config = (array) ($field['config'] ?? []);

            if ($handle === '') {
                continue;
            }

            $definition = [$required ? 'required' : 'nullable'];

            match ($type) {
                FormField::TYPE_EMAIL => $definition = [...$definition, 'email'],
                FormField::TYPE_PHONE => $definition = [...$definition, 'string', 'max:50'],
                FormField::TYPE_TEXTAREA => $definition = [...$definition, 'string'],
                FormField::TYPE_CHECKBOX => $definition = [...$definition, 'boolean'],
                FormField::TYPE_SELECT, FormField::TYPE_RADIO => $definition = [
                    ...$definition,
                    Rule::in(array_keys((array) ($config['options'] ?? []))),
                ],
                FormField::TYPE_FILE => $definition = $this->fileRules($definition, $config),
                default => $definition = [...$definition, 'string'],
            };

            if ($type === FormField::TYPE_TEXT && filled($config['max_length'] ?? null)) {
                $definition[] = 'max:'.(int) $config['max_length'];
            }

            $rules[$handle] = $definition;
        }

        return $rules;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function sortedFields(Form $form): array
    {
        return collect($form->fields ?? [])
            ->sortBy(static fn (array $field): int => (int) ($field['order'] ?? 0))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $base
     * @param  array<string, mixed>  $config
     * @return array<int, mixed>
     */
    private function fileRules(array $base, array $config): array
    {
        $rules = [...$base, 'file'];

        if (filled($config['accepted'] ?? null)) {
            $extensions = collect(explode(',', (string) $config['accepted']))
                ->map(static fn (string $value): string => ltrim(trim($value), '.'))
                ->filter()
                ->values()
                ->all();

            if ($extensions !== []) {
                $rules[] = 'mimes:'.implode(',', $extensions);
            }
        }

        if (filled($config['max_size_mb'] ?? null)) {
            $maxKb = max(1, (int) $config['max_size_mb']) * 1024;
            $rules[] = 'max:'.$maxKb;
        }

        return $rules;
    }
}
