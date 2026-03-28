<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Faculties\StoreFacultyRequest;
use App\Http\Requests\Admin\Faculties\UpdateFacultyRequest;
use App\Http\Requests\Admin\Faculties\UpdateFacultyStatusRequest;
use App\Models\Faculty;
use App\Services\FacultyService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FacultyController extends Controller
{
    public function __construct(
        protected FacultyService $facultyService,
    ) {
    }

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));

        $faculties = Faculty::query()
            ->withCount('departments')
            ->search($search)
            ->ordered()
            ->get();

        $usedFacultyIds = $this->facultyService->usedFacultyIds(
            $faculties->pluck('id')->all(),
        );

        return Inertia::render('admin/faculties/index', [
            'faculties' => $faculties->map(
                fn (Faculty $faculty): array => $this->facultyPayload(
                    $faculty,
                    $faculty->departments_count === 0 && ! in_array($faculty->id, $usedFacultyIds, true),
                ),
            ),
            'filters' => [
                'search' => $search,
            ],
            'summary' => [
                'total' => Faculty::count(),
                'active' => Faculty::where('is_active', true)->count(),
                'inactive' => Faculty::where('is_active', false)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/faculties/create');
    }

    public function store(StoreFacultyRequest $request): RedirectResponse
    {
        $this->facultyService->create($request->validated());

        return to_route('admin.faculties.index')
            ->with('success', 'Faculty created successfully.');
    }

    public function edit(Faculty $faculty): Response
    {
        $faculty->loadCount('departments');

        return Inertia::render('admin/faculties/edit', [
            'faculty' => $this->facultyPayload(
                $faculty,
                $faculty->departments_count === 0 && ! $this->facultyService->hasRecordedPayments($faculty),
            ),
        ]);
    }

    public function update(UpdateFacultyRequest $request, Faculty $faculty): RedirectResponse
    {
        $this->facultyService->update($faculty, $request->validated());

        return to_route('admin.faculties.index')
            ->with('success', 'Faculty updated successfully.');
    }

    public function updateStatus(UpdateFacultyStatusRequest $request, Faculty $faculty): RedirectResponse
    {
        $isActive = (bool) $request->validated('is_active');

        $this->facultyService->updateStatus($faculty, $isActive);

        return back()->with(
            'success',
            $isActive
                ? 'Faculty activated successfully.'
                : 'Faculty deactivated successfully.',
        );
    }

    public function destroy(Faculty $faculty): RedirectResponse
    {
        try {
            $this->facultyService->delete($faculty);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Faculty deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function facultyPayload(Faculty $faculty, bool $canDelete): array
    {
        return [
            'id' => $faculty->id,
            'name' => $faculty->name,
            'is_active' => $faculty->is_active,
            'display_order' => $faculty->display_order,
            'departments_count' => $faculty->departments_count ?? 0,
            'can_delete' => $canDelete,
            'created_at' => $faculty->created_at?->toIso8601String(),
            'updated_at' => $faculty->updated_at?->toIso8601String(),
        ];
    }
}
