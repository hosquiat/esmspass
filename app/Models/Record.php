<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Record extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'type',
        'title',
        'description',
        'tags',
        'group',
        'data',
        'is_archived',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tags' => 'array',
        'data' => 'encrypted:array', // Encrypt the entire data JSON column
        'is_archived' => 'boolean',
    ];

    /**
     * Valid record types.
     */
    public const TYPE_PASSWORD = 'password';
    public const TYPE_CONTACT = 'contact';
    public const TYPE_CODE = 'code';
    public const TYPE_NOTE = 'note';

    public const VALID_TYPES = [
        self::TYPE_PASSWORD,
        self::TYPE_CONTACT,
        self::TYPE_CODE,
        self::TYPE_NOTE,
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically set created_by when creating
        static::creating(function ($record) {
            if (auth()->check() && !$record->created_by) {
                $record->created_by = auth()->id();
            }
        });

        // Automatically set updated_by when updating
        static::updating(function ($record) {
            if (auth()->check()) {
                $record->updated_by = auth()->id();
            }
        });
    }

    /**
     * Get the user who created this record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get all changes for this record.
     */
    public function changes()
    {
        return $this->hasMany(RecordChange::class);
    }

    /**
     * Scope a query to only include active records.
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope a query to only include archived records.
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to search records.
     */
    public function scopeSearch($query, ?string $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('group', 'like', "%{$search}%")
              ->orWhereJsonContains('tags', $search);
        });
    }

    /**
     * Archive this record.
     */
    public function archive(): bool
    {
        return $this->update(['is_archived' => true]);
    }

    /**
     * Restore this record from archive.
     */
    public function restore(): bool
    {
        return $this->update(['is_archived' => false]);
    }
}
