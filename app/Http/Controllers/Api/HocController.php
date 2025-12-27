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
use App\Models\StudyYear;
use App\Models\DepartmentLoad;
use App\Models\TeacherLoad;
use Illuminate\Support\Facades\Validator;

class HalfYear
{
    public $name;
    public $value;

    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }
}
class HocController extends Controller
{
    public function show($id)
    {
        $department = User::find($id)->department;
        // return response()->json();
        $department_loads = $department->load(['department_load.subject', 'department_load.group'])->department_load;
        foreach ($department_loads as $load) {
            $load->computed_attributes = [
                'difference_hours' => $load->getDifferenceBetweenDepartmentAndTeachersHoursAttribute(),
            ];
        }
        $teachers = $department->load(['users'])->users;

        // Функция для получения данных о нагрузке преподавателя
        function getTeacherLoadData($teacherId, $study_year_id, $half_year_value, $type_id, $subtype_id)
        {
            return TeacherLoad::where('study_year_id', $study_year_id)
                ->where('half_year', $half_year_value)
                ->where('type_id', $type_id)
                ->where('subtype_id', $subtype_id)
                ->where('teacher_id', $teacherId) // фильтрация по преподавателю
                ->with(['departmentLoad.group', 'departmentLoad.subject'])
                ->get()
                ->map(function ($teacher_load) {
                    return [
                        'id' => ['value' => $teacher_load->id, 'label' => ['editSidebar']],
                        'name' => ['value' => "[" . $teacher_load->departmentLoad->group->name . '] ' . $teacher_load->departmentLoad->subject->name, 'label' => ['table, editSidebar']],
                        'hours_allocated' => $teacher_load->hours_allocated,
                        'hours_per_week' => $teacher_load->hours_per_week,
                        'department_load' => $teacher_load->departmentLoad,
                        'max_hours' => $teacher_load->departmentLoad->getDifferenceBetweenDepartmentAndTeachersHoursAttribute() + $teacher_load->hours_allocated,
                    ];
                })->values()->all();
        }

        // Функция для получения данных о подтипах
        function getSubtypeData($teacherId, $type_id, $study_year_id, $half_year_value)
        {
            return Subtype::all()->map(function ($subType) use ($teacherId, $type_id, $study_year_id, $half_year_value) {
                $teacherLoads = getTeacherLoadData($teacherId, $study_year_id, $half_year_value, $type_id, $subType->id);
                $totalHours = collect($teacherLoads)->sum('hours_allocated');
                $totalHoursPerWeek = collect($teacherLoads)->sum('hours_per_week');

                return [
                    'id' => ['value' => $subType->id, 'label' => ['editSidebar']],
                    'name' => ['value' => $subType->name, 'label' => ['table', 'editSidebar']],
                    'items' => ['value' => $teacherLoads, 'label' => ['table', 'editSidebar']],
                    'total_hours' => $totalHours,
                    'total_hours_per_week' => round($totalHoursPerWeek, 1),
                ];
            })->values()->all();
        }

        // Функция для получения данных о типах
        function getTypeData($teacherId, $study_year_id, $half_year_value)
        {
            return Type::all()->map(function ($type) use ($teacherId, $study_year_id, $half_year_value) {
                $subtypes = getSubtypeData($teacherId, $type->id, $study_year_id, $half_year_value);
                $totalHours = collect($subtypes)->sum('total_hours');
                $totalHoursPerWeek = collect($subtypes)->sum('total_hours_per_week');

                return [
                    'id' => ['value' => $type->id, 'label' => ['editSidebar']],
                    'name' => ['value' => $type->name, 'label' => ['table', 'editSidebar']],
                    'subtypes' => $subtypes,
                    'total_hours' => $totalHours,
                    'total_hours_per_week' => round($totalHoursPerWeek, 1),
                ];
            })->values()->all();
        }

        // Функция для получения данных о годах обучения с учетом полугодий
        function getStudyYearData($teacherId)
        {
            $halfYears = collect([
                new HalfYear('Первое полугодие', 1),
                new HalfYear('Второе полугодие', 2)
            ]);

            return StudyYear::all()->map(function ($study_year) use ($halfYears, $teacherId) {
                $halfYearData = $halfYears->map(function ($half_year) use ($study_year, $teacherId) {
                    $types = getTypeData($teacherId, $study_year->id, $half_year->value);
                    $totalHours = collect($types)->sum('total_hours');
                    $totalHoursPerWeek = collect($types)->sum('total_hours_per_week');

                    return [
                        'name' => ['value' => $half_year->name],
                        'value' => ['value' => $half_year->value],
                        'types' => $types,
                        'total_hours' => $totalHours,
                        'total_hours_per_week' => round($totalHoursPerWeek, 1),
                    ];
                })->values()->all();

                $totalHours = collect($halfYearData)->sum('total_hours');
                $totalHoursPerWeek = collect($halfYearData)->sum('total_hours_per_week');

                return [
                    'id' => ['value' => $study_year->id, 'label' => ['editSidebar']],
                    'name' => ['value' => "$study_year->start_year-$study_year->end_year", 'label' => ['table']],
                    'start_year' => ['value' => $study_year->start_year, 'label' => ['editSidebar']],
                    'end_year' => ['value' => $study_year->end_year, 'label' => ['editSidebar']],
                    'half_years' => $halfYearData,
                    'total_hours' => $totalHours,
                    'total_hours_per_week' => round($totalHoursPerWeek, 1),
                ];
            })->values()->all();
        }

        // Форматирование данных о преподавателях
        $formattedData = $teachers->map(function ($teacher) {
            return [
                'id' => ['value' => $teacher->id, 'label' => ['editSidebar']],
                'login' => ['value' => $teacher->login, 'label' => ['table', 'editSidebar']],
                'email' => ['value' => $teacher->email, 'label' => ['table', 'editSidebar']],
                'firstname' => ['value' => $teacher->firstname, 'label' => ['table', 'editSidebar']],
                'lastname' => ['value' => $teacher->lastname, 'label' => ['table', 'editSidebar']],
                'middlename' => ['value' => $teacher->middlename, 'label' => ['table', 'editSidebar']],
                'roles' => ['value' => $teacher->roles, 'label' => ['table', 'editSidebar']],
                'teacher_statuses' => ['value' => $teacher->teacherStatuses, 'label' => ['table', 'editSidebar']],
                'study_years' => getStudyYearData($teacher->id),
            ];
        })->values()->all();

        return response()->json([
            'users' => $formattedData,
            'department_loads' => $department_loads,
        ]);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'teacher_id' => 'required|exists:users,id',
            'hours_allocated' => 'required|integer',
            'study_year_id' => 'required|exists:study_years,id',
            'half_year' => 'required|integer|in:1,2', // Либо 1, либо 2
            'department_load_id' => 'required|exists:department_loads,id',
            'type_id' => 'required|exists:types,id',
            'subtype_id' => 'required|exists:subtypes,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $teacherLoad = TeacherLoad::create($request->all());

        $teacherLoad->load(['departmentLoad.group', 'departmentLoad.subject']);
        return response()->json([
            'id' => ['value' => $teacherLoad->id, 'label' => ['editSidebar']],
            'name' => ['value' => "[" . $teacherLoad->departmentLoad->group->name. '] ' . $teacherLoad->departmentLoad->subject->name, 'label' => ['table, editSidebar']],
            'hours_allocated' => $teacherLoad->hours_allocated,
            'hours_per_week' => $teacherLoad->hours_per_week,
            'department_load' => $teacherLoad->departmentLoad,
            'max_hours' => $teacherLoad->departmentLoad->getDifferenceBetweenDepartmentAndTeachersHoursAttribute() + $teacherLoad->hours_allocated,
        ], 201);
    }
    public function update(Request $request, $id)
    {
        $teacherLoad = TeacherLoad::find($id);

        if (!$teacherLoad) {
            return response()->json(['error' => 'Teacher Load not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            // 'teacher_id' => 'required|exists:users,id',
            'hours_allocated' => 'required|integer',
            // 'study_year_id' => 'required|exists:study_years,id',
            // 'half_year' => 'required|integer|in:1,2',  Либо 1, либо 2
            'department_load_id' => 'required|exists:department_loads,id',
            // 'type_id' => 'required|exists:types,id',
            // 'subtype_id' => 'required|exists:subtypes,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $teacherLoad->update($request->all());
        $teacherLoad->load(['departmentLoad.group', 'departmentLoad.subject']);
        return response()->json([
            'id' => ['value' => $teacherLoad->id, 'label' => ['editSidebar']],
            'name' => ['value' => "[" . $teacherLoad->departmentLoad->group->name. '] ' . $teacherLoad->departmentLoad->subject->name, 'label' => ['table, editSidebar']],
            'hours_allocated' => $teacherLoad->hours_allocated,
            'hours_per_week' => $teacherLoad->hours_per_week,
            'department_load' => $teacherLoad->departmentLoad,
            'max_hours' => $teacherLoad->departmentLoad->getDifferenceBetweenDepartmentAndTeachersHoursAttribute() + $teacherLoad->hours_allocated,
        ]);
    }

    public function destroy($id)
    {
        $teacherLoad = TeacherLoad::find($id);

        if (!$teacherLoad) {
            return response()->json(['error' => 'Teacher Load not found'], 404);
        }

        $teacherLoad->delete();

        return response()->json(200);
    }
}
