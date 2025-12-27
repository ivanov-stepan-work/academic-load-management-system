<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $users = User::with('roles')->get();
        $formattedUsers = $users->map(function ($user) {
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
        return response()->json($formattedUsers);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Filter user data based on your needs
        $userData = [
            'id' => $user->id,
            'login' => $user->login,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'middlename' => $user->middlename,
            'email' => $user->email,
        ];

        return response()->json($userData);
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
            'login' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8',
            'firstname' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users',
            'roles' => 'array',
            'roles.*' => 'exists:roles,id',
            'teacher_statuses' => 'array',
            'teacher_statuses.*' => 'exists:teacher_statuses,id',
            'departments' => 'array',
            'departments.*' => 'exists:departments,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'login' => $request->login,
            'password' => Hash::make($request->password),
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'middlename' => $request->middlename,
            'email' => $request->email,
        ]);

        if ($request->has('roles')) {
            $user->roles()->attach($request->roles);
        }

        if ($request->has('teacher_statuses')) {
            $user->teacherStatuses()->attach($request->teacher_statuses);
        }
        if ($request->has('departments')) {
            $user->departments()->attach($request->departments);
        }

        return response()->json([
            'id' => ['value' => $user->id, 'label' => ['editSidebar']],
                'login' => ['value' => $user->login, 'label' => ['table','editSidebar']],
                'email' => ['value' => $user->email, 'label' => ['table','editSidebar']],
                'firstname' => ['value' => $user->firstname, 'label' => ['table','editSidebar']],
                'lastname' => ['value' => $user->lastname, 'label' => ['table','editSidebar']],
                'middlename' => ['value' => $user->middlename, 'label' => ['table','editSidebar']],
                'roles' => ['value' => $user->roles, 'label' => ['table', 'editSidebar']],
                'teacher_statuses' => ['value' => $user->teacherStatuses, 'label' => ['table', 'editSidebar']],
                'departments' => ['value' => $user->departments, 'label' => ['table', 'editSidebar']],
        ], 201);
    }

    /**
    * Update the specified resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  int  $id
    * @return \Illuminate\Http\JsonResponse
    */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string|max:255|unique:users,login,' . $user->id,
            'password' => 'nullable|string|min:8',
            'firstname' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'roles' => 'array',
            'roles.*' => 'exists:roles,id',
            'teacher_statuses' => 'array',
            'teacher_statuses.*' => 'exists:teacher_statuses,id',
            'departments' => 'array',
            'departments.*' => 'exists:departments,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $user->roles()->sync($request->roles);
        $user->teacherStatuses()->sync($request->teacher_statuses);
        $user->departments()->sync($request->departments);

        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->update($request->only([
            'login',
            'firstname',
            'lastname',
            'middlename',
            'email',
            'password'
        ]));
        // Нужно пройтись по всем ролям user
        // Тех ролей, что нет в $request->roles нужно удалить. Тех что нет у user, но есть в request, нужно добавить. В request передаются только id. Тоже самое сделать для $user->teacherStatuses, $user->departments
        return response()->json([
            'id' => ['value' => $user->id, 'label' => ['editSidebar']],
                'login' => ['value' => $user->login, 'label' => ['table','editSidebar']],
                'email' => ['value' => $user->email, 'label' => ['table','editSidebar']],
                'firstname' => ['value' => $user->firstname, 'label' => ['table','editSidebar']],
                'lastname' => ['value' => $user->lastname, 'label' => ['table','editSidebar']],
                'middlename' => ['value' => $user->middlename, 'label' => ['table','editSidebar']],
                'roles' => ['value' => $user->roles, 'label' => ['table', 'editSidebar']],
                'teacher_statuses' => ['value' => $user->teacherStatuses, 'label' => ['table', 'editSidebar']],
                'departments' => ['value' => $user->departments, 'label' => ['table', 'editSidebar']],
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
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Check authorization if needed

        $user->delete();

        return response()->json(200);
    }
}
