<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {

        $departments = Department::with('user')->get();


        $result = [];
        foreach ($departments as $department) {

            if ($department->user) {
                $user = $department->user;
                $fullName = $user->firstname . ' ' . $user->middlename . ' ' . $user->lastname;
                $result[] = [
                    'id' => $department->id,
                    'department' => $department->name,
                    'user' => $fullName,
                ];
            }
        }
        $formattedDepartments = $departments->map(function ($department) {
            return [
                'id' => ['value' => $department->id, 'label' => ['editSidebar']],
                'titleLeft' => $department->name,
                'titleRight' => $department->totalHours(),
                'name' => ['value' => $department->name, 'label' => ['table','editSidebar']],
                'user' => ['value' => $department->user, 'label' => ['table','editSidebar']],

                'title' =>  $department->name,
                'href' =>  "/assistantDirector/" . $department->id,
            ];
        });
        return response()->json($formattedDepartments);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'user_id' => [
                'nullable',
                'integer',
                'unique:departments,user_id',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $department = Department::create([
            'name' => $request->name,
            'user_id' => $request->user_id,
        ]);

        return response()->json([
            'id' => ['value' => $department->id, 'label' => ['editSidebar']],
            'name' => ['value' => $department->name, 'label' => ['table','editSidebar']],
            'user' => ['value' => $department->user, 'label' => ['table','editSidebar']],
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $department = Department::with('users')->find($id);

        if (!$department) {
            return response()->json(['error' => 'Department not found'], 404);
        }

        return response()->json($department);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Department $department)
    {
        if (!$department) {
            return response()->json(['error' => 'Department not found'], 404);
        }
        // dd($department->user_id, $request->user_id);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'user_id' => [
                'nullable',
                'integer',
                Rule::unique('departments')->ignore($department->id),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $department->update($request->only([
            'name',
            'user_id',
        ]));

        return response()->json([
            'id' => ['value' => $department->id, 'label' => ['editSidebar']],
            'name' => ['value' => $department->name, 'label' => ['table','editSidebar']],
            'user' => ['value' => $department->user, 'label' => ['table','editSidebar']],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $department = Department::find($id);

        if (!$department) {
            return response()->json(['error' => 'Department not found'], 404);
        }

        $department->delete();

        return response()->json(200);
    }
}
