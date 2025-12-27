<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subtype;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubtypeController extends Controller
{
    public function index()
    {
        $subTypes = Subtype::all();
        $formattedSubTypes = $subTypes->map(function ($subType) {
            return [
                'id' => ['value' => $subType->id, 'label' => ['editSidebar']],
                'name' => ['value' => $subType->name, 'label' => ['table','editSidebar']],
            ];
        });
        return response()->json($formattedSubTypes);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:subtypes',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $subtype = Subtype::create($request->all());

        return response()->json($subtype, 201);
    }

    public function show($id)
    {
        $subtype = Subtype::find($id);

        if (!$subtype) {
            return response()->json(['error' => 'Subtype not found'], 404);
        }

        return response()->json($subtype);
    }

    public function update(Request $request, $id)
    {
        $subType = Subtype::find($id);

        if (!$subType) {
            return response()->json(['error' => 'Subtype not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:subtypes,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $subType->update($request->all());

        return response()->json([
            'id' => ['value' => $subType->id, 'label' => ['editSidebar']],
            'name' => ['value' => $subType->name, 'label' => ['table','editSidebar']],
        ]);
    }

    public function destroy($id)
    {
        $subtype = Subtype::find($id);

        if (!$subtype) {
            return response()->json(['error' => 'Subtype not found'], 404);
        }

        $subtype->delete();

        return response()->json(200);
    }
}
