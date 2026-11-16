<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Tipo de cuenta -->
        <fieldset class="mt-4">
            <legend class="block font-medium text-sm text-gray-700">Quiero registrarme como</legend>
            <div class="mt-2 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-gray-700">
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="account_type" value="alumno" class="text-indigo-600 border-gray-300 focus:ring-indigo-500" @checked(old('account_type', 'alumno') === 'alumno')>
                    Alumno
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="account_type" value="profesor" class="text-indigo-600 border-gray-300 focus:ring-indigo-500" @checked(old('account_type') === 'profesor')>
                    Profesor
                </label>
            </div>
            <x-input-error :messages="$errors->get('account_type')" class="mt-2" />
        </fieldset>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
