<x-app-layout>
    <div class="min-h-screen bg-[#F7F9F6] py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="font-display text-2xl text-[#1F2421] mb-6">Staff & Roles</h1>

            @if (session('success'))
                <div class="mb-4 bg-green-100 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 bg-red-100 text-red-800 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
            @endif

            {{-- Desktop: table --}}
            <div class="hidden md:block bg-white rounded-lg border border-gray-100 overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#F7F9F6] text-xs text-gray-500 uppercase tracking-wide">
                        <tr>
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Email</th>
                            <th class="px-6 py-3">Role</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($staff as $user)
                            <tr>
                                <td class="px-6 py-4 font-medium text-[#1F2421]">{{ $user->name }}</td>
                                <td class="px-6 py-4 text-gray-500 truncate max-w-[220px]">{{ $user->email }}</td>
                                <td class="px-6 py-4">
                                    <form action="{{ route('admin.staff.update', $user) }}" method="POST" class="flex items-center gap-2">
                                        @csrf @method('PATCH')
                                        <select name="role" class="rounded-lg border-gray-300 text-sm focus:ring-[#1B4332]">
                                            <option value="none" @selected($user->role === 'none')>No Access</option>
                                            <option value="warehouse_staff" @selected($user->role === 'warehouse_staff')>Warehouse Staff</option>
                                            <option value="admin" @selected($user->role === 'admin')>Admin</option>
                                        </select>
                                        <button type="submit" class="text-[#1B4332] text-sm hover:underline shrink-0">Save</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile: stacked cards --}}
            <div class="md:hidden space-y-3">
                @foreach ($staff as $user)
                    <div class="bg-white rounded-lg border border-gray-100 p-4">
                        <p class="font-medium text-[#1F2421]">{{ $user->name }}</p>
                        <p class="text-sm text-gray-500 break-all">{{ $user->email }}</p>
                        <form action="{{ route('admin.staff.update', $user) }}" method="POST" class="mt-3 flex items-center gap-2">
                            @csrf @method('PATCH')
                            <select name="role" class="flex-1 rounded-lg border-gray-300 text-sm focus:ring-[#1B4332]">
                                <option value="none" @selected($user->role === 'none')>No Access</option>
                                <option value="warehouse_staff" @selected($user->role === 'warehouse_staff')>Warehouse Staff</option>
                                <option value="admin" @selected($user->role === 'admin')>Admin</option>
                            </select>
                            <button type="submit" class="bg-[#1B4332] text-white px-3 py-2 rounded-lg text-sm shrink-0">Save</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
