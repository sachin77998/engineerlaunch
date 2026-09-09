<?php

namespace App\Http\Controllers;

use App\Services\CodeExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PracticeController extends Controller
{
    public function index(): View
    {
        return view('practice-modular');
    }

    public function run(Request $request, CodeExecutionService $executor): JsonResponse
    {
        $data = $request->validate([
            'language' => ['required', Rule::in(array_keys(config('services.judge0.languages', [])))],
            'source' => ['required', 'string', 'max:50000'],
            'stdin' => ['nullable', 'string', 'max:10000'],
        ]);

        try {
            return response()->json($executor->execute($data['language'], $data['source'], $data['stdin'] ?? null));
        } catch (\Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => 'The code runner is temporarily unavailable. Please try again shortly.',
            ], 503);
        }
    }
}
