<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
    public function index()
    {
        $groups = Group::with('specialty')->get();
        $formattedSpecialties = $groups->map(function ($group) {
            return [
                'id' => ['value' => $group->id, 'label' => ['editSidebar']],
                'name' => ['value' => $group->name, 'label' => ['table','editSidebar']],
                'student_count' => ['value' => $group->student_count, 'label' => ['table','editSidebar']],
                'specialty' => ['value' => $group->specialty, 'label' => ['table','editSidebar']],
            ];
        });
        return response()->json($formattedSpecialties);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'student_count' => 'required|integer',
            'specialty_id' => 'required|exists:specialties,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $group = Group::create($request->all());

        return response()->json([
            'id' => ['value' => $group->id, 'label' => ['editSidebar']],
            'name' => ['value' => $group->name, 'label' => ['table','editSidebar']],
            'student_count' => ['value' => $group->student_count, 'label' => ['table','editSidebar']],
            'specialty' => ['value' => $group->specialty, 'label' => ['table','editSidebar']],
        ], 201);
    }

    public function show($id)
    {
        $group = Group::find($id);

        if (!$group) {
            return response()->json(['error' => 'Group not found'], 404);
        }

        return response()->json($group);
    }

    public function update(Request $request, Group $group)
    {
        if (!$group) {
            return response()->json(['error' => 'Group not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'student_count' => 'required|integer',
            'specialty_id' => 'required|exists:specialties,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $group->update($request->all());

        return response()->json([
            'id' => ['value' => $group->id, 'label' => ['editSidebar']],
            'name' => ['value' => $group->name, 'label' => ['table','editSidebar']],
            'student_count' => ['value' => $group->student_count, 'label' => ['table','editSidebar']],
            'specialty' => ['value' => $group->specialty, 'label' => ['table','editSidebar']],
        ]);
    }

    public function destroy($id)
    {
        $group = Group::find($id);

        if (!$group) {
            return response()->json(['error' => 'Group not found'], 404);
        }

        $group->delete();

        return response()->json(200);
    }
}