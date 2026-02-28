<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
      {{ __('Deals / Create') }}
    </h2>
  </x-slot>

  <div class="py-12">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8" align="center">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-10 max-w-2xl m-5" align="left">

        <form method="POST" action="{{ route('deals.store') }}">
          @csrf
          <!-- Pipeline Stage -->
          <div>
            <x-input-label for="pipeline" :value="__('Pipeline Stage')" />
            <select id="pipeline" name="pipeline" required
              class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
              @foreach($pipelines as $pipeline)
                <option value="{{ $pipeline->value }}" {{ old('pipeline') == $pipeline->value ? 'selected' : '' }}>
                  {{ $pipeline->value }}
                </option>
              @endforeach
            </select>
            <x-input-error :messages="$errors->get('pipeline')" class="mt-2" />
          </div>

          <!-- Linked Client -->
          <div class="mt-4">
            <x-input-label for="client_id" :value="__('Linked Client')" />
            <select id="client_id" name="client_id" required
              class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
              <option value="">Select a client</option>
              @foreach($clients as $client)
                <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                  {{ $client->client_id ? $client->client_id . ' - ' : '' }}{{ $client->name }}
                </option>
              @endforeach
            </select>
            <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
          </div>

          <!-- Project Name -->
          <div class="mt-4">
            <x-input-label for="project_name" :value="__('Project Name')" />
            <x-text-input id="project_name" class="block mt-1 w-full" type="text" name="project_name"
              :value="old('project_name')" required />
            <x-input-error :messages="$errors->get('project_name')" class="mt-2" />
          </div>

          <!-- Developer -->
          <div class="mt-4">
            <x-input-label for="developer" :value="__('Developer')" />
            <x-text-input id="developer" class="block mt-1 w-full" type="text" name="developer"
              :value="old('developer')" />
            <x-input-error :messages="$errors->get('developer')" class="mt-2" />
          </div>

          <!-- Unit Number -->
          <div class="mt-4">
            <x-input-label for="unit_number" :value="__('Unit Number')" />
            <x-text-input id="unit_number" class="block mt-1 w-full" type="text" name="unit_number"
              :value="old('unit_number')" />
            <x-input-error :messages="$errors->get('unit_number')" class="mt-2" />
          </div>

          <!-- Selling Price -->
          <div class="mt-4">
            <x-input-label for="selling_price" :value="__('Selling Price')" />
            <x-text-input id="selling_price" class="block mt-1 w-full" type="number" step="0.01" name="selling_price"
              :value="old('selling_price')" required />
            <x-input-error :messages="$errors->get('selling_price')" class="mt-2" />
          </div>

          <!-- Commission % -->
          <div class="mt-4">
            <x-input-label for="commission_percentage" :value="__('Commission %')" />
            <x-text-input id="commission_percentage" class="block mt-1 w-full" type="number" step="0.01"
              name="commission_percentage" :value="old('commission_percentage')" required />
            <x-input-error :messages="$errors->get('commission_percentage')" class="mt-2" />
          </div>

          <!-- Commission Amount (auto) -->
          <div class="mt-4">
            <x-input-label for="commission_amount" :value="__('Commission Amount')" />
            <x-text-input id="commission_amount" class="block mt-1 w-full bg-gray-100" type="number" step="0.01"
              name="commission_amount" :value="old('commission_amount')" readonly />
          </div>

          <!-- Booking Fee -->
          <div class="mt-4">
            <x-input-label for="booking_fee" :value="__('Booking Fee')" />
            <x-text-input id="booking_fee" class="block mt-1 w-full" type="number" step="0.01" name="booking_fee"
              :value="old('booking_fee')" />
            <x-input-error :messages="$errors->get('booking_fee')" class="mt-2" />
          </div>

          <!-- SPA Date -->
          <div class="mt-4" id="spa_date_group">
            <x-input-label for="spa_date" :value="__('SPA Date')" />
            <x-text-input id="spa_date" class="block mt-1 w-full" type="date" name="spa_date"
              :value="old('spa_date')" />
            <x-input-error :messages="$errors->get('spa_date')" class="mt-2" />
          </div>

          <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ms-4 bg-green-600 hover:bg-green-800">
              {{ __('Save Deal') }}
            </x-primary-button>
          </div>
        </form>
        <script>
          const priceInput = document.getElementById('selling_price');
          const percentInput = document.getElementById('commission_percentage');
          const amountInput = document.getElementById('commission_amount');

          function recalc() {
            const price = parseFloat(priceInput.value) || 0;
            const pct = parseFloat(percentInput.value) || 0;
            amountInput.value = (price * pct / 100).toFixed(2);
          }

          const pipelineInput = document.getElementById('pipeline');
          const spaDateGroup = document.getElementById('spa_date_group');
          const spaDateInput = document.getElementById('spa_date');

          function toggleSpaDate() {
            const requiresSpaDate = pipelineInput.value === 'SPA Signed';
            spaDateGroup.style.display = requiresSpaDate ? '' : 'none';
            spaDateInput.required = requiresSpaDate;
          }

          priceInput.addEventListener('input', recalc);
          percentInput.addEventListener('input', recalc);
          pipelineInput.addEventListener('change', toggleSpaDate);
          toggleSpaDate();
        </script>

      </div>
    </div>
  </div>
</x-app-layout>
