<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Department;
use App\Models\Group;
use App\Models\Role;
use App\Models\Specialty;
use App\Models\Subject;
use App\Models\Subtype;
use App\Models\Type;
use App\Models\TeacherStatus;
use App\Models\DepartmentLoad;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AssistantDirectorController extends Controller
{
    public function index()
    {


    }
    public function show($id)
    {

        // Retrieve and format departments
        $formattedDepartments = Department::with('department_load.subject', 'department_load.group')
            ->get()
            ->map(function ($department) use ($id) {
                return [
                    'id' => ['value' => $department->id, 'label' => 'editSidebar'],
                    'titleLeft' => $department->name,
                    'titleRight' => $department->totalHours($id), // Передаем $id в качестве $userId
                    'name' => ['value' => $department->name, 'label' => ['table', 'editSidebar']],
                    'user' => ['value' => $department->user, 'label' => ['table', 'editSidebar']],
                    'department_loads' => ['value' => $department->department_load->where('user_id', $id)->toArray(), 'label' => ['table', 'editSidebar']],
                ];

            });

        // Retrieve and format groups
        $formattedGroups = Group::with('specialty')
            ->get()
            ->map(function ($group) {
                return [
                    'id' => ['value' => $group->id, 'label' => 'editSidebar'],
                    'name' => ['value' => $group->name, 'label' => ['table', 'editSidebar']],
                    'student_count' => ['value' => $group->student_count, 'label' => ['table', 'editSidebar']],
                    'specialty' => ['value' => $group->specialty, 'label' => ['table', 'editSidebar']],
                ];
            });

        // Retrieve and format subjects
        $formattedSubjects = Subject::all()
            ->map(function ($subject) {
                return [
                    'id' => ['value' => $subject->id, 'label' => 'editSidebar'],
                    'name' => ['value' => $subject->name, 'label' => ['table', 'editSidebar']],
                ];
            });

        return response()->json([
            'departments' => $formattedDepartments,
            'groups' => $formattedGroups,
            'subjects' => $formattedSubjects,
        ]);
    }

    public function store(Request $request)
    {
        // Получаем id текущего пользователя

        // Добавляем 'user_id' к правилам валидации, если это необходимо
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

        $requestData = $request->all();
        // dd($requestData);
        $departmentLoad = DepartmentLoad::create($requestData);

        // Загружаем связанные модели для возврата в ответе
        $departmentLoad->load(['group', 'subject', 'department']);

        // Возвращаем успешный ответ с созданной записью
        return response()->json($departmentLoad, 201);
    }
    public function update(Request $request, $id)
    {
        $departmentLoad = DepartmentLoad::find($id);
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
