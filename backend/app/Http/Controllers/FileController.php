<?php

namespace App\Http\Controllers;

use App\Models\ProjectFile;
use App\Support\InputSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FileController extends Controller
{
    /**
     * List files for the authenticated user, optionally filtered by project.
     */
    public function index(Request $request)
    {
        $request->validate([
            'project_id' => [
                'nullable',
                Rule::exists('projects', 'id')->where(
                    fn ($query) => $query->where('user_id', $request->user()->id)
                ),
            ],
        ]);

        $query = $request->user()->projectFiles()->with('project');

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        return response()->json($query->latest()->get());
    }

    /**
     * Upload one or more files.
     * Supports local disk by default; swap $disk to 's3' or 'r2' when configured.
     */
    public function store(Request $request)
    {
        $request->validate([
            'files'      => 'required|array|min:1|max:5',
            'files.*'    => 'required|file|mimetypes:image/jpeg,image/png,image/webp,application/pdf,text/plain,text/csv,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/msword,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel|extensions:jpg,jpeg,png,webp,pdf,txt,csv,doc,docx,xls,xlsx|max:20480',
            'project_id' => [
                'nullable',
                Rule::exists('projects', 'id')->where(
                    fn ($query) => $query->where('user_id', $request->user()->id)
                ),
            ],
        ]);

        $disk    = config('filesystems.default', 'local');
        $records = [];

        foreach ($request->file('files') as $file) {
            $originalName = InputSanitizer::fileName($file->getClientOriginalName());
            $extension = $file->guessExtension() ?: $file->getClientOriginalExtension();
            $storedName   = Str::uuid() . '.' . strtolower((string) $extension);
            $path         = $file->storeAs('uploads', $storedName, $disk);

            $record = $request->user()->projectFiles()->create([
                'project_id'    => $request->project_id ?? null,
                'original_name' => $originalName,
                'stored_name'   => $storedName,
                'disk'          => $disk,
                'path'          => $path,
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
            ]);

            $records[] = $record;
        }

        return response()->json($records, 201);
    }

    /**
     * Download / stream a file.
     */
    public function download(Request $request, ProjectFile $projectFile)
    {
        $this->ensureOwnedByUser($request, $projectFile);

        if (! Storage::disk($projectFile->disk)->exists($projectFile->path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return Storage::disk($projectFile->disk)->download($projectFile->path, $projectFile->original_name);
    }

    /**
     * Delete a file record and the underlying stored file.
     */
    public function destroy(Request $request, ProjectFile $projectFile)
    {
        $this->ensureOwnedByUser($request, $projectFile);

        Storage::disk($projectFile->disk)->delete($projectFile->path);
        $projectFile->delete();

        return response()->json(['message' => 'File deleted']);
    }
}
