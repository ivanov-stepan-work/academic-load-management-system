<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeacherLoad;
use App\Models\Group;
use App\Models\Type;
use App\Models\Subtype;
use App\Models\Subject;
use App\Models\Department;
use App\Models\User;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeacherLoadController extends Controller
{
    public function index()
    {
        $userId = 14;
        $department = User::find($userId)->department;
        $department->load(['users']);
        $teacher_loads = TeacherLoad::with([
            'teacher',
            'type',
            'subtype',
            'departmentLoad.group',
            'departmentLoad.subject',
            'departmentLoad.department',
        ])->get();
        $groups = Group::all();
        $types = Type::all();
        $subtypes = Subtype::all();
        $subjects = Subject::all();

        $data = [
            'department_teachers' => $department->users,
            'teacher_loads' => $teacher_loads,
            'groups' => $groups,
            'types' => $types,
            'subtypes' => $subtypes,
            'subjects' => $subjects,
        ];
        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'teacher_id' => 'required|exists:users,id',
            'hours_allocated' => 'required|integer',
            'department_id' => 'required|exists:departments,id',
            'type_id' => 'required|exists:types,id',
            'subtype_id' => 'required|exists:subtypes,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $teacherLoad = TeacherLoad::create($request->all());

        return response()->json($teacherLoad, 201);
    }

    public function show($id)
    {
        $teacherLoad = TeacherLoad::find($id);

        if (!$teacherLoad) {
            return response()->json(['error' => 'Teacher Load not found'], 404);
        }

        return response()->json($teacherLoad);
    }

    public function update(Request $request, $id)
    {
        $teacherLoad = TeacherLoad::find($id);

        if (!$teacherLoad) {
            return response()->json(['error' => 'Teacher Load not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'teacher_id' => 'required|exists:users,id',
            'hours_allocated' => 'required|integer',
            'department_id' => 'required|exists:departments,id',
            'type_id' => 'required|exists:types,id',
            'subtype_id' => 'required|exists:subtypes,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $teacherLoad->update($request->all());

        return response()->json($teacherLoad);
    }

    public function destroy($id)
    {
        $teacherLoad = TeacherLoad::find($id);

        if (!$teacherLoad) {
            return response()->json(['error' => 'Teacher Load not found'], 404);
        }

        $teacherLoad->delete();

        return response()->json(null, 204);
    }
}
