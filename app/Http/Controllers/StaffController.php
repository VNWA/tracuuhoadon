<?php

namespace App\Http\Controllers;

use App\Http\Requests\StaffStoreRequest;
use App\Http\Requests\StaffUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    private function mapStaff(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at?->toDateTimeString(),
            'updated_at' => $user->updated_at?->toDateTimeString(),
        ];
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $perPage = (int) $request->integer('per_page', 10);
        $search = (string) $request->string('search');

        $staff = User::query()
            ->role('staff')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('staff/Index', [
            'staff' => $staff->through(fn (User $user) => $this->mapStaff($user)),
            'filters' => [
                'search' => $search,
                'perPage' => $perPage,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('staff/Create');
    }

    public function edit(User $staff): Response
    {
        $this->authorize('update', $staff);

        return Inertia::render('staff/Edit', [
            'staff' => $this->mapStaff($staff),
        ]);
    }

    public function store(StaffStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $staff = User::query()->create($request->validated());
        $staff->assignRole('staff');

        return to_route('admin.staff.index');
    }

    public function update(StaffUpdateRequest $request, User $staff): RedirectResponse
    {
        $this->authorize('update', $staff);

        $payload = $request->validated();
        if (! filled($payload['password'] ?? null)) {
            unset($payload['password']);
        }

        $staff->update($payload);

        return to_route('admin.staff.index');
    }

    public function destroy(User $staff): RedirectResponse
    {
        $this->authorize('delete', $staff);

        $staff->delete();

        return to_route('admin.staff.index');
    }
}
