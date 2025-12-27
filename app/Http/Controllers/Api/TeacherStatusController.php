<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeacherStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeacherStatusController extends Controller
{
    public function index()
    {
        $teacher_statuses = TeacherStatus::all();
        $formattedTeacherStatuses = $teacher_statuses->map(function ($teacher_status) {
            return [
                'id' => ['value' => $teacher_status->id, 'label' => ['editSidebar']],
                'name' => ['value' => $teacher_status->name, 'label' => ['table','editSidebar']],
            ];
        });
        return response()->json($formattedTeacherStatuses);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:teacher_statuses',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $teacherStatus = TeacherStatus::create($request->all());

        return response()->json($teacherStatus, 201);
    }

    public function show($id)
    {
        $teacherStatus = TeacherStatus::find($id);

        if (!$teacherStatus) {
            return response()->json(['error' => 'Teacher status not found'], 404);
        }

        return response()->json($teacherStatus);
    }

    public function update(Request $request, $id)
    {
        $teacherStatus = TeacherStatus::find($id);

        if (!$teacherStatus) {
            return response()->json(['error' => 'Teacher status not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:teacher_statuses,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $teacherStatus->update($request->all());

        return response()->json([
            'id' => ['value' => $teacherStatus->id, 'label' => ['editSidebar']],
            'name' => ['value' => $teacherStatus->name, 'label' => ['table','editSidebar']],
        ]);
    }

    public function destroy($id)
    {
        $teacherStatus = TeacherStatus::find($id);

        if (!$teacherStatus) {
            return response()->json(['error' => 'Teacher status not found'], 404);
        }

        $teacherStatus->delete();

        return response()->json(200);
    }
}
