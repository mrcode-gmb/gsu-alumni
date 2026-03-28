<?php

namespace Database\Seeders;

use App\Models\ProgramType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProgramTypeSeeder extends Seeder
{
    public function run(): void
    {
        $programTypes = [
            [
                'name' => 'Undergraduate',
                'description' => 'Regular undergraduate degree programmes run through the university faculties and departments.',
            ],
            [
                'name' => 'Part-Time Undergraduate',
                'description' => 'Part-time undergraduate degree programmes coordinated through the university directorate.',
            ],
            [
                'name' => 'Diploma',
                'description' => 'Diploma programmes offered through approved university centres and directorates.',
            ],
            [
                'name' => 'Certificate',
                'description' => 'Certificate programmes offered through approved university centres and allied programmes.',
            ],
            [
                'name' => 'Pre-Degree',
                'description' => 'Pre-degree and allied preparatory programmes run through SPAP.',
            ],
            [
                'name' => 'Postgraduate Diploma',
                'description' => 'Postgraduate diploma programmes approved under the postgraduate school.',
            ],
            [
                'name' => 'Professional Programme',
                'description' => 'Professional postgraduate programmes such as MBA, MPA, and related approved courses.',
            ],
            [
                'name' => 'Masters',
                'description' => 'Masters degree programmes approved under the postgraduate school.',
            ],
            [
                'name' => 'PhD',
                'description' => 'Doctor of Philosophy programmes approved under the postgraduate school.',
            ],
        ];

        $activeProgramTypeSlugs = collect($programTypes)
            ->map(fn (array $programType): string => Str::slug($programType['name']))
            ->all();

        foreach ($programTypes as $index => $programType) {
            ProgramType::query()->updateOrCreate(
                ['slug' => Str::slug($programType['name'])],
                [
                    'name' => $programType['name'],
                    'description' => $programType['description'],
                    'display_order' => $index + 1,
                    'is_active' => true,
                ],
            );
        }

        ProgramType::query()
            ->whereNotIn('slug', $activeProgramTypeSlugs)
            ->update(['is_active' => false]);
    }
}
