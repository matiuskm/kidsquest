<?php

namespace App\Http\Controllers\Child;

use App\Exceptions\AlreadySubmitted;
use App\Exceptions\InvalidImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExamResultRequest;
use App\Models\ExamResult;
use App\Services\ExamService;

class ExamController extends Controller
{
    public function index()
    {
        return view('child.exams', ['exams' => ExamResult::where('child_id', auth()->id())->latest('id')->limit(30)->get()]);
    }

    public function store(StoreExamResultRequest $request, ExamService $exams)
    {
        try {
            $exams->submit($request->user(), $request->string('subject')->trim()->toString(), $request->filled('score') ? $request->integer('score') : null, $request->input('letter'), $request->file('photo'));
        } catch (AlreadySubmitted|InvalidImage $e) {
            return back()->withErrors(['photo' => $e->getMessage()])->with('sound', 'reject');
        }

        return redirect()->route('child.exams')->with('status', 'Nilai terkirim! Tunggu ortu periksa ya ✨')->with('sound', 'submit');
    }
}
