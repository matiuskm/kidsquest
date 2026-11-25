<?php

namespace App\Http\Controllers;

use App\Models\ExamResult;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    public function __invoke(Submission $submission)
    {
        return $this->serve($submission);
    }

    public function exam(ExamResult $exam)
    {
        return $this->serve($exam);
    }

    private function serve(Model $model)
    {
        Gate::authorize('view', $model);

        abort_unless($model->photo_path && Storage::disk('local')->exists($model->photo_path), 404);

        return Storage::disk('local')->response($model->photo_path, null, ['Cache-Control' => 'private, max-age=86400']);
    }
}
