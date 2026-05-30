<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\Task;
use App\Support\InputSanitizer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = $request->user()
            ->projects()
            ->with(['client', 'tasks'])
            ->latest()
            ->paginate(20);

        return ProjectResource::collection($projects);
    }

    public function store(StoreProjectRequest $request)
    {
        $validated = $request->validated();

        if (isset($validated['contract_id'])) {
            $contract = $request->user()->contracts()->findOrFail($validated['contract_id']);

            if ((int) $contract->client_id !== (int) $validated['client_id']) {
                throw ValidationException::withMessages([
                    'contract_id' => 'The selected contract does not belong to the selected client.',
                ]);
            }
        }

        $project = $request->user()->projects()->create($validated);

        return (new ProjectResource($project->load(['client', 'tasks'])))->response()->setStatusCode(201);
    }

    public function show(Request $request, Project $project)
    {
        $this->ensureOwnedByUser($request, $project);

        return new ProjectResource($project->load(['client', 'contract', 'tasks', 'files']));
    }

    public function update(Request $request, Project $project)
    {
        $this->ensureOwnedByUser($request, $project);

        $validated = $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'status'      => 'sometimes|required|in:Planning,Active,On Hold,Completed,Cancelled',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date',
            'budget'      => 'nullable|numeric|min:0',
        ]);

        $project->update([
            'title' => array_key_exists('title', $validated) ? InputSanitizer::text($validated['title']) : $project->title,
            'description' => array_key_exists('description', $validated)
                ? InputSanitizer::multilineText($validated['description'])
                : $project->description,
            'status' => $validated['status'] ?? $project->status,
            'start_date' => array_key_exists('start_date', $validated) ? $validated['start_date'] : $project->start_date,
            'end_date' => array_key_exists('end_date', $validated) ? $validated['end_date'] : $project->end_date,
            'budget' => array_key_exists('budget', $validated) ? $validated['budget'] : $project->budget,
        ]);

        return new ProjectResource($project->load(['client', 'tasks']));
    }

    public function destroy(Request $request, Project $project)
    {
        $this->ensureOwnedByUser($request, $project);

        $project->delete();

        return response()->json(['message' => 'Project deleted']);
    }

    // ── Tasks ──────────────────────────────────────────────────────────────

    public function tasks(Request $request, Project $project)
    {
        $this->ensureOwnedByUser($request, $project);

        return response()->json($project->tasks()->orderBy('position')->get());
    }

    public function storeTask(Request $request, Project $project)
    {
        $this->ensureOwnedByUser($request, $project);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'status'      => ['nullable', Rule::in(['Todo', 'In Progress', 'In Review', 'Done'])],
            'priority'    => ['nullable', Rule::in(['Low', 'Medium', 'High'])],
            'due_date'    => 'nullable|date',
        ]);

        // Place new task at the end of its column
        $maxPosition = $project->tasks()
            ->where('status', $validated['status'] ?? 'Todo')
            ->max('position') ?? -1;

        $task = $project->tasks()->create(array_merge($validated, [
            'title' => InputSanitizer::text($validated['title']),
            'description' => InputSanitizer::multilineText($validated['description'] ?? null),
            'position' => $maxPosition + 1,
        ]));

        return response()->json($task, 201);
    }

    public function updateTask(Request $request, Project $project, Task $task)
    {
        $this->ensureOwnedByUser($request, $project);
        $this->ensureBelongsTo($task, 'project_id', $project);

        $validated = $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'status'      => ['sometimes', 'required', Rule::in(['Todo', 'In Progress', 'In Review', 'Done'])],
            'priority'    => ['sometimes', 'required', Rule::in(['Low', 'Medium', 'High'])],
            'due_date'    => 'nullable|date',
            'position'    => 'sometimes|required|integer|min:0',
        ]);

        $task->update([
            'title' => array_key_exists('title', $validated) ? InputSanitizer::text($validated['title']) : $task->title,
            'description' => array_key_exists('description', $validated)
                ? InputSanitizer::multilineText($validated['description'])
                : $task->description,
            'status' => $validated['status'] ?? $task->status,
            'priority' => $validated['priority'] ?? $task->priority,
            'due_date' => array_key_exists('due_date', $validated) ? $validated['due_date'] : $task->due_date,
            'position' => array_key_exists('position', $validated) ? $validated['position'] : $task->position,
        ]);

        return response()->json($task);
    }

    public function destroyTask(Request $request, Project $project, Task $task)
    {
        $this->ensureOwnedByUser($request, $project);
        $this->ensureBelongsTo($task, 'project_id', $project);

        $task->delete();

        return response()->json(['message' => 'Task deleted']);
    }

    /**
     * Bulk reorder tasks (Kanban drag-and-drop).
     * Expects: { tasks: [{ id, status, position }] }
     */
    public function reorderTasks(Request $request, Project $project)
    {
        $this->ensureOwnedByUser($request, $project);

        $request->validate([
            'tasks'          => 'required|array',
            'tasks.*.id'     => 'required|integer',
            'tasks.*.status' => ['required', Rule::in(['Todo', 'In Progress', 'In Review', 'Done'])],
            'tasks.*.position' => 'required|integer|min:0',
        ]);

        $taskIds = collect($request->input('tasks'))->pluck('id')->unique()->values();

        if ($project->tasks()->whereIn('id', $taskIds)->count() !== $taskIds->count()) {
            throw ValidationException::withMessages([
                'tasks' => 'One or more tasks do not belong to this project.',
            ]);
        }

        foreach ($request->tasks as $item) {
            $project->tasks()->where('id', $item['id'])->update([
                'status'   => $item['status'],
                'position' => $item['position'],
            ]);
        }

        return response()->json(['message' => 'Tasks reordered']);
    }
}
