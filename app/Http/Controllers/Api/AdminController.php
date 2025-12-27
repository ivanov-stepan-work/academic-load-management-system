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

class AdminController extends Controller
{
    public function index()
    {
        // Получаем всех пользователей и форматируем их
        $formattedUsers = User::all()->map(function ($user) {
            return [
                'id' => ['value' => $user->id, 'label' => ['editSidebar']],
                'login' => ['value' => $user->login, 'label' => ['table','editSidebar']],
                'email' => ['value' => $user->email, 'label' => ['table','editSidebar']],
                'firstname' => ['value' => $user->firstname, 'label' => ['table','editSidebar']],
                'lastname' => ['value' => $user->lastname, 'label' => ['table','editSidebar']],
                'middlename' => ['value' => $user->middlename, 'label' => ['table','editSidebar']],
                'roles' => ['value' => $user->roles, 'label' => ['table', 'editSidebar']],
                'teacher_statuses' => ['value' => $user->teacherStatuses, 'label' => ['table', 'editSidebar']],
                'departments' => ['value' => $user->departments, 'label' => ['table', 'editSidebar']],
            ];
        });

        // Получаем все департаменты и форматируем их
        $formattedDepartments = Department::with('user')->get()->map(function ($department) {
            return [
                'id' => ['value' => $department->id, 'label' => ['editSidebar']],
                'titleLeft' => $department->name,
                'titleRight' => $department->totalHours(),
                'name' => ['value' => $department->name, 'label' => ['table','editSidebar']],
                'user' => ['value' => $department->user, 'label' => ['table','editSidebar']],
                'title' => $department->name,
                'href' => "/assistantDirector/" . $department->id,
            ];
        });

        // Получаем все группы и форматируем их
        $formattedGroups = Group::with('specialty')->get()->map(function ($group) {
            return [
                'id' => ['value' => $group->id, 'label' => ['editSidebar']],
                'name' => ['value' => $group->name, 'label' => ['table','editSidebar']],
                'student_count' => ['value' => $group->student_count, 'label' => ['table','editSidebar']],
                'specialty' => ['value' => $group->specialty, 'label' => ['table','editSidebar']],
            ];
        });

        // Получаем все роли и форматируем их
        $formattedRoles = Role::all()->map(function ($role) {
            return [
                'id' => ['value' => $role->id, 'label' => ['editSidebar']],
                'name' => ['value' => $role->name, 'label' => ['table','editSidebar']],
            ];
        });

        // Получаем все специальности и форматируем их
        $formattedSpecialties = Specialty::with('department')->get()->map(function ($specialty) {
            return [
                'id' => ['value' => $specialty->id, 'label' => ['editSidebar']],
                'name' => ['value' => $specialty->name, 'label' => ['table','editSidebar']],
                'department' => ['value' => $specialty->department, 'label' => ['table','editSidebar']],
            ];
        });

        // Получаем все предметы и форматируем их
        $formattedSubjects = Subject::all()->map(function ($subject) {
            return [
                'id' => ['value' => $subject->id, 'label' => ['editSidebar']],
                'name' => ['value' => $subject->name, 'label' => ['table','editSidebar']],
            ];
        });

        // Получаем все подтипы и форматируем их
        $formattedSubTypes = Subtype::all()->map(function ($subType) {
            return [
                'id' => ['value' => $subType->id, 'label' => ['editSidebar']],
                'name' => ['value' => $subType->name, 'label' => ['table','editSidebar']],
            ];
        });

        // Получаем все типы и форматируем их
        $formattedTypes = Type::all()->map(function ($type) {
            return [
                'id' => ['value' => $type->id, 'label' => ['editSidebar']],
                'name' => ['value' => $type->name, 'label' => ['table','editSidebar']],
            ];
        });
        $formattedTeacherStatuses = TeacherStatus::all()->map(function ($teacher_status) {
            return [
                'id' => ['value' => $teacher_status->id, 'label' => ['editSidebar']],
                'name' => ['value' => $teacher_status->name, 'label' => ['table','editSidebar']],
            ];
        });
        $formattedStudyYears = StudyYear::all()->map(function ($study_year) {
            return [
                'id' => ['value' => $study_year->id, 'label' => ['editSidebar']],
                'name' => ['value' => $study_year->start_year . '-' . $study_year->end_year, 'label' => ['table']],
                'start_year' => ['value' => $study_year->start_year, 'label' => ['editSidebar']],
                'end_year' => ['value' => $study_year->end_year, 'label' => ['editSidebar']],
            ];
        });
        // Возвращаем результат в формате JSON
        return response()->json([
            'users' => $formattedUsers,
            'departments' => $formattedDepartments,
            'groups' => $formattedGroups,
            'roles' => $formattedRoles,
            'specialties' => $formattedSpecialties,
            'subjects' => $formattedSubjects,
            'subtypes' => $formattedSubTypes,
            'types' => $formattedTypes,
            'teacher_statuses' => $formattedTeacherStatuses,
            'study_years' => $formattedStudyYears,
        ]);
    }
}
