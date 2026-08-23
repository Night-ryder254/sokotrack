<x-guest-layout>
    <div x-data="{ tab: '{{ $initialTab ?? 'login' }}' }">

        <div class="mb-8">
            <h2 class="font-display text-2xl font-semibold text-[#1F2421]" x-text="tab === 'login' ? 'Welcome back' : 'Create your vendor account'"></h2>
            <p class="text-sm text-gray-500 mt-1" x-text="tab === 'login' ? 'Log in to your SokoTrack account' : 'Start ordering from your distributor today'"></p>
        </div>

        {{-- Tab switcher --}}
        <div class="flex bg-[#F7F9F6] rounded-lg p-1 mb-6">
            <button type="button" @click="tab = 'login'"
                    :class="tab === 'login' ? 'bg-white text-[#1B4332] shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                    class="flex-1 py-2 text-sm font-medium rounded-md transition">
                Log In
            </button>
            <button type="button" @click="tab = 'register'"
                    :class="tab === 'register' ? 'bg-white text-[#1B4332] shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                    class="flex-1 py-2 text-sm font-medium rounded-md transition">
                Register as Vendor
            </button>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        {{-- LOGIN FORM --}}
        <div x-show="tab === 'login'" x-cloak>
            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" class="block mt-1.5 w-full" type="email" name="email" :value="old('email')" autocomplete="username" x-bind:required="tab === 'login'" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" :value="__('Password')" />
                    <x-text-input id="password" class="block mt-1.5 w-full" type="password" name="password" autocomplete="current-password" x-bind:required="tab === 'login'" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-[#1B4332] shadow-sm focus:ring-[#1B4332]" name="remember">
                        <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a class="text-sm text-[#1B4332] hover:underline font-medium" href="{{ route('password.request') }}">
                            {{ __('Forgot password?') }}
                        </a>
                    @endif
                </div>

                <button type="submit" class="w-full bg-[#1B4332] text-white text-sm font-medium py-2.5 rounded-lg hover:bg-[#1B4332]/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1B4332] transition">
                    {{ __('Log In') }}
                </button>
            </form>
        </div>

        {{-- REGISTER FORM --}}
        <div x-show="tab === 'register'" x-cloak>
            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="reg_name" :value="__('Your Name')" />
                        <x-text-input id="reg_name" class="block mt-1.5 w-full" type="text" name="name" :value="old('name')" autocomplete="name" x-bind:required="tab === 'register'" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="business_name" :value="__('Business Name')" />
                        <x-text-input id="business_name" class="block mt-1.5 w-full" type="text" name="business_name" :value="old('business_name')" autocomplete="organization" x-bind:required="tab === 'register'" />
                        <x-input-error :messages="$errors->get('business_name')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="phone" :value="__('Phone Number')" />
                    <x-text-input id="phone" class="block mt-1.5 w-full" type="tel" name="phone" :value="old('phone')" autocomplete="tel" placeholder="254712345678" x-bind:required="tab === 'register'" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="reg_email" :value="__('Email')" />
                    <x-text-input id="reg_email" class="block mt-1.5 w-full" type="email" name="email" :value="old('email')" autocomplete="username" x-bind:required="tab === 'register'" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="reg_password" :value="__('Password')" />
                        <x-text-input id="reg_password" class="block mt-1.5 w-full" type="password" name="password" autocomplete="new-password" x-bind:required="tab === 'register'" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                        <x-text-input id="password_confirmation" class="block mt-1.5 w-full" type="password" name="password_confirmation" autocomplete="new-password" x-bind:required="tab === 'register'" />
                    </div>
                </div>

                <button type="submit" class="w-full bg-[#1B4332] text-white text-sm font-medium py-2.5 rounded-lg hover:bg-[#1B4332]/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1B4332] transition mt-2">
                    {{ __('Create Vendor Account') }}
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
