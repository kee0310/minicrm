<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
      {{ __('Clients') }}
    </h2>
  </x-slot>

  @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
      class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 transition duration-500 ease-in-out">
      <p>{{ session('success') }}</p>
    </div>
  @endif

  @if(session('warning'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
      class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4 transition duration-500 ease-in-out">
      <p>{{ session('warning') }}</p>
    </div>
  @endif

  <div class="py-12">
    <div class="mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900" x-data="{ createOpen: false, editOpen: false, editClient: null }">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium">{{ __('List of clients') }}</h3>
            <button type="button" @click="createOpen = true"
              class="inline-flex items-center px-4 py-2 my-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-wider hover:bg-green-800 focus:bg-green-800 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
              {{ __('Create Client') }}
            </button>
          </div>

          <div class="mb-4">
            <form method="GET" action="{{ route('clients.index') }}" class="flex items-center space-x-2 text-xs">
              <div>
                <input type="search" name="search" placeholder="Search..." value="{{ request('search') }}"
                  class="w-full rounded-md border-gray-300 shadow-sm px-3 py-2 text-xs" />
              </div>

              <div class="flex items-center space-x-2 text-[0.6rem]">
                <button type="submit"
                  class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white uppercase tracking-wider hover:bg-indigo-700 focus:outline-none">Filter</button>
                <a href="{{ route('clients.index') }}"
                  class="inline-flex items-center px-3 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-gray-700 hover:bg-gray-300">Clear</a>
              </div>
            </form>
          </div>

          @if($clients->count())
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  <tr>
                    <th class="px-6 py-3">Client ID</th>
                    <th class="px-6 py-3">Name</th>
                    <th class="px-6 py-3">Email</th>
                    <th class="px-6 py-3">Phone</th>
                    <th class="px-6 py-3">Salesperson</th>
                    <th class="px-6 py-3">Leader</th>
                    <th class="px-6 py-3">Data Completeness</th>
                    <th class="px-6 py-3">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 text-sm text-gray-500 whitespace-nowrap">
                  @foreach($clients as $client)
                    <tr>
                      <td class="px-6 py-4 text-gray-900">{{ $client->client_id ?? '-' }}</td>
                      <td class="px-6 py-4 text-gray-900">
                        <a href="{{ route('clients.show', $client) }}" class="text-indigo-600 hover:underline">
                          {{ $client->name }}
                        </a>
                      </td>
                      <td class="px-6 py-4">{{ $client->email }}</td>
                      <td class="px-6 py-4">{{ $client->phone }}</td>
                      <td class="px-6 py-4">{{ $client->salesperson ? $client->salesperson->name : '-' }}</td>
                      <td class="px-6 py-4">{{ $client->leader ? $client->leader->name : '-' }}</td>
                      <td class="px-6 py-4">
                        {{ is_null($client->completeness_rate) ? '-' : $client->completeness_rate . '%' }}
                      </td>
                      <td class="px-6 py-4">
                        @php
                          $clientPayload = [
                            'id' => $client->id,
                            'name' => $client->name,
                            'email' => $client->email,
                            'phone' => $client->phone,
                            'salesperson_id' => $client->salesperson_id,
                            'leader_id' => $client->leader_id,
                            'age' => $client->age,
                            'ic_passport' => $client->ic_passport,
                            'occupation' => $client->occupation,
                            'company' => $client->company,
                            'monthly_income' => $client->monthly_income,
                          ];
                        @endphp
                        <button type="button" class="text-indigo-600 hover:underline" data-client='@json($clientPayload)'
                          @click="editClient = JSON.parse($el.dataset.client); editOpen = true">Edit</button> |
                        <form method="POST" action="{{ route('clients.destroy', $client) }}" class="inline"
                          onsubmit="return confirm('Confirm to delete client {{ $client->name }}?');">
                          @method('DELETE')
                          @csrf
                          <button type="submit" class="text-red-600 hover:underline">Delete</button>
                        </form>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            <div class="mt-4">
              {{ $clients->links() }}
            </div>
          @else
            <div class="text-gray-600">{{ __('No clients found.') }}</div>
          @endif

          <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            @click.self="createOpen = false">
            <div class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl max-h-[90vh] overflow-y-auto">
              <div class="mb-4 flex items-center justify-between">
                <h4 class="text-lg font-semibold">Create Client</h4>
                <button type="button" class="text-gray-500 hover:text-gray-700" @click="createOpen = false">X</button>
              </div>
              <form method="POST" action="{{ route('clients.store') }}">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div><x-input-label for="create_client_name" :value="__('Name')" /><x-text-input id="create_client_name" class="block mt-1 w-full" type="text" name="name" required /></div>
                  <div><x-input-label for="create_client_email" :value="__('Email')" /><x-text-input id="create_client_email" class="block mt-1 w-full" type="email" name="email" required /></div>
                  <div><x-input-label for="create_client_phone" :value="__('Phone')" /><x-text-input id="create_client_phone" class="block mt-1 w-full" type="text" name="phone" required /></div>
                  <div><x-input-label for="create_client_salesperson_id" :value="__('Salesperson')" />
                    <select id="create_client_salesperson_id" name="salesperson_id" required class="block mt-1 w-full rounded-md border-gray-300">
                      <option value="">Select salesperson</option>
                      @foreach($salespersons as $salesperson)
                        <option value="{{ $salesperson->id }}">{{ $salesperson->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div><x-input-label for="create_client_leader_id" :value="__('Leader')" />
                    <select id="create_client_leader_id" name="leader_id" required class="block mt-1 w-full rounded-md border-gray-300">
                      <option value="">Select leader</option>
                      @foreach($leaders as $leader)
                        <option value="{{ $leader->id }}">{{ $leader->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div><x-input-label for="create_client_age" :value="__('Age')" /><x-text-input id="create_client_age" class="block mt-1 w-full" type="number" name="age" /></div>
                  <div><x-input-label for="create_client_ic_passport" :value="__('IC/Passport')" /><x-text-input id="create_client_ic_passport" class="block mt-1 w-full" type="text" name="ic_passport" /></div>
                  <div><x-input-label for="create_client_occupation" :value="__('Occupation')" /><x-text-input id="create_client_occupation" class="block mt-1 w-full" type="text" name="occupation" /></div>
                  <div><x-input-label for="create_client_company" :value="__('Company')" /><x-text-input id="create_client_company" class="block mt-1 w-full" type="text" name="company" /></div>
                  <div><x-input-label for="create_client_monthly_income" :value="__('Monthly Income')" /><x-text-input id="create_client_monthly_income" class="block mt-1 w-full" type="number" step="0.01" name="monthly_income" /></div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                  <button type="button" @click="createOpen = false" class="px-4 py-2 bg-gray-200 rounded-md">Cancel</button>
                  <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md">Create</button>
                </div>
              </form>
            </div>
          </div>

          <div x-show="editOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            @click.self="editOpen = false">
            <div class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl max-h-[90vh] overflow-y-auto">
              <div class="mb-4 flex items-center justify-between">
                <h4 class="text-lg font-semibold">Edit Client</h4>
                <button type="button" class="text-gray-500 hover:text-gray-700" @click="editOpen = false">X</button>
              </div>
              <form method="POST" :action="'{{ url('clients') }}/' + (editClient?.id ?? '')">
                @method('PUT')
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div><x-input-label for="edit_client_name" :value="__('Name')" /><x-text-input id="edit_client_name" class="block mt-1 w-full" type="text" name="name" x-model="editClient.name" required /></div>
                  <div><x-input-label for="edit_client_email" :value="__('Email')" /><x-text-input id="edit_client_email" class="block mt-1 w-full" type="email" name="email" x-model="editClient.email" required /></div>
                  <div><x-input-label for="edit_client_phone" :value="__('Phone')" /><x-text-input id="edit_client_phone" class="block mt-1 w-full" type="text" name="phone" x-model="editClient.phone" required /></div>
                  <div><x-input-label for="edit_client_salesperson_id" :value="__('Salesperson')" />
                    <select id="edit_client_salesperson_id" name="salesperson_id" x-model="editClient.salesperson_id" required class="block mt-1 w-full rounded-md border-gray-300">
                      <option value="">Select salesperson</option>
                      @foreach($salespersons as $salesperson)
                        <option value="{{ $salesperson->id }}">{{ $salesperson->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div><x-input-label for="edit_client_leader_id" :value="__('Leader')" />
                    <select id="edit_client_leader_id" name="leader_id" x-model="editClient.leader_id" required class="block mt-1 w-full rounded-md border-gray-300">
                      <option value="">Select leader</option>
                      @foreach($leaders as $leader)
                        <option value="{{ $leader->id }}">{{ $leader->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div><x-input-label for="edit_client_age" :value="__('Age')" /><x-text-input id="edit_client_age" class="block mt-1 w-full" type="number" name="age" x-model="editClient.age" /></div>
                  <div><x-input-label for="edit_client_ic_passport" :value="__('IC/Passport')" /><x-text-input id="edit_client_ic_passport" class="block mt-1 w-full" type="text" name="ic_passport" x-model="editClient.ic_passport" /></div>
                  <div><x-input-label for="edit_client_occupation" :value="__('Occupation')" /><x-text-input id="edit_client_occupation" class="block mt-1 w-full" type="text" name="occupation" x-model="editClient.occupation" /></div>
                  <div><x-input-label for="edit_client_company" :value="__('Company')" /><x-text-input id="edit_client_company" class="block mt-1 w-full" type="text" name="company" x-model="editClient.company" /></div>
                  <div><x-input-label for="edit_client_monthly_income" :value="__('Monthly Income')" /><x-text-input id="edit_client_monthly_income" class="block mt-1 w-full" type="number" step="0.01" name="monthly_income" x-model="editClient.monthly_income" /></div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                  <button type="button" @click="editOpen = false" class="px-4 py-2 bg-gray-200 rounded-md">Cancel</button>
                  <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md">Save</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
