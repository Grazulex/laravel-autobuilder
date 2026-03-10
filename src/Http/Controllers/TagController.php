<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Http\Controllers;

use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index(): JsonResponse
    {
        $tags = Tag::orderBy('name')->get(['id', 'name', 'slug']);

        return response()->json(['data' => $tags]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:autobuilder_tags,name'],
        ]);

        $tag = Tag::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return response()->json(['data' => $tag], 201);
    }

    public function attach(string $flow, string $tag): JsonResponse
    {
        $flowModel = Flow::findOrFail($flow);
        $tagModel = Tag::findOrFail($tag);

        $flowModel->tags()->syncWithoutDetaching([$tagModel->id]);

        return response()->json(['message' => 'Tag attached']);
    }

    public function detach(string $flow, string $tag): JsonResponse
    {
        $flowModel = Flow::findOrFail($flow);
        $tagModel = Tag::findOrFail($tag);

        $flowModel->tags()->detach($tagModel->id);

        return response()->json(['message' => 'Tag detached']);
    }
}
