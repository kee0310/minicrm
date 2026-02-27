<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
      {{ __('Clients / Edit') }}
    </h2>
  </x-slot>

  <div class="py-12">
    <div class="max-w-7x1 mx-auto sm:px-6 lg:px-8" align="center">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-10 max-w-3xl m-5" align="left">
        <form method="POST" action="{{ route('clients.update', $client) }}">
          @method('PUT')
          @csrf

          <h3 class="text-lg font-medium text-gray-900 mb-4">Profile</h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <x-input-label for="client_id" :value="__('Client ID')" />
              <x-text-input id="client_id" class="block mt-1 w-full bg-gray-100" type="text" :value="$client->client_id ?? '-'" readonly />
            </div>

            <div>
              <x-input-label for="name" :value="__('Name')" />
              <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $client->name)" required />
              <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
              <x-input-label for="email" :value="__('Email')" />
              <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $client->email)" required />
              <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
              <x-input-label for="phone" :value="__('Phone')" />
              <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone', $client->phone)" required />
              <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>

            <div>
              <x-input-label for="age" :value="__('Age')" />
              <x-text-input id="age" class="block mt-1 w-full" type="number" name="age" :value="old('age', $client->age)" />
              <x-input-error :messages="$errors->get('age')" class="mt-2" />
            </div>

            <div>
              <x-input-label for="ic_passport" :value="__('IC/Passport')" />
              <x-text-input id="ic_passport" class="block mt-1 w-full" type="text" name="ic_passport" :value="old('ic_passport', $client->ic_passport)" />
              <x-input-error :messages="$errors->get('ic_passport')" class="mt-2" />
            </div>

            <div>
              <x-input-label for="occupation" :value="__('Occupation')" />
              <x-text-input id="occupation" class="block mt-1 w-full" type="text" name="occupation" :value="old('occupation', $client->occupation)" />
              <x-input-error :messages="$errors->get('occupation')" class="mt-2" />
            </div>

            <div>
              <x-input-label for="company" :value="__('Company')" />
              <x-text-input id="company" class="block mt-1 w-full" type="text" name="company" :value="old('company', $client->company)" />
              <x-input-error :messages="$errors->get('company')" class="mt-2" />
            </div>

            <div>
              <x-input-label for="monthly_income" :value="__('Monthly Income')" />
              <x-text-input id="monthly_income" class="block mt-1 w-full" type="number" step="0.01" name="monthly_income"
                :value="old('monthly_income', $client->monthly_income)" />
              <x-input-error :messages="$errors->get('monthly_income')" class="mt-2" />
            </div>
          </div>

          <div class="flex items-center justify-end mt-6">
            <x-primary-button class="ms-4 bg-green-600 hover:bg-green-800">
              {{ __('Save Client') }}
            </x-primary-button>
          </div>
        </form>
      </div>
    </div>
  </div>
</x-app-layout>
