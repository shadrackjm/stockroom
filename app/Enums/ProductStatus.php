<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    /**
     * The human-friendly name shown in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Archived => 'Archived',
        };
    }

    /**
     * The badge colour used for this status.
     */
    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Active => 'green',
            self::Archived => 'amber',
        };
    }

    /**
     * Every status as [value => label], handy for select inputs.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }
}