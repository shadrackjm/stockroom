<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['user_id', 'event', 'properties'])]
class Activity extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * A readable sentence, e.g. "Shadrack updated price from $20.00 to $25.00".
     */
    public function description(): string
    {
        return ($this->user?->name ?? 'System').' '.$this->summary();
    }

    /**
     * The sentence without the person, e.g. "updated price from $20.00 to $25.00".
     */
    public function summary(): string
    {
        if ($this->event !== 'updated' || empty($this->properties)) {
            return "{$this->event} this product";
        }

        $subject = $this->subject;

        $parts = collect($this->properties)->map(fn (array $change, string $field) => sprintf(
            '%s from %s to %s',
            $subject->activityFieldLabel($field),
            $subject->activityFieldValue($field, $change['old']),
            $subject->activityFieldValue($field, $change['new']),
        ));

        return 'updated '.$parts->join(', ', ' and ');
    }
}