<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudyYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudyYearController extends Controller
{
    public function index()
    {
        $study_years = StudyYear::all();
        $formattedStudyYears = $study_years->map(function ($study_year) {
            return [
                'id' => ['value' => $study_year->id, 'label' => ['editSidebar']],
                'name' => ['value' => $study_year->name, 'label' => ['table']],
                'start_year' => ['value' => $study_year->start_year, 'label' => ['editSidebar']],
                'end_year' => ['value' => $study_year->end_year, 'label' => ['editSidebar']],
            ];
        });
        return response()->json($formattedStudyYears);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_year' => 'required|integer|unique:study_years',
            'end_year' => 'required|integer|unique:study_years',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $start_year = $request->input('start_year');
        $end_year = $request->input('end_year');

        if (($end_year - $start_year) !== 1) {
            return response()->json(['error' => 'The difference between start year and end year must be 1 year'], 422);
        }
        $study_year = StudyYear::create($request->all());

        return response()->json($study_year, 201);
    }

    public function show($id)
    {
        $study_year = StudyYear::find($id);

        if (!$study_year) {
            return response()->json(['error' => 'Study year not found'], 404);
        }

        return response()->json($study_year);
    }

    public function update(Request $request, $id)
    {
        $study_year = StudyYear::find($id);

        if (!$study_year) {
            return response()->json(['error' => 'Study year not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'start_year' => 'required|integer|unique:study_years,start_year,' . $id,
            'end_year' => 'required|integer|unique:study_years,end_year,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $start_year = $request->input('start_year');
        $end_year = $request->input('end_year');

        if (($end_year - $start_year) !== 1) {
            return response()->json(['error' => 'The difference between start year and end year must be 1 year'], 422);
        }

        $study_year->update($request->all());

        return response()->json([
            'id' => ['value' => $study_year->id, 'label' => ['editSidebar']],
            'name' => ['value' => $study_year->start_year . '-' . $study_year->end_year, 'label' => ['table']],
            'start_year' => ['value' => $study_year->start_year, 'label' => ['editSidebar']],
            'end_year' => ['value' => $study_year->end_year, 'label' => ['editSidebar']],
        ]);
    }


    public function destroy($id)
    {
        $study_year = StudyYear::find($id);

        if (!$study_year) {
            return response()->json(['error' => 'Study year not found'], 404);
        }

        $study_year->delete();

        return response()->json(200);
    }
}
