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
use App\Models\StudyYear;

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
class TeacherController extends Controller
{
    public function show($id)
    {
        $userId = $id;

        $data = StudyYear::all()->map(function ($study_year) use ($userId) {
            $halfYears = collect([
                new HalfYear('Первое полугодие', 1),
                new HalfYear('Второе полугодие', 2)
            ]);

            return [
                'id' => $study_year->id,
                'name' => $study_year->start_year . '-' . $study_year->end_year,
                'items' => $halfYears->map(function ($halfYear) use ($study_year, $userId) {
                    $types = Type::all()->map(function ($type) use ($study_year, $halfYear, $userId) {
                        $subtypes = Subtype::all()->map(function ($subType) use ($study_year, $halfYear, $type, $userId) {
                            $teacher_loads = TeacherLoad::where('study_year_id', $study_year->id)
                                ->where('half_year', $halfYear->value)
                                ->where('type_id', $type->id)
                                ->where('subtype_id', $subType->id)
                                ->where('teacher_id', $userId)
                                ->with(['departmentLoad.group', 'departmentLoad.subject'])
                                ->get()
                                ->map(function ($teacher_load) {
                                    return [
                                        'id' => $teacher_load->id,
                                        'hours_allocated' => $teacher_load->hours_allocated,
                                        'hours_per_week' => $teacher_load->hours_per_week,
                                        'department_load' => $teacher_load->departmentLoad,
                                    ];
                                });

                            $totalHours = $teacher_loads->sum('hours_allocated');
                            $totalHoursPerWeek = $teacher_loads->sum('hours_per_week');

                            return [
                                'id' => $subType->id,
                                'name' => $subType->name,
                                'teacher_loads' => $teacher_loads,
                                'total_hours' => $totalHours,
                                'total_hours_per_week' => round($totalHoursPerWeek, 1),
                            ];
                        });

                        $totalHours = $subtypes->sum('total_hours');
                        $totalHoursPerWeek = $subtypes->sum('total_hours_per_week');

                        return [
                            'id' => $type->id,
                            'name' => $type->name,
                            'subtypes' => $subtypes,
                            'total_hours' => $totalHours,
                            'total_hours_per_week' => round($totalHoursPerWeek, 1),
                        ];
                    });

                    $totalHours = $types->sum('total_hours');
                    $totalHoursPerWeek = $types->sum('total_hours_per_week');

                    return [
                        'name' => $halfYear->name,
                        'value' => $halfYear->value,
                        'types' => $types,
                        'total_hours' => $totalHours,
                        'total_hours_per_week' => round($totalHoursPerWeek, 1),
                    ];
                }),
            ];
        });

        return response()->json($data);
    }

}
