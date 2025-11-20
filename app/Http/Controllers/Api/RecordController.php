<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRecordRequest;
use App\Http\Requests\UpdateRecordRequest;
use App\Http\Resources\RecordResource;
use App\Models\Record;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecordController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of records with search, filter, and pagination.
     *
     * Query parameters:
     * - search: Full-text search across title, description, tags, group
     * - type: Filter by record type (password, contact, code, note)
     * - archived: Filter by archived status (0=active, 1=archived, all=both)
     * - tag: Filter by specific tag
     * - sort: Sort field (default: updated_at)
     * - direction: Sort direction (asc/desc, default: desc)
     * - per_page: Results per page (default: 15, max: 100)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Record::class);

        $query = Record::with(['creator', 'updater']);

        // Search
        if ($request->has('search')) {
            $query->search($request->input('search'));
        }

        // Filter by type
        if ($request->has('type')) {
            $query->ofType($request->input('type'));
        }

        // Filter by archived status
        $archived = $request->input('archived', '0');
        if ($archived === 'all') {
            // No filter - show both
        } elseif ($archived === '1') {
            $query->archived();
        } else {
            $query->active();
        }

        // Filter by tag
        if ($request->has('tag')) {
            $query->whereJsonContains('tags', $request->input('tag'));
        }

        // Filter by group
        if ($request->has('group')) {
            $query->where('group', $request->input('group'));
        }

        // Sorting
        $sortField = $request->input('sort', 'updated_at');
        $sortDirection = $request->input('direction', 'desc');

        $allowedSortFields = ['title', 'type', 'created_at', 'updated_at', 'group'];
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('updated_at', 'desc');
        }

        // Pagination
        $perPage = min($request->input('per_page', 15), 100);
        $records = $query->paginate($perPage);

        return RecordResource::collection($records);
    }

    /**
     * Store a newly created record.
     */
    public function store(StoreRecordRequest $request): RecordResource
    {
        $this->authorize('create', Record::class);

        $record = Record::create($request->validated());

        return new RecordResource($record->load(['creator', 'updater']));
    }

    /**
     * Display the specified record.
     */
    public function show(Record $record): RecordResource
    {
        $this->authorize('view', $record);

        return new RecordResource($record->load(['creator', 'updater']));
    }

    /**
     * Update the specified record.
     */
    public function update(UpdateRecordRequest $request, Record $record): RecordResource
    {
        $this->authorize('update', $record);

        $record->update($request->validated());

        return new RecordResource($record->load(['creator', 'updater']));
    }

    /**
     * Remove the specified record (hard delete - admin only).
     */
    public function destroy(Record $record): JsonResponse
    {
        $this->authorize('delete', $record);

        $record->delete();

        return response()->json([
            'message' => 'Record permanently deleted.',
        ]);
    }

    /**
     * Archive the specified record.
     */
    public function archive(Record $record): RecordResource
    {
        $this->authorize('update', $record);

        $record->archive();

        return new RecordResource($record->load(['creator', 'updater']));
    }

    /**
     * Restore the specified record from archive.
     */
    public function restore(Record $record): RecordResource
    {
        $this->authorize('update', $record);

        $record->restore();

        return new RecordResource($record->load(['creator', 'updater']));
    }

    /**
     * Get change history for the specified record.
     */
    public function changes(Record $record): JsonResponse
    {
        $this->authorize('view', $record);

        $changes = $record->changes()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($change) {
                return [
                    'id' => $change->id,
                    'action' => $change->action,
                    'changes' => $change->changes,
                    'user' => [
                        'id' => $change->user->id,
                        'name' => $change->user->name,
                        'avatar' => $change->user->avatar,
                    ],
                    'created_at' => $change->created_at->toISOString(),
                ];
            });

        return response()->json($changes);
    }

    /**
     * Export selected records as JSON
     */
    public function export(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Record::class);

        $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'exists:records,id',
            'include_archived' => 'boolean',
        ]);

        $query = Record::with(['creator', 'updater']);

        // If specific IDs provided, only export those
        if ($request->has('ids') && count($request->ids) > 0) {
            $query->whereIn('id', $request->ids);
        } elseif (!$request->boolean('include_archived', false)) {
            // Otherwise, respect archived filter
            $query->active();
        }

        $records = $query->get()->map(function ($record) {
            return [
                'type' => $record->type,
                'title' => $record->title,
                'description' => $record->description,
                'tags' => $record->tags,
                'group' => $record->group,
                'data' => $record->data,
                'is_archived' => $record->is_archived,
                'created_at' => $record->created_at->toISOString(),
            ];
        });

        return response()->json([
            'records' => $records,
            'exported_at' => now()->toISOString(),
            'count' => $records->count(),
        ]);
    }

    /**
     * Import records from JSON
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', Record::class);

        $request->validate([
            'records' => 'required|array',
            'records.*.type' => 'required|in:password,contact,code,note',
            'records.*.title' => 'required|string',
        ]);

        $imported = 0;
        $errors = [];

        foreach ($request->records as $index => $recordData) {
            try {
                Record::create([
                    'type' => $recordData['type'],
                    'title' => $recordData['title'],
                    'description' => $recordData['description'] ?? null,
                    'tags' => $recordData['tags'] ?? [],
                    'group' => $recordData['group'] ?? null,
                    'data' => $recordData['data'] ?? [],
                    'is_archived' => $recordData['is_archived'] ?? false,
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'title' => $recordData['title'] ?? 'Unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'imported' => $imported,
            'total' => count($request->records),
            'errors' => $errors,
        ]);
    }
}
