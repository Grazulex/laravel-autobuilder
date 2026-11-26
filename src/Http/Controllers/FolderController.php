<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Http\Controllers;

use Grazulex\AutoBuilder\Http\Resources\FlowResource;
use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Models\Folder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FolderController extends Controller
{
    public function index(): JsonResponse
    {
        $folders = Folder::query()
            ->withCount('flows')
            ->orderBy('name')
            ->get()
            ->map(fn (Folder $folder): array => $this->transform($folder));

        return response()->json([
            'data' => $folders,
            'meta' => [
                'uncategorized_count' => Flow::query()->whereNull('folder_id')->count(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'string', 'exists:autobuilder_folders,id'],
        ]);

        $parentId = ($validated['parent_id'] ?? null) ?: null;

        $this->ensureNameIsAvailable($validated['name'], $parentId);

        $folder = Folder::create([
            'name' => $validated['name'],
            'parent_id' => $parentId,
        ]);

        return response()->json(['data' => $this->transform($folder)], 201);
    }

    public function update(Request $request, string $folder): JsonResponse
    {
        $folderModel = Folder::findOrFail($folder);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'parent_id' => ['sometimes', 'nullable', 'string', 'exists:autobuilder_folders,id'],
        ]);

        $name = $validated['name'] ?? $folderModel->name;
        $parentId = array_key_exists('parent_id', $validated)
            ? ($validated['parent_id'] ?: null)
            : $folderModel->parent_id;

        if (! $folderModel->canMoveTo($parentId)) {
            throw ValidationException::withMessages([
                'parent_id' => 'A folder cannot be moved into itself or one of its sub-folders.',
            ]);
        }

        $this->ensureNameIsAvailable($name, $parentId, $folderModel->id);

        $folderModel->update([
            'name' => $name,
            'parent_id' => $parentId,
        ]);

        return response()->json(['data' => $this->transform($folderModel->loadCount('flows'))]);
    }

    public function destroy(string $folder): JsonResponse
    {
        $folderModel = Folder::findOrFail($folder);

        DB::transaction(fn () => $folderModel->delete());

        return response()->json(['message' => 'Folder deleted']);
    }

    public function assign(Request $request, string $flow): FlowResource
    {
        $flowModel = Flow::findOrFail($flow);

        $validated = $request->validate([
            'folder_id' => ['present', 'nullable', 'string', 'exists:autobuilder_folders,id'],
        ]);

        $flowModel->update(['folder_id' => ($validated['folder_id'] ?? null) ?: null]);

        return new FlowResource($flowModel->load('folder'));
    }

    /**
     * Folder names must be unique among the folders sharing the same parent.
     */
    protected function ensureNameIsAvailable(string $name, ?string $parentId, ?string $ignoreId = null): void
    {
        $taken = Folder::query()
            ->where('name', $name)
            ->when($parentId === null, fn ($q) => $q->whereNull('parent_id'), fn ($q) => $q->where('parent_id', $parentId))
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'name' => 'A folder with this name already exists at this level.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function transform(Folder $folder): array
    {
        return [
            'id' => $folder->id,
            'name' => $folder->name,
            'parent_id' => $folder->parent_id,
            'flows_count' => (int) ($folder->flows_count ?? 0),
        ];
    }
}
