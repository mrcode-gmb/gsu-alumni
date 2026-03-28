<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departmentsByFaculty = [
            'Faculty of Basic Medical Sciences' => [
                'Human Anatomy',
                'Human Physiology',
                'Medical Biochemistry',
            ],
            'Faculty of Basic Clinical Sciences' => [
                'Chemical Pathology',
                'Clinical Pharmacology and Therapeutics',
                'Haematology and Blood Transfusion',
                'Histopathology',
                'Medical Microbiology and Immunology',
            ],
            'Faculty of Clinical Sciences' => [
                'Anaesthesia',
                'E.N.T',
                'Medicine',
                'Community Medicine and Public Health',
                'Surgery',
                'Paediatrics',
                'Obstetrics and Gynaecology',
                'Ophthalmology',
                'Radiology',
            ],
            'Faculty of Arts and Social Sciences' => [
                'Accounting',
                'Business Administration',
                'Economics',
                'Languages and Linguistics',
                'History',
                'Political Science',
                'Public Administration',
                'Religious Studies',
                'Sociology',
            ],
            'Faculty of Environmental Sciences' => [
                'Architectural Technology',
                'Building Technology',
                'Estate Management',
                'Quantity Surveying',
            ],
            'Faculty of Sciences' => [
                'Biological Sciences',
                'Biochemistry',
                'Plant Sciences',
                'Chemistry',
                'Computer Science',
                'Geography and Environmental Management',
                'Geology',
                'Mathematical Sciences',
                'Microbiology',
                'Pure and Applied Physics',
                'Science Laboratory Technology',
                'Zoology and Wildlife Ecology',
            ],
            'Faculty of Education' => [
                'Arts and Social Sciences Education',
                'Educational Foundation',
                'Science Education',
            ],
            'Faculty of Law' => [
                'Sharia',
                'Public Law',
            ],
            'Faculty of Pharmaceutical Sciences' => [
                'Pharmacognosy and Drug Development',
                'Pharmacology and Therapeutics',
                'Pharmaceutics and Pharmaceutical Technology',
                'Clinical Pharmacy and Pharmacy Practice',
                'Pharmaceutics and Medicinal Chemistry',
                'Pharmaceutical Microbiology',
            ],
        ];

        $activeDepartmentIds = collect();

        foreach ($departmentsByFaculty as $facultyName => $departments) {
            $faculty = Faculty::query()->where('name', $facultyName)->first();

            if (! $faculty) {
                continue;
            }

            $facultyDepartmentIds = collect($departments)
                ->map(function (string $name, int $index) use ($faculty): int {
                    $department = Department::query()->updateOrCreate(
                        [
                            'faculty_id' => $faculty->id,
                            'slug' => Str::slug($name),
                        ],
                        [
                            'name' => $name,
                            'display_order' => $index + 1,
                            'is_active' => true,
                        ],
                    );

                    return $department->id;
                });

            $activeDepartmentIds = $activeDepartmentIds->merge($facultyDepartmentIds);
        }

        if ($activeDepartmentIds->isNotEmpty()) {
            Department::query()
                ->whereNotIn('id', $activeDepartmentIds->all())
                ->update(['is_active' => false]);
        }
    }
}
