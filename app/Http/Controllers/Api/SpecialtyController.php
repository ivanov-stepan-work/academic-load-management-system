<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SpecialtyController extends Controller
{
    public function index()
    {
        $specialties = Specialty::with('department')->get();
        $formattedSpecialties = $specialties->map(function ($specialty) {
            return [
                'id' => ['value' => $specialty->id, 'label' => ['editSidebar']],
                'name' => ['value' => $specialty->name, 'label' => ['table','editSidebar']],
                'department' => ['value' => $specialty->department, 'label' => ['table','editSidebar']],
            ];
        });
        return response()->json($formattedSpecialties);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $specialty = Specialty::create($request->all());

        return response()->json([
            'id' => ['value' => $specialty->id, 'label' => ['editSidebar']],
            'name' => ['value' => $specialty->name, 'label' => ['table','editSidebar']],
            'department' => ['value' => $specialty->department, 'label' => ['table','editSidebar']],
        ], 201);
    }

    public function show($id)
    {
        $specialty = Specialty::find($id);

        if (!$specialty) {
            return response()->json(['error' => 'Specialty not found'], 404);
        }

        return response()->json($specialty);
    }

    public function update(Request $request, $id)
    {
        $specialty = Specialty::find($id);

        if (!$specialty) {
            return response()->json(['error' => 'Specialty not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $specialty->update($request->all());

        return response()->json([
            'id' => ['value' => $specialty->id, 'label' => ['editSidebar']],
            'name' => ['value' => $specialty->name, 'label' => ['table','editSidebar']],
            'department' => ['value' => $specialty->department, 'label' => ['table','editSidebar']],
        ]);
    }

    public function destroy($id)
    {
        $specialty = Specialty::find($id);

        if (!$specialty) {
            return response()->json(['error' => 'Specialty not found'], 404);
        }

        $specialty->delete();

        return response()->json(200);
    }
}
