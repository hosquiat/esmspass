<?php

namespace App\Observers;

use App\Models\Record;
use App\Models\RecordChange;

class RecordObserver
{
    /**
     * Handle the Record "created" event.
     */
    public function created(Record $record): void
    {
        RecordChange::create([
            'record_id' => $record->id,
            'user_id' => auth()->id() ?? $record->created_by,
            'action' => 'created',
            'changes' => null,
            'created_at' => now(),
        ]);
    }

    /**
     * Handle the Record "updated" event.
     */
    public function updated(Record $record): void
    {
        // Get what changed
        $changes = [];
        foreach ($record->getDirty() as $key => $newValue) {
            if (in_array($key, ['updated_at', 'updated_by'])) {
                continue; // Skip meta fields
            }
            $changes[$key] = [
                'old' => $record->getOriginal($key),
                'new' => $newValue,
            ];
        }

        if (empty($changes)) {
            return;
        }

        // Determine action
        $action = 'updated';
        if (isset($changes['is_archived'])) {
            $action = $changes['is_archived']['new'] ? 'archived' : 'restored';
        }

        RecordChange::create([
            'record_id' => $record->id,
            'user_id' => auth()->id() ?? $record->updated_by,
            'action' => $action,
            'changes' => $changes,
            'created_at' => now(),
        ]);
    }

    /**
     * Handle the Record "deleted" event.
     */
    public function deleted(Record $record): void
    {
        RecordChange::create([
            'record_id' => $record->id,
            'user_id' => auth()->id(),
            'action' => 'deleted',
            'changes' => null,
            'created_at' => now(),
        ]);
    }

    /**
     * Handle the Record "restored" event.
     */
    public function restored(Record $record): void
    {
        //
    }

    /**
     * Handle the Record "force deleted" event.
     */
    public function forceDeleted(Record $record): void
    {
        //
    }
}
