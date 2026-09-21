<?php

namespace App\Concerns;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

/**
 * Records who created, changed, deleted or restored a model — and exactly
 * which fields changed, from what, to what.
 */
trait LogsActivity
{
    /**
     * Laravel calls boot{TraitName}() automatically for every model using this trait.
     */
    public static function bootLogsActivity(): void
    {
        static::created(fn (self $model) => $model->logActivity('created'));

        static::updated(function (self $model) {
            $changes = $model->activityChanges();

            if ($changes !== []) {
                $model->logActivity('updated', $changes);
            }
        });

        static::deleted(function (self $model) {
            if (! $model->isForceDeleting()) {
                $model->logActivity('deleted');
            }
        });

        static::restored(fn (self $model) => $model->logActivity('restored'));

        // Permanently deleted: the history goes with it.
        static::forceDeleted(fn (self $model) => $model->activities()->delete());
    }

    /**
     * @return MorphMany<Activity, $this>
     */
    public function activities(): MorphMany
    {
        // chaperone() gives each activity its parent model without an extra query.
        return $this->morphMany(Activity::class, 'subject')->chaperone()->latest('id');
    }

    /**
     * @param  array<string, array{old: mixed, new: mixed}>|null  $changes
     */
    public function logActivity(string $event, ?array $changes = null): Activity
    {
        return $this->activities()->create([
            'user_id' => Auth::id(),
            'event' => $event,
            'properties' => $changes,
        ]);
    }

    /**
     * The fields that changed in the save that just happened, as [field => [old, new]].
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    protected function activityChanges(): array
    {
        return collect($this->getChanges())
            ->except($this->ignoredActivityFields())
            ->map(fn (mixed $new, string $field) => [
                'old' => $this->getRawOriginal($field),
                'new' => $new,
            ])
            ->all();
    }

    /**
     * Fields that change on every save and would only add noise to the log.
     *
     * @return array<int, string>
     */
    protected function ignoredActivityFields(): array
    {
        return ['updated_at', 'deleted_at', 'version'];
    }

    /**
     * How a field name reads in a sentence. Override in the model for nicer names.
     */
    public function activityFieldLabel(string $field): string
    {
        return str_replace('_', ' ', $field);
    }

    /**
     * How a stored value reads in a sentence. Override in the model to format money, enums, etc.
     */
    public function activityFieldValue(string $field, mixed $value): string
    {
        return $value === null || $value === '' ? 'empty' : (string) $value;
    }
}