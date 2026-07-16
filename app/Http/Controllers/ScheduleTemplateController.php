<?php

namespace App\Http\Controllers;

use App\Models\ScheduleTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ScheduleTemplateController extends Controller
{
    public function index()
    {
        $templates = ScheduleTemplate::orderBy('is_system', 'desc')->orderBy('name')->get();

        return view('schedule-templates.index', compact('templates'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'cron' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
        $data['slug'] = Str::slug($data['name']) . '-' . Str::lower(Str::random(4));

        ScheduleTemplate::create($data);

        return redirect()->route('schedule-templates.index')->with('status', "Template \"{$data['name']}\" created.");
    }

    public function destroy(ScheduleTemplate $scheduleTemplate)
    {
        if ($scheduleTemplate->is_system) {
            return back()->with('status', 'System templates cannot be deleted.');
        }
        $scheduleTemplate->delete();

        return redirect()->route('schedule-templates.index')->with('status', 'Template deleted.');
    }
}
