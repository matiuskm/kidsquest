<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    public function __invoke(Submission $submission)
    {
        Gate::authorize('view', $submission);

        abort_unless($submission->photo_path && Storage::disk('local')->exists($submission->photo_path), 404);

        return Storage::disk('local')->response($submission->photo_path, null, ['Cache-Control' => 'private, max-age=86400']);
    }
}
