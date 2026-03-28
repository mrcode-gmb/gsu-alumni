<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Departments\StoreDepartmentRequest;
use App\Http\Requests\Admin\Departments\UpdateDepartmentRequest;
use App\Http\Requests\Admin\Departments\UpdateDepartmentStatusRequest;
use App\Models\Department;
use App\Models\Faculty;
use App\Services\DepartmentService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function __construct(
        protected DepartmentService $departmentService,
    ) {
    }

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $facultyId = $request->filled('faculty_id') ? (int) $request->integer('faculty_id') : null;

        $departments = Department::query()
            ->with('faculty:id,name')
            ->search($search)
            ->when($facultyId, fn ($query) => $query->where('faculty_id', $facultyId))
            ->ordered()
            ->get();

        $usedDepartmentIds = $this->departmentService->usedDepartmentIds(
            $departments->pluck('id')->all(),
        );

        return Inertia::render('admin/departments/index', [
            'departments' => $departments->map(
                fn (Department $department): array => $this->departmentPayload(
                    $department,
                    ! in_array($department->id, $usedDepartmentIds, true),
                ),
            ),
            'filters' => [
                'search' => $search,
                'faculty_id' => $facultyId ? (string) $facultyId : '',
            ],
            'summary' => [
                'total' => Department::count(),
                'active' => Department::where('is_active', true)->count(),
                'inactive' => Department::where('is_active', false)->count(),
            ],
            'facultyOptions' => Faculty::query()
                ->ordered()
                ->get(['id', 'name'])
                ->map(fn (Faculty $faculty): array => [
                    'value' => (string) $faculty->id,
                    'label' => $faculty->name,
                ])
                ->values(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/departments/create', [
            'facultyOptions' => $this->facultyOptions(),
        ]);
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $this->departmentService->create($request->validated());

        return to_route('admin.departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function edit(Department $department): Response
    {
        $department->loadMissing('faculty:id,name');

        return Inertia::render('admin/departments/edit', [
            'department' => $this->departmentPayload(
                $department,
                ! $this->departmentService->hasRecordedPayments($department),
            ),
            'facultyOptions' => $this->facultyOptions(),
        ]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $this->departmentService->update($department, $request->validated());

        return to_route('admin.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function updateStatus(UpdateDepartmentStatusRequest $request, Department $department): RedirectResponse
    {
        $isActive = (bool) $request->validated('is_active');

        $this->departmentService->updateStatus($department, $isActive);

        return back()->with(
            'success',
            $isActive
                ? 'Department activated successfully.'
                : 'Department deactivated successfully.',
        );
    }

    public function destroy(Department $department): RedirectResponse
    {
        try {
            $this->departmentService->delete($department);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Department deleted successfully.');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function facultyOptions(): array
    {
        return Faculty::query()
            ->ordered()
            ->get(['id', 'name'])
            ->map(fn (Faculty $faculty): array => [
                'value' => (string) $faculty->id,
                'label' => $faculty->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function departmentPayload(Department $department, bool $canDelete): array
    {
        return [
            'id' => $department->id,
            'faculty_id' => $department->faculty_id,
            'faculty_name' => $department->faculty?->name,
            'name' => $department->name,
            'is_active' => $department->is_active,
            'display_order' => $department->display_order,
            'can_delete' => $canDelete,
            'created_at' => $department->created_at?->toIso8601String(),
            'updated_at' => $department->updated_at?->toIso8601String(),
        ];
    }
}
