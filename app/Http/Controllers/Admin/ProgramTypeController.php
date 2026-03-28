<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProgramTypes\StoreProgramTypeRequest;
use App\Http\Requests\Admin\ProgramTypes\UpdateProgramTypeRequest;
use App\Http\Requests\Admin\ProgramTypes\UpdateProgramTypeStatusRequest;
use App\Models\ProgramType;
use App\Services\ProgramTypeService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProgramTypeController extends Controller
{
    public function __construct(
        protected ProgramTypeService $programTypeService,
    ) {
    }

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));

        $programTypes = ProgramType::query()
            ->search($search)
            ->ordered()
            ->get();

        $usedProgramTypeIds = $this->programTypeService->usedProgramTypeIds(
            $programTypes->pluck('id')->all(),
        );

        return Inertia::render('admin/program-types/index', [
            'programTypes' => $programTypes->map(
                fn (ProgramType $programType): array => $this->programTypePayload(
                    $programType,
                    ! in_array($programType->id, $usedProgramTypeIds, true),
                ),
            ),
            'filters' => [
                'search' => $search,
            ],
            'summary' => [
                'total' => ProgramType::count(),
                'active' => ProgramType::where('is_active', true)->count(),
                'inactive' => ProgramType::where('is_active', false)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/program-types/create');
    }

    public function store(StoreProgramTypeRequest $request): RedirectResponse
    {
        $this->programTypeService->create($request->validated());

        return to_route('admin.program-types.index')
            ->with('success', 'Program type created successfully.');
    }

    public function edit(ProgramType $programType): Response
    {
        return Inertia::render('admin/program-types/edit', [
            'programType' => $this->programTypePayload(
                $programType,
                ! $this->programTypeService->hasRecordedPayments($programType),
            ),
        ]);
    }

    public function update(UpdateProgramTypeRequest $request, ProgramType $programType): RedirectResponse
    {
        $this->programTypeService->update($programType, $request->validated());

        return to_route('admin.program-types.index')
            ->with('success', 'Program type updated successfully.');
    }

    public function updateStatus(
        UpdateProgramTypeStatusRequest $request,
        ProgramType $programType,
    ): RedirectResponse {
        $isActive = (bool) $request->validated('is_active');

        $this->programTypeService->updateStatus($programType, $isActive);

        return back()->with(
            'success',
            $isActive
                ? 'Program type activated successfully.'
                : 'Program type deactivated successfully.',
        );
    }

    public function destroy(ProgramType $programType): RedirectResponse
    {
        try {
            $this->programTypeService->delete($programType);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Program type deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function programTypePayload(ProgramType $programType, bool $canDelete): array
    {
        return [
            'id' => $programType->id,
            'name' => $programType->name,
            'description' => $programType->description,
            'is_active' => $programType->is_active,
            'display_order' => $programType->display_order,
            'can_delete' => $canDelete,
            'created_at' => $programType->created_at?->toIso8601String(),
            'updated_at' => $programType->updated_at?->toIso8601String(),
        ];
    }
}
