<?php

namespace App\Http\Controllers\Parents;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Models\Task;
use App\Models\User;

class TaskController extends Controller
{
    public function index()
    {
        return view('parent.tasks.index', ['tasks' => Task::with('children')->latest()->get()]);
    }

    public function create()
    {
        return view('parent.tasks.form', $this->formData(new Task(['points' => 10, 'recurrence' => 'daily', 'active' => true])));
    }

    public function store(TaskRequest $request)
    {
        Task::create($request->taskData())->children()->sync($request->childIds());

        return redirect()->route('parent.tasks.index')->with('status', 'Tugas ditambahkan.');
    }

    public function edit(Task $task)
    {
        return view('parent.tasks.form', $this->formData($task));
    }

    public function update(TaskRequest $request, Task $task)
    {
        $task->update($request->taskData());
        $task->children()->sync($request->childIds());

        return redirect()->route('parent.tasks.index')->with('status', 'Tugas disimpan.');
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return redirect()->route('parent.tasks.index')->with('status', 'Tugas dihapus.');
    }

    private function formData(Task $task): array
    {
        return [
            'task' => $task,
            'children' => User::children()->orderBy('name')->get(),
            'selected' => old('children', $task->exists ? $task->children()->pluck('users.id')->all() : []),
        ];
    }
}
