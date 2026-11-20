<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoAcademicLoadSeeder extends Seeder
{
    private const HOURS_PER_DEPARTMENT = 4000;

    public function run(): void
    {
        $data = json_decode(
            file_get_contents(__DIR__.'/demo-academic-load.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        DB::transaction(function () use ($data) {
            foreach ([...array_keys($data), 'teacher_loads'] as $table) {
                if (DB::table($table)->exists()) {
                    throw new RuntimeException('DemoAcademicLoadSeeder requires an empty academic database.');
                }
            }

            $demoPassword = Hash::make('password');
            foreach ($data['users'] as &$user) {
                $user['password'] = $demoPassword;
            }
            unset($user);

            $teacherIds = array_column(
                array_filter($data['role_user'], fn ($row) => $row['role_id'] === 4),
                'user_id',
            );
            $teacherLoads = [];
            foreach ($data['departments'] as $department) {
                $departmentId = $department['id'];
                $departmentTeachers = array_column(array_filter(
                    $data['department_user'],
                    fn ($row) => $row['department_id'] === $departmentId
                        && in_array($row['user_id'], $teacherIds, true),
                ), 'user_id');
                $loadIndexes = array_keys(array_filter(
                    $data['department_loads'],
                    fn ($row) => $row['department_id'] === $departmentId,
                ));
                $this->scaleDepartmentHours($data['department_loads'], $loadIndexes);

                $teacherLoads = array_merge($teacherLoads, $this->distributeHours(
                    array_map(fn ($index) => $data['department_loads'][$index], $loadIndexes),
                    $departmentTeachers,
                    array_column($data['study_years'], 'id'),
                    array_column($data['types'], 'id'),
                    array_column($data['subtypes'], 'id'),
                ));
            }

            $data['teacher_loads'] = $teacherLoads;
            $timestamp = now();
            foreach ($data as $table => $rows) {
                if (!in_array($table, ['role_user', 'department_user', 'user_teacher_statuses'], true)) {
                    $rows = array_map(fn ($row) => $row + [
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ], $rows);
                }
                DB::table($table)->insert($rows);
            }
        });
    }

    private function scaleDepartmentHours(array &$loads, array $indexes): void
    {
        $originalTotal = array_sum(array_map(fn ($index) => $loads[$index]['hours_allocated'], $indexes));
        $originalSoFar = 0;
        $scaledSoFar = 0;

        foreach ($indexes as $index) {
            $originalSoFar += $loads[$index]['hours_allocated'];
            // Rebasing the cumulative total keeps rounding from changing the department budget.
            $scaledTotal = (int) round($originalSoFar * self::HOURS_PER_DEPARTMENT / $originalTotal);
            $loads[$index]['hours_allocated'] = $scaledTotal - $scaledSoFar;
            $scaledSoFar = $scaledTotal;
        }
    }

    private function distributeHours(array $loads, array $teachers, array $years, array $types, array $subtypes): array
    {
        shuffle($loads);
        shuffle($teachers);
        $teacherTotals = array_fill_keys($teachers, 0);
        $assignments = [];

        foreach ($loads as $index => $load) {
            // Assign shuffled loads to the teacher with the fewest hours so far.
            $teacher = array_search(min($teacherTotals), $teacherTotals, true);
            $teacherTotals[$teacher] += $load['hours_allocated'];
            $assignments[] = [
                'teacher_id' => $teacher,
                'department_load_id' => $load['id'],
                'hours_allocated' => $load['hours_allocated'],
                'study_year_id' => $years[$index % count($years)],
                'half_year' => $load['semester'] % 2 === 0 ? 2 : 1,
                'type_id' => $types[array_rand($types)],
                'subtype_id' => $subtypes[array_rand($subtypes)],
            ];
        }

        return $assignments;
    }
}
