<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduleTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ScheduleTemplateController extends Controller
{
    public function index(Request $request)
    {
        return ScheduleTemplate::query()
            ->latest()
            ->paginate(50);
    }

    public function store(Request $request)
    {
        $data = $this->validateScheduleTemplate($request);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return response()->json(ScheduleTemplate::create($data), 201);
    }

    public function show(ScheduleTemplate $scheduleTemplate)
    {
        return $scheduleTemplate;
    }

    public function update(Request $request, ScheduleTemplate $scheduleTemplate)
    {
        $scheduleTemplate->update($this->validateScheduleTemplate($request, updating: true));

        return $scheduleTemplate;
    }

    public function destroy(ScheduleTemplate $scheduleTemplate)
    {
        if ($scheduleTemplate->is_system) {
            return response()->json(['message' => 'System templates cannot be deleted.'], 422);
        }

        $scheduleTemplate->delete();

        return response()->noContent();
    }

    private function validateScheduleTemplate(Request $request, bool $updating = false): array
    {
        $req = $updating ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$req, 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140'],
            'cron' => [$req, 'string', 'max:120'],
            'description' => ['nullable', 'string'],
        ]);
    }
}
