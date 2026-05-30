<?php

namespace App\Http\Controllers;

use App\Http\Resources\IssueResource;
use App\Models\Issue;
use App\Support\InputSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class IssueController extends Controller
{
    public function index(Request $request)
    {
        $issues = $request->user()->issues()->latest()->paginate(15);

        return IssueResource::collection($issues);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'category' => ['nullable', Rule::in(['general', 'bug', 'billing', 'security', 'support'])],
        ]);

        $issue = $request->user()->issues()->create([
            'title' => InputSanitizer::text($validated['title']),
            'description' => InputSanitizer::multilineText($validated['description']),
            'priority' => $validated['priority'] ?? 'medium',
            'category' => $validated['category'] ?? 'general',
            'metadata' => [
                'source' => 'dashboard',
            ],
        ]);

        Log::channel('support')->info('Issue created.', [
            'user_id' => $request->user()->id,
            'issue_id' => $issue->id,
            'category' => $issue->category,
            'priority' => $issue->priority,
        ]);

        return (new IssueResource($issue))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Issue $issue)
    {
        $this->ensureOwnedByUser($request, $issue);

        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['open', 'in_progress', 'resolved', 'closed'])],
            'priority' => ['sometimes', Rule::in(['low', 'medium', 'high', 'urgent'])],
        ]);

        if (array_key_exists('status', $validated)) {
            $issue->status = $validated['status'];
            $issue->resolved_at = in_array($validated['status'], ['resolved', 'closed'], true) ? now() : null;
        }

        if (array_key_exists('priority', $validated)) {
            $issue->priority = $validated['priority'];
        }

        $issue->save();

        return new IssueResource($issue);
    }

    public function destroy(Request $request, Issue $issue)
    {
        $this->ensureOwnedByUser($request, $issue);
        $issue->delete();

        return response()->json(['message' => 'Issue deleted.']);
    }
}
