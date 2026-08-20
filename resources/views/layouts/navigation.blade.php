<nav x-data="{ menuOpen: false, accountOpen: false }" class="bg-white border-b border-gray-100 relative z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo />
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @if (Auth::user()->isInternalStaff())
                        <x-nav-link :href="route('categories.index')" :active="request()->routeIs('categories.*')">
                            {{ __('Categories') }}
                        </x-nav-link>

                        <x-nav-link :href="route('products.index')" :active="request()->routeIs('products.*')">
                            {{ __('Products') }}
                        </x-nav-link>

                        <x-nav-link :href="route('admin.orders.index')" :active="request()->routeIs('admin.orders.*')">
                            {{ __('Orders') }}
                        </x-nav-link>
                    @endif

                    @if (Auth::user()->isAdmin())
                        <x-nav-link :href="route('admin.staff.index')" :active="request()->routeIs('admin.staff.*')">
                            {{ __('Staff') }}
                        </x-nav-link>

                        <x-nav-link :href="route('admin.vendors.index')" :active="request()->routeIs('admin.vendors.*')">
                            {{ __('Vendors') }}
                        </x-nav-link>
                    @endif

                    @if (Auth::user()->isVendor())
                        <x-nav-link :href="route('storefront.index')" :active="request()->routeIs('storefront.*')">
                            {{ __('Shop') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            {{-- Mobile: account avatar + hamburger, each with its own dropdown card --}}
            <div class="flex items-center gap-2 sm:hidden">

                {{-- Account trigger + dropdown, wrapped together so outside-click detection works correctly --}}
                <div class="relative" @click.outside="accountOpen = false">
                    <button @click="accountOpen = ! accountOpen; menuOpen = false"
                            class="h-9 w-9 rounded-full bg-[#1B4332] text-white flex items-center justify-center font-semibold text-sm ring-2 ring-transparent focus:outline-none focus:ring-[#1B4332]/30 transition"
                            :class="{ 'ring-[#1B4332]/30': accountOpen }">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </button>

                    <div x-show="accountOpen"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="absolute right-0 top-full mt-2 w-64 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-50"
                         style="display: none;">

                        <div class="px-4 py-4 bg-[#1B4332]">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full bg-white/15 text-white flex items-center justify-center font-semibold text-sm shrink-0 border border-white/20">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-sm text-white truncate">{{ Auth::user()->name }}</p>
                                    <p class="text-xs text-white/70 truncate">{{ Auth::user()->email }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="py-1.5">
                            <a href="{{ route('profile.edit') }}"
                               class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition">
                                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                </svg>
                                {{ __('Profile') }}
                            </a>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <a href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); this.closest('form').submit();"
                                   class="flex items-center gap-3 px-4 py-2.5 text-sm text-[#C2622D] hover:bg-[#C2622D]/5 transition cursor-pointer">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0110.5 3h6a2.25 2.25 0 012.25 2.25v13.5A2.25 2.25 0 0116.5 21h-6a2.25 2.25 0 01-2.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3H21" />
                                    </svg>
                                    {{ __('Log Out') }}
                                </a>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Menu trigger + dropdown, wrapped together --}}
                <div class="relative" @click.outside="menuOpen = false">
                    <button @click="menuOpen = ! menuOpen; accountOpen = false"
                            class="h-9 w-9 inline-flex items-center justify-center rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none transition"
                            :class="{ 'bg-gray-100': menuOpen }">
                        <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': menuOpen, 'inline-flex': ! menuOpen }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': ! menuOpen, 'inline-flex': menuOpen }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <div x-show="menuOpen"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="absolute right-0 top-full mt-2 w-64 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-50"
                         style="display: none;">

                        <div class="px-4 pt-3 pb-1">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Menu</p>
                        </div>

                        <div class="py-1">
                            <a href="{{ route('dashboard') }}"
                               @class([
                                   'block px-4 py-2.5 text-sm font-medium transition',
                                   'text-[#1B4332] bg-[#1B4332]/5' => request()->routeIs('dashboard'),
                                   'text-gray-700 hover:bg-gray-50' => ! request()->routeIs('dashboard'),
                               ])>
                                {{ __('Dashboard') }}
                            </a>
                        </div>

                        @if (Auth::user()->isInternalStaff())
                            <div class="border-t border-gray-100 py-1">
                                <a href="{{ route('categories.index') }}"
                                   @class([
                                       'block px-4 py-2.5 text-sm font-medium transition',
                                       'text-[#1B4332] bg-[#1B4332]/5' => request()->routeIs('categories.*'),
                                       'text-gray-700 hover:bg-gray-50' => ! request()->routeIs('categories.*'),
                                   ])>
                                    {{ __('Categories') }}
                                </a>
                                <a href="{{ route('products.index') }}"
                                   @class([
                                       'block px-4 py-2.5 text-sm font-medium transition',
                                       'text-[#1B4332] bg-[#1B4332]/5' => request()->routeIs('products.*'),
                                       'text-gray-700 hover:bg-gray-50' => ! request()->routeIs('products.*'),
                                   ])>
                                    {{ __('Products') }}
                                </a>
                                <a href="{{ route('admin.orders.index') }}"
                                   @class([
                                       'block px-4 py-2.5 text-sm font-medium transition',
                                       'text-[#1B4332] bg-[#1B4332]/5' => request()->routeIs('admin.orders.*'),
                                       'text-gray-700 hover:bg-gray-50' => ! request()->routeIs('admin.orders.*'),
                                   ])>
                                    {{ __('Orders') }}
                                </a>
                            </div>
                        @endif

                        @if (Auth::user()->isAdmin())
                            <div class="border-t border-gray-100 py-1">
                                <a href="{{ route('admin.staff.index') }}"
                                   @class([
                                       'block px-4 py-2.5 text-sm font-medium transition',
                                       'text-[#1B4332] bg-[#1B4332]/5' => request()->routeIs('admin.staff.*'),
                                       'text-gray-700 hover:bg-gray-50' => ! request()->routeIs('admin.staff.*'),
                                   ])>
                                    {{ __('Staff') }}
                                </a>
                                <a href="{{ route('admin.vendors.index') }}"
                                   @class([
                                       'block px-4 py-2.5 text-sm font-medium transition',
                                       'text-[#1B4332] bg-[#1B4332]/5' => request()->routeIs('admin.vendors.*'),
                                       'text-gray-700 hover:bg-gray-50' => ! request()->routeIs('admin.vendors.*'),
                                   ])>
                                    {{ __('Vendors') }}
                                </a>
                            </div>
                        @endif

                        @if (Auth::user()->isVendor())
                            <div class="border-t border-gray-100 py-1">
                                <a href="{{ route('storefront.index') }}"
                                   @class([
                                       'block px-4 py-2.5 text-sm font-medium transition',
                                       'text-[#1B4332] bg-[#1B4332]/5' => request()->routeIs('storefront.*'),
                                       'text-gray-700 hover:bg-gray-50' => ! request()->routeIs('storefront.*'),
                                   ])>
                                    {{ __('Shop') }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
