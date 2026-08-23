<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-2xl font-semibold text-[#1F2421]">
                Add Staff Member
            </h2>
            <a href="{{ route('admin.staff.index') }}" class="text-sm text-[#1B4332] hover:underline">
                &larr; Back to staff
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <form method="POST" action="{{ route('admin.staff.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Full name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#1B4332] focus:border-[#1B4332]">
                    @error('name') <p class="text-xs text-[#C2622D] mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#1B4332] focus:border-[#1B4332]">
                    @error('email') <p class="text-xs text-[#C2622D] mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Role</label>
                    <select name="role" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#1B4332] focus:border-[#1B4332]">
                        <option value="">Select a role</option>
                        <option value="warehouse_staff" @selected(old('role') === 'warehouse_staff')>Warehouse Staff</option>
                        <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                    </select>
                    @error('role') <p class="text-xs text-[#C2622D] mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Password</label>
                    <input type="password" name="password" required
                           class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#1B4332] focus:border-[#1B4332]">
                    @error('password') <p class="text-xs text-[#C2622D] mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Confirm password</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#1B4332] focus:border-[#1B4332]">
                </div>

                <button type="submit" class="w-full bg-[#1B4332] text-white text-sm font-medium px-4 py-2.5 rounded-lg hover:bg-[#1B4332]/90">
                    Create Staff Account
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
