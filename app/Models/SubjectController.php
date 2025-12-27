<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::all();
        $formattedSubjects = $subjects->map(function ($subject) {
            return [
                'id' => ['value' => $subject->id, 'label' => ['editSidebar']],
                'name' => ['value' => $subject->name, 'label' => ['table','editSidebar']],
            ];
        });
        return response()->json($formattedSubjects);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:subjects',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $subject = Subject::create($request->all());

        return response()->json($subject, 201);
    }

    public function show($id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json(['error' => 'Subject not found'], 404);
        }

        return response()->json($subject);
    }

    public function update(Request $request, $id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json(['error' => 'Subject not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:subjects,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $subject->update($request->all());

        return response()->json([
            'id' => ['value' => $subject->id, 'label' => ['editSidebar']],
            'name' => ['value' => $subject->name, 'label' => ['table','editSidebar']],
        ]);
    }

    public function destroy($id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json(['error' => 'Subject not found'], 404);
        }

        $subject->delete();

        return response()->json(200);
    }
}
