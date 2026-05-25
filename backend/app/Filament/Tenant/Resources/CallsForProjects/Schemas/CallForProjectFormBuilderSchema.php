<?php

namespace App\Filament\Tenant\Resources\CallsForProjects\Schemas;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class CallForProjectFormBuilderSchema
{
    public static function make(string $name = 'meta.application_form.fields'): Builder
    {
        return Builder::make($name)
            ->label('Questionnaire / form builder')
            ->blocks(static::fieldBlocks())
            ->afterStateHydrated(function (Builder $component, mixed $state): void {
                $normalizedState = static::normalizeBuilderState($state);

                $component->state(static::isLegacyDefaultState($normalizedState) ? [] : $normalizedState);
            })
            ->columnSpanFull();
    }

    public static function fieldBlocks(): array
    {
        return [
            Builder\Block::make('text')->label('Texte court')->schema(static::commonFieldSchema()),
            Builder\Block::make('textarea')->label('Texte long')->schema(static::commonFieldSchema()),
            Builder\Block::make('email')->label('E-mail')->schema(static::commonFieldSchema()),
            Builder\Block::make('number')->label('Nombre')->schema(static::commonFieldSchema()),
            Builder\Block::make('date')->label('Date')->schema(static::commonFieldSchema()),
            Builder\Block::make('country')->label('Pays')->schema(static::commonFieldSchema()),
            Builder\Block::make('city')->label('Ville')->schema(array_merge(static::commonFieldSchema(), [
                TextInput::make('country_field')->label('Champ pays lié')->placeholder('country_of_residence')->maxLength(80),
            ])),
            Builder\Block::make('phone')->label('Téléphone')->schema(array_merge(static::commonFieldSchema(), [
                TextInput::make('country_field')->label('Champ pays lié')->placeholder('country_of_residence')->maxLength(80),
            ])),
            Builder\Block::make('radio')->label('Choix unique')->schema(array_merge(static::commonFieldSchema(), [
                KeyValue::make('options')->label('Options')->helperText('Clé = valeur technique, valeur = libellé public.'),
            ])),
            Builder\Block::make('checkbox_group')->label('Choix multiples')->schema(array_merge(static::commonFieldSchema(), [
                KeyValue::make('options')->label('Options')->helperText('Clé = valeur technique, valeur = libellé public.'),
            ])),
            Builder\Block::make('boolean')->label('Oui / Non')->schema(array_merge(static::commonFieldSchema(), [
                TextInput::make('true_label')->label('Libellé Oui')->maxLength(255),
                TextInput::make('false_label')->label('Libellé Non')->maxLength(255),
                Toggle::make('must_be_true')->label('Doit être accepté')->default(false),
            ])),
            Builder\Block::make('file')->label('Fichier')->schema(array_merge(static::commonFieldSchema(), [
                TagsInput::make('accept')->label('Types MIME acceptés')->placeholder('application/pdf'),
                TextInput::make('max_size_mb')->label('Taille max (MB)')->numeric()->minValue(1)->default(10),
            ])),
            Builder\Block::make('section')->label('Section informative')->schema([
                TextInput::make('key')->label('Clé')->required()->maxLength(80),
                TextInput::make('label')->label('Titre')->required()->maxLength(255),
                Textarea::make('help_text')->label('Texte')->rows(3)->columnSpanFull(),
                Select::make('step')->label('Étape')->options(static::stepOptions())->native(false),
            ]),
        ];
    }

    public static function commonFieldSchema(): array
    {
        return [
            TextInput::make('key')->label('Clé')->required()->maxLength(80),
            TextInput::make('label')->label('Libellé')->required()->maxLength(255),
            Select::make('step')->label('Étape')->options(static::stepOptions())->native(false),
            TextInput::make('section')->label('Section')->placeholder('project_overview')->maxLength(80),
            TextInput::make('section_title')->label('Titre section')->maxLength(255),
            Textarea::make('section_description')->label('Description section')->rows(2)->columnSpanFull(),
            Textarea::make('help_text')->label('Aide')->rows(2)->columnSpanFull(),
            Toggle::make('required')->label('Obligatoire')->default(false),
            TextInput::make('placeholder')->label('Placeholder')->maxLength(255),
            Select::make('column_span')->label('Largeur')->options([
                1 => 'Demi largeur',
                2 => 'Pleine largeur',
            ])->default(2)->native(false),
        ];
    }

    private static function stepOptions(): array
    {
        return [
            'identity' => 'Identité',
            'project' => 'Projet',
            'documents' => 'Documents',
            'payment' => 'Paiement / validation',
        ];
    }

    private static function normalizeBuilderState(mixed $fields): array
    {
        if (! is_array($fields)) {
            return [];
        }

        return collect($fields)
            ->map(function ($field): ?array {
                if (! is_array($field)) {
                    return null;
                }

                if (isset($field['type'], $field['data']) && is_array($field['data'])) {
                    return $field;
                }

                return static::fieldToBlock($field);
            })
            ->filter()
            ->values()
            ->all();
    }

    private static function fieldToBlock(array $field): ?array
    {
        $type = (string) ($field['type'] ?? 'text');

        if (! in_array($type, static::blockTypes(), true)) {
            $type = 'text';
        }

        if (isset($field['options']) && is_array($field['options']) && array_is_list($field['options'])) {
            $field['options'] = collect($field['options'])
                ->mapWithKeys(function ($option): array {
                    if (! is_array($option)) {
                        return [(string) $option => (string) $option];
                    }

                    $value = (string) ($option['value'] ?? '');
                    $label = (string) ($option['label'] ?? $value);

                    return $value !== '' ? [$value => $label] : [];
                })
                ->all();
        }

        unset($field['type']);

        return [
            'type' => $type,
            'data' => $field,
        ];
    }

    private static function blockTypes(): array
    {
        return [
            'text',
            'textarea',
            'email',
            'number',
            'date',
            'country',
            'city',
            'phone',
            'radio',
            'checkbox_group',
            'boolean',
            'file',
        ];
    }

    private static function isLegacyDefaultState(array $state): bool
    {
        if ($state === []) {
            return false;
        }

        $defaultState = static::normalizeBuilderState(config('call_for_project_forms.default.fields', []));

        if (count($state) !== count($defaultState)) {
            return false;
        }

        return static::stateSignature($state) === static::stateSignature($defaultState);
    }

    private static function stateSignature(array $state): array
    {
        return collect($state)
            ->map(function (array $block): string {
                $data = is_array($block['data'] ?? null) ? $block['data'] : [];

                return implode('|', [
                    (string) ($block['type'] ?? ''),
                    (string) ($data['key'] ?? ''),
                    (string) ($data['label'] ?? ''),
                ]);
            })
            ->values()
            ->all();
    }
}
