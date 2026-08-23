<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-2xl font-semibold text-[#1F2421]">
                Staff Management
            </h2>
            <a href="{{ route('admin.staff.create') }}" class="bg-[#1B4332] text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-[#1B4332]/90">
                Add Staff Member
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        @if (session('success'))
            <div class="mb-4 rounded-lg bg-[#1B4332]/10 border border-[#1B4332]/30 text-[#1B4332] px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-lg bg-[#C2622D]/10 border border-[#C2622D]/30 text-[#C2622D] px-4 py-3 text-sm">
                {{ session('error') }}
            </div>
        @endif

        {{-- Desktop table --}}
        <div class="hidden md:block bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#F7F9F6]">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Role</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($staff as $user)
                        <tr class="hover:bg-[#F7F9F6]">
                            <td class="px-4 py-3 text-sm font-medium text-[#1F2421]">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $user->email }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'text-xs font-medium px-2.5 py-1 rounded-full',
                                    'bg-[#1B4332]/10 text-[#1B4332]' => $user->role === 'admin',
                                    'bg-[#E8A93B]/20 text-[#8a5c14]' => $user->role === 'warehouse_staff',
                                    'bg-gray-100 text-gray-500' => $user->role === 'none',
                                ])>
                                    {{ str($user->role)->replace('_', ' ')->title() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('admin.staff.update', $user) }}" class="inline-flex gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="role" onchange="this.form.submit()"
                                            class="rounded-lg border-gray-300 text-sm focus:ring-[#1B4332] focus:border-[#1B4332]">
                                        <option value="admin" @selected($user->role === 'admin')>Admin</option>
                                        <option value="warehouse_staff" @selected($user->role === 'warehouse_staff')>Warehouse Staff</option>
                                        <option value="none" @selected($user->role === 'none')>None</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden space-y-3">
            @foreach ($staff as $user)
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <p class="font-medium text-[#1F2421]">{{ $user->name }}</p>
                            <p class="text-sm text-gray-500">{{ $user->email }}</p>
                        </div>
                        <span @class([
                            'text-xs font-medium px-2.5 py-1 rounded-full',
                            'bg-[#1B4332]/10 text-[#1B4332]' => $user->role === 'admin',
                            'bg-[#E8A93B]/20 text-[#8a5c14]' => $user->role === 'warehouse_staff',
                            'bg-gray-100 text-gray-500' => $user->role === 'none',
                        ])>
                            {{ str($user->role)->replace('_', ' ')->title() }}
                        </span>
                    </div>
                    <form method="POST" action="{{ route('admin.staff.update', $user) }}" class="mt-2">
                        @csrf
                        @method('PATCH')
                        <select name="role" onchange="this.form.submit()"
                                class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#1B4332] focus:border-[#1B4332]">
                            <option value="admin" @selected($user->role === 'admin')>Admin</option>
                            <option value="warehouse_staff" @selected($user->role === 'warehouse_staff')>Warehouse Staff</option>
                            <option value="none" @selected($user->role === 'none')>None</option>
                        </select>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
