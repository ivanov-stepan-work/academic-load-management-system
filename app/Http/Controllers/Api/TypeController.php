<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TypeController extends Controller
{
    public function index()
    {
        $types = Type::all();
        $formattedTypes = $types->map(function ($type) {
            return [
                'id' => ['value' => $type->id, 'label' => ['editSidebar']],
                'name' => ['value' => $type->name, 'label' => ['table','editSidebar']],
            ];
        });
        return response()->json($formattedTypes);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:types',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $type = Type::create($request->all());

        return response()->json($type, 201);
    }

    public function show($id)
    {
        $type = Type::find($id);

        if (!$type) {
            return response()->json(['error' => 'Type not found'], 404);
        }

        return response()->json($type);
    }

    public function update(Request $request, $id)
    {
        $type = Type::find($id);

        if (!$type) {
            return response()->json(['error' => 'Type not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:types,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $type->update($request->all());

        return response()->json([
            'id' => ['value' => $type->id, 'label' => ['editSidebar']],
            'name' => ['value' => $type->name, 'label' => ['table','editSidebar']],
        ]);
    }

    public function destroy($id)
    {
        $type = Type::find($id);

        if (!$type) {
            return response()->json(['error' => 'Type not found'], 404);
        }

        $type->delete();

        return response()->json(200);
    }
}
