<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
      {{ __('Leads / Edit') }}
    </h2>
  </x-slot>

  <div class="py-12">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8" align="center">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-10 max-w-2xl m-5" align="left">

        <form method="POST" action="{{ route('leads.update', $lead) }}">
          @method('PUT')
          @csrf

          <!-- Name -->
          <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $lead->name)"
              required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
          </div>

          <!-- Email Address -->
          <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $lead->email)" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
          </div>

          <!-- Phone -->
          <div class="mt-4">
            <x-input-label for="phone" :value="__('Phone')" />
            <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone', $lead->phone)" required autocomplete="phone" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
          </div>

          <!-- Source -->
          <div class="mt-4">
            <x-input-label for="source" :value="__('Source')" />

            <select id="source" name="source"
              class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
              required>
              @php
                $options = [
                  'Facebook' => 'Facebook',
                  'Friend Referral' => 'Friend Referral',
                  'Exhibition/Fair' => 'Exhibition/Fair',
                  'Company Assigned' => 'Company Assigned',
                  'Old Client Referral' => 'Old Client Referral',
                ];
              @endphp

              @foreach($options as $value => $label)
                <option value="{{ $value }}" {{ old('source', $lead->source) == $value ? 'selected' : '' }}>
                  {{ $label }}
                </option>
              @endforeach
            </select>

            <x-input-error :messages="$errors->get('source')" class="mt-2" />
          </div>

          <!-- Salesperson -->
          <div class="mt-4">
            <x-input-label for="salesperson_id" :value="__('Salesperson')" />
            <select id="salesperson_id" name="salesperson_id"
              class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
              required>
              <option value="">Select a user</option>
              @foreach($users as $user)
                <option value="{{ $user->id }}" {{ old('salesperson_id', $lead->salesperson_id) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
              @endforeach
            </select>
            <x-input-error :messages="$errors->get('salesperson_id')" class="mt-2" />
          </div>

          <!-- Leader -->
          <div class="mt-4">
            <x-input-label for="leader_id" :value="__('Leader')" />
            <select id="leader_id" name="leader_id"
              class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
              <option value="">-- None --</option>
              @foreach($leaders as $user)
                <option value="{{ $user->id }}" {{ old('leader_id', $lead->leader_id) == $user->id ? 'selected' : '' }}>
                  {{ $user->name }}
                </option>
              @endforeach
            </select>
            <x-input-error :messages="$errors->get('leader_id')" class="mt-2" />
          </div>

          <!-- Status -->
          <div class="mt-4">
            <x-input-label for="status" :value="__('Status')" />

            <select id="status" name="status"
              class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
              required>
              @foreach($statuses as $status)
                <option value="{{ $status }}" {{ old('status', $lead->status?->value) == $status ? 'selected' : '' }}>
                  {{ $status }}
                </option>
              @endforeach
            </select>

            <x-input-error :messages="$errors->get('status')" class="mt-2" />
          </div>

          <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ms-4 bg-green-600 hover:bg-green-800">
              {{ __('Save') }}
            </x-primary-button>
          </div>
        </form>

      </div>
    </div>
  </div>
</x-app-layout>

