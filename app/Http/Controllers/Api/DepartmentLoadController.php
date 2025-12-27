<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DepartmentLoad;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentLoadController extends Controller
{
    public function index()
    {
        $department_loads = DepartmentLoad::with('group', 'department', 'subject')->get();
        return response()->json($department_loads);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject_id' => 'required|exists:subjects,id',
            'semester' => 'required|int',
            'hours_allocated' => 'required|integer',
            'group_id' => 'required|exists:groups,id',
            'department_id' => 'required|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $departmentLoad = DepartmentLoad::create($request->all());

        return response()->json($departmentLoad, 201);
    }

    public function show($id)
    {
        $department = Department::find($id);
        $department_loads = $department->department_load;
        if (!$department_loads) {
            return response()->json(['error' => 'Department Loads not found'], 404);
        }
        $department_loads->load(['group', 'subject', 'department']);
        return response()->json($department_loads);
    }

    public function update(Request $request, DepartmentLoad $departmentLoad)
    {
        if (!$departmentLoad) {
            return response()->json(['error' => 'Department Load not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'subject_id' => 'required|exists:subjects,id',
            'semester' => 'required|int',
            'hours_allocated' => 'required|integer',
            'group_id' => 'required|exists:groups,id',
            'department_id' => 'required|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $departmentLoad->update($request->all());

        $departmentLoad->load(['group', 'subject', 'department']);

        return response()->json($departmentLoad);
    }

    public function destroy($id)
    {
        $departmentLoad = DepartmentLoad::find($id);

        if (!$departmentLoad) {
            return response()->json(['error' => 'Department Load not found'], 404);
        }

        $departmentLoad->delete();

        return response()->json(200);
    }
}
