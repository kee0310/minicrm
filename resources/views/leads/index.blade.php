<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
      {{ __('Leads') }}
    </h2>
  </x-slot>

  @if(session('warning'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
      class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4 transition duration-500 ease-in-out">
      <p>{{ session('warning') }}</p>
    </div>
  @endif

  @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
      class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 transition duration-500 ease-in-out">
      <p>{{ session('success') }}</p>
    </div>
  @endif

  <div class="py-12">
    <div class="mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900" x-data="{
          createOpen: false,
          editOpen: false,
          editLead: null,
          toggleDealFields(prefix) {
            const status = prefix === 'create' ? document.getElementById('create_status')?.value : this.editLead?.status;
            const wrap = document.getElementById(prefix + '_deal_fields');
            if (wrap) wrap.style.display = status === 'Deal' ? '' : 'none';
          }
        }">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium">{{ __('List of leads') }}</h3>

            <div class="flex items-center justify-end">
              <button type="button" @click="createOpen = true; $nextTick(() => toggleDealFields('create'))"
                class="inline-flex items-center px-4 py-2 my-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-wider hover:bg-green-800 focus:bg-green-800 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Create Lead') }}
              </button>
            </div>
          </div>

          <!-- Search and filter form -->
          <div class="mb-4">
            <form method="GET" action="{{ route('leads.index') }}" class="flex items-center space-x-2 text-xs">
              <div>
                <input type="search" name="search" placeholder="Search..." value="{{ request('search') }}"
                  class="w-full rounded-md border-gray-300 shadow-sm px-3 py-2 text-xs" />
              </div>

              <div>
                <select name="status" type="filter" class="rounded-md border-gray-300 shadow-sm px-3 py-2 pr-8 text-xs">
                  <option value="">All Statuses</option>
                  @if(!empty($statuses))
                    @foreach($statuses as $s)
                      <option value="{{ $s }}" @selected(request('status') == $s)>{{ $s }}</option>
                    @endforeach
                  @endif
                </select>
              </div>

              <div>
                <select name="source" type="filter" class="rounded-md border-gray-300 shadow-sm px-3 py-2 pr-8 text-xs">
                  <option value="">All Sources</option>
                  @if(!empty($sources))
                    @foreach($sources as $source)
                      <option value="{{ $source }}" @selected(request('source') == $source)>{{ $source }}</option>
                    @endforeach
                  @endif
                </select>
              </div>

              <div class="flex items-center space-x-2 text-[0.6rem]">
                <button type="submit"
                  class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white uppercase tracking-wider hover:bg-indigo-700 focus:outline-none">Filter</button>
                <a href="{{ route('leads.index') }}"
                  class="inline-flex items-center px-3 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-gray-700 hover:bg-gray-300">Clear</a>
              </div>
            </form>
          </div>

          <div id="live-table-container">
            @if(isset($leads) && $leads->count())
              <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <tr>
                      <th class="px-6 py-3">Name</th>
                      <th class="px-6 py-3">Email</th>
                      <th class="px-6 py-3 ">Phone</th>
                      <th class="px-6 py-3">Source</th>
                      <th class="px-6 py-3">Salesperson</th>
                      <th class="px-6 py-3">Leader</th>
                      <th class="px-6 py-3">Status</th>
                      <th class="px-6 py-3">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200 text-sm text-gray-500 whitespace-nowrap">
                    @foreach($leads as $lead)
                      <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $lead->name }}</td>
                        <td class="px-6 py-4">{{ $lead->email }}</td>
                        <td class="px-6 py-4">{{ $lead->phone }}</td>
                        <td class="px-6 py-4">{{ $lead->source }}</td>
                        <td class="px-6 py-4">{{ $lead->salesperson?->name }}</td>
                        <td class="px-6 py-4">{{ $lead->leader?->name }}</td>
                        <td class="px-6 py-4">
                          <span class="{{ $lead->status->badge() }}">
                            {{ $lead->status->value }}
                          </span>
                        </td>
                        <td class="px-6 py-4">
                          @if($lead->status->value === \App\Enums\LeadStatusEnum::DEAL->value)

                          @else
                            @php
                              $leadPayload = [
                                'id' => $lead->id,
                                'name' => $lead->name,
                                'email' => $lead->email,
                                'phone' => $lead->phone,
                                'source' => $lead->source,
                                'salesperson_id' => $lead->salesperson_id,
                                'leader_id' => $lead->leader_id,
                                'status' => $lead->status?->value,
                                'age' => $lead->client?->age,
                                'ic_passport' => $lead->client?->ic_passport,
                                'occupation' => $lead->client?->occupation,
                                'company' => $lead->client?->company,
                                'monthly_income' => $lead->client?->monthly_income,
                              ];
                            @endphp
                            <button type="button" class="text-indigo-600 hover:underline" data-lead='@json($leadPayload)'
                              @click="editLead = JSON.parse($el.dataset.lead); editOpen = true; $nextTick(() => toggleDealFields('edit'))">Edit</button> |
                            <form method="POST" action="{{ route('leads.destroy', $lead) }}" class="inline"
                              onsubmit="return confirm('Confirm to delete lead {{ $lead->name }}?');">
                              @method('DELETE')
                              @csrf
                              <button type="submit" class="text-red-600 hover:underline">Delete</button>
                            </form>
                          @endif
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>

              <div class="mt-4">
                {{ $leads->links() }}
              </div>
            @else
              <div class="text-gray-600">{{ __('No leads found.') }}</div>
            @endif
          </div>

          <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            @click.self="createOpen = false">
            <div class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl max-h-[90vh] overflow-y-auto">
              <div class="mb-4 flex items-center justify-between">
                <h4 class="text-lg font-semibold">Create Lead</h4>
                <button type="button" class="text-gray-500 hover:text-gray-700" @click="createOpen = false">X</button>
              </div>
              <form method="POST" action="{{ route('leads.store') }}">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div><x-input-label for="create_name" :value="__('Name')" /><x-text-input id="create_name" class="block mt-1 w-full"
                      type="text" name="name" required /></div>
                  <div><x-input-label for="create_email" :value="__('Email')" /><x-text-input id="create_email" class="block mt-1 w-full"
                      type="email" name="email" required /></div>
                  <div><x-input-label for="create_phone" :value="__('Phone')" /><x-text-input id="create_phone" class="block mt-1 w-full"
                      type="text" name="phone" required /></div>
                  <div>
                    <x-input-label for="create_source" :value="__('Source')" />
                    <select id="create_source" name="source" required class="block mt-1 w-full rounded-md border-gray-300">
                      @php
                        $options = ['Facebook', 'Friend Referral', 'Exhibition/Fair', 'Company Assigned', 'Old Client Referral'];
                      @endphp
                      @foreach($options as $source)
                        <option value="{{ $source }}">{{ $source }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div>
                    <x-input-label for="create_salesperson_id" :value="__('Salesperson')" />
                    <select id="create_salesperson_id" name="salesperson_id" required class="block mt-1 w-full rounded-md border-gray-300">
                      <option value="">Select a user</option>
                      @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div>
                    <x-input-label for="create_leader_id" :value="__('Leader')" />
                    <select id="create_leader_id" name="leader_id" class="block mt-1 w-full rounded-md border-gray-300">
                      <option value="">-- None --</option>
                      @foreach($leaders as $leader)
                        <option value="{{ $leader->id }}">{{ $leader->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div>
                    <x-input-label for="create_status" :value="__('Status')" />
                    <select id="create_status" name="status" required @change="toggleDealFields('create')" class="block mt-1 w-full rounded-md border-gray-300">
                      @foreach($statuses as $status)
                        <option value="{{ $status }}">{{ $status }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>

                <div id="create_deal_fields" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div><x-input-label for="create_age" :value="__('Age')" /><x-text-input id="create_age" class="block mt-1 w-full" type="number" min="1" name="age" /></div>
                  <div><x-input-label for="create_ic_passport" :value="__('IC/Passport')" /><x-text-input id="create_ic_passport" class="block mt-1 w-full" type="text" name="ic_passport" /></div>
                  <div><x-input-label for="create_occupation" :value="__('Occupation')" /><x-text-input id="create_occupation" class="block mt-1 w-full" type="text" name="occupation" /></div>
                  <div><x-input-label for="create_company" :value="__('Company')" /><x-text-input id="create_company" class="block mt-1 w-full" type="text" name="company" /></div>
                  <div><x-input-label for="create_monthly_income" :value="__('Monthly Income')" /><x-text-input id="create_monthly_income" class="block mt-1 w-full" type="number" step="0.01" min="0" name="monthly_income" /></div>
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
                <h4 class="text-lg font-semibold">Edit Lead</h4>
                <button type="button" class="text-gray-500 hover:text-gray-700" @click="editOpen = false">X</button>
              </div>
              <form method="POST" :action="'{{ url('leads') }}/' + (editLead?.id ?? '')">
                @method('PUT')
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div><x-input-label for="edit_name" :value="__('Name')" /><x-text-input id="edit_name" class="block mt-1 w-full"
                      type="text" name="name" x-model="editLead.name" required /></div>
                  <div><x-input-label for="edit_email" :value="__('Email')" /><x-text-input id="edit_email" class="block mt-1 w-full"
                      type="email" name="email" x-model="editLead.email" required /></div>
                  <div><x-input-label for="edit_phone" :value="__('Phone')" /><x-text-input id="edit_phone" class="block mt-1 w-full"
                      type="text" name="phone" x-model="editLead.phone" required /></div>
                  <div>
                    <x-input-label for="edit_source" :value="__('Source')" />
                    <select id="edit_source" name="source" x-model="editLead.source" required class="block mt-1 w-full rounded-md border-gray-300">
                      @foreach(['Facebook', 'Friend Referral', 'Exhibition/Fair', 'Company Assigned', 'Old Client Referral'] as $source)
                        <option value="{{ $source }}">{{ $source }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div>
                    <x-input-label for="edit_salesperson_id" :value="__('Salesperson')" />
                    <select id="edit_salesperson_id" name="salesperson_id" x-model="editLead.salesperson_id" required class="block mt-1 w-full rounded-md border-gray-300">
                      <option value="">Select a user</option>
                      @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div>
                    <x-input-label for="edit_leader_id" :value="__('Leader')" />
                    <select id="edit_leader_id" name="leader_id" x-model="editLead.leader_id" class="block mt-1 w-full rounded-md border-gray-300">
                      <option value="">-- None --</option>
                      @foreach($leaders as $leader)
                        <option value="{{ $leader->id }}">{{ $leader->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div>
                    <x-input-label for="edit_status" :value="__('Status')" />
                    <select id="edit_status" name="status" x-model="editLead.status" required @change="toggleDealFields('edit')" class="block mt-1 w-full rounded-md border-gray-300">
                      @foreach($statuses as $status)
                        <option value="{{ $status }}">{{ $status }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <div id="edit_deal_fields" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div><x-input-label for="edit_age" :value="__('Age')" /><x-text-input id="edit_age" class="block mt-1 w-full" type="number" min="1" name="age" x-model="editLead.age" /></div>
                  <div><x-input-label for="edit_ic_passport" :value="__('IC/Passport')" /><x-text-input id="edit_ic_passport" class="block mt-1 w-full" type="text" name="ic_passport" x-model="editLead.ic_passport" /></div>
                  <div><x-input-label for="edit_occupation" :value="__('Occupation')" /><x-text-input id="edit_occupation" class="block mt-1 w-full" type="text" name="occupation" x-model="editLead.occupation" /></div>
                  <div><x-input-label for="edit_company" :value="__('Company')" /><x-text-input id="edit_company" class="block mt-1 w-full" type="text" name="company" x-model="editLead.company" /></div>
                  <div><x-input-label for="edit_monthly_income" :value="__('Monthly Income')" /><x-text-input id="edit_monthly_income" class="block mt-1 w-full" type="number" step="0.01" min="0" name="monthly_income" x-model="editLead.monthly_income" /></div>
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
