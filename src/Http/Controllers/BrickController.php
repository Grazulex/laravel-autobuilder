<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Http\Controllers;

use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BrickController extends Controller
{
    public function __construct(
        protected BrickRegistry $registry
    ) {}

    public function index(Request $request): JsonResponse
    {
        $all = $this->registry->all();

        // Filter by type if specified
        if ($type = $request->input('type')) {
            $all = match ($type) {
                'triggers' => ['triggers' => $all['triggers']],
                'conditions' => ['conditions' => $all['conditions']],
                'actions' => ['actions' => $all['actions']],
                default => $all,
            };
        }

        // Filter by category if specified
        if ($category = $request->input('category')) {
            foreach ($all as $type => $bricks) {
                $all[$type] = array_filter($bricks, fn ($b) => $b['category'] === $category);
            }
        }

        return response()->json([
            'data' => $all,
            'meta' => [
                'triggers_count' => count($this->registry->getTriggers()),
                'conditions_count' => count($this->registry->getConditions()),
                'actions_count' => count($this->registry->getActions()),
            ],
        ]);
    }

    public function schema(string $brick): JsonResponse
    {
        if (! class_exists($brick)) {
            abort(404, 'Brick not found');
        }

        $instance = $this->registry->resolve($brick);

        return response()->json([
            'data' => $instance->toArray(),
        ]);
    }

    public function categories(): JsonResponse
    {
        $all = $this->registry->all();
        $categories = [];

        foreach ($all as $type => $bricks) {
            foreach ($bricks as $brick) {
                $cat = $brick['category'];
                if (! isset($categories[$cat])) {
                    $categories[$cat] = ['triggers' => 0, 'conditions' => 0, 'actions' => 0];
                }
                $categories[$cat][$type]++;
            }
        }

        return response()->json([
            'data' => $categories,
        ]);
    }
}
