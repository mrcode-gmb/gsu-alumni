<?php

namespace Database\Seeders;

use App\Models\Faculty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FacultySeeder extends Seeder
{
    public function run(): void
    {
        $faculties = [
            'Faculty of Basic Medical Sciences',
            'Faculty of Basic Clinical Sciences',
            'Faculty of Clinical Sciences',
            'Faculty of Arts and Social Sciences',
            'Faculty of Environmental Sciences',
            'Faculty of Sciences',
            'Faculty of Education',
            'Faculty of Law',
            'Faculty of Pharmaceutical Sciences',
        ];

        $activeFacultySlugs = collect($faculties)
            ->map(fn (string $name): string => Str::slug($name))
            ->all();

        foreach ($faculties as $index => $name) {
            Faculty::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'display_order' => $index + 1,
                    'is_active' => true,
                ],
            );
        }

        Faculty::query()
            ->whereNotIn('slug', $activeFacultySlugs)
            ->update(['is_active' => false]);
    }
}
