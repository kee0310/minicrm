<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
      {{ __('Users') }}
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
        <div class="p-6 text-gray-900" x-data="{ createOpen: false, editOpen: false, createRole: '', editUser: null, searchTerm: '', roleFilter: '' }">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium">{{ __('List of users') }}</h3>

            <div class="flex items-center justify-end">
              <button type="button" @click="createOpen = true; createRole = ''"
                class="inline-flex items-center px-4 py-2 my-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-wider hover:bg-green-800 focus:bg-green-800 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Create User') }}
              </button>
            </div>
          </div>

          <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
            <input type="text" x-model.debounce.300ms="searchTerm" placeholder="Search name or email..."
              class="w-full sm:max-w-sm rounded-md border-gray-300" />
            <select x-model="roleFilter" class="w-full sm:w-48 rounded-md border-gray-300">
              <option value="">All Roles</option>
              @foreach($roles as $role)
                <option value="{{ $role }}">{{ $role }}</option>
              @endforeach
            </select>
          </div>

          <div id="live-table-container">
            @if(isset($users) && $users->count())
              <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <tr>
                      <th class="px-6 py-3">Name</th>
                      <th class="px-6 py-3">Email</th>
                      <th class="px-6 py-3">Role</th>
                      <th class="px-6 py-3 ">Created</th>
                      <th class="px-6 py-3">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200 text-sm text-gray-500 whitespace-nowrap">
                    @foreach($users as $user)
                      <tr
                        x-show="((('{{ strtolower((string) ($user->name ?? '')) }}' + ' {{ strtolower((string) ($user->email ?? '')) }}').includes((searchTerm || '').toLowerCase()))) && ((!roleFilter) || ('{{ $user->getRoleNames()->first() ?? '' }}' === roleFilter))">
                        <td class="px-6 py-4 text-gray-900">{{ $user->name }}</td>
                        <td class="px-6 py-4">{{ $user->email }}</td>
                        <td class="px-6 py-4">{{ $user->getRoleNames()->join(', ') }}</td>
                        <td class="px-6 py-4">{{ optional($user->created_at)->format('Y-m-d') }}</td>
                        <td class="px-6 py-4">
                          @php
                            $userPayload = [
                              'id' => $user->id,
                              'name' => $user->name,
                              'email' => $user->email,
                              'role' => $user->getRoleNames()->first(),
                              'leader_id' => $user->leader_id,
                            ];
                          @endphp
                          <button type="button" class="text-indigo-600 hover:underline" data-user='@json($userPayload)'
                            @click="editUser = JSON.parse($el.dataset.user); editOpen = true">Edit</button> |
                          <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline"
                            onsubmit="return confirm('Confirm to delete user {{ $user->name }}?');">
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
                {{ $users->links() }}
              </div>
            @else
              <div class="text-gray-600">{{ __('No users found.') }}</div>
            @endif
          </div>

          <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            @click.self="createOpen = false">
            <div class="w-full max-w-xl rounded-lg bg-white p-6 shadow-xl">
              <div class="mb-4 flex items-center justify-between">
                <h4 class="text-lg font-semibold">Create User</h4>
                <button type="button" class="text-gray-500 hover:text-gray-700" @click="createOpen = false">X</button>
              </div>
              <form method="POST" action="{{ route('users.store') }}">
                @csrf
                <div class="grid grid-cols-1 gap-3">
                  <div><x-input-label for="create_user_name" :value="__('Name')" /><x-text-input id="create_user_name"
                      class="block mt-1 w-full" type="text" name="name" :value="old('name')" required /></div>
                  <div><x-input-label for="create_user_email" :value="__('Email')" /><x-text-input id="create_user_email"
                      class="block mt-1 w-full" type="email" name="email" :value="old('email')" required /></div>
                  <div><x-input-label for="create_user_password" :value="__('Password')" /><x-text-input
                      id="create_user_password" class="block mt-1 w-full" type="password" name="password" required />
                  </div>
                  <div><x-input-label for="create_user_password_confirmation" :value="__('Confirm Password')" /><x-text-input
                      id="create_user_password_confirmation" class="block mt-1 w-full" type="password"
                      name="password_confirmation" required /></div>
                  <div>
                    <x-input-label for="create_user_role" :value="__('Role')" />
                    <select id="create_user_role" name="role" x-model="createRole" required
                      class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                      <option value="">Select a role</option>
                      @foreach($roles as $role)
                        <option value="{{ $role }}">{{ $role }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div x-show="createRole === '{{ \App\Enums\RoleEnum::SALESPERSON->value }}'" x-cloak>
                    <x-input-label for="create_user_leader_id" :value="__('Leader')" />
                    <select id="create_user_leader_id" name="leader_id" :required="createRole === '{{ \App\Enums\RoleEnum::SALESPERSON->value }}'"
                      class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                      <option value="">Select leader</option>
                      @foreach($leaders as $leader)
                        <option value="{{ $leader->id }}">{{ $leader->name }}</option>
                      @endforeach
                    </select>
                  </div>
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
            <div class="w-full max-w-xl rounded-lg bg-white p-6 shadow-xl">
              <div class="mb-4 flex items-center justify-between">
                <h4 class="text-lg font-semibold">Edit User</h4>
                <button type="button" class="text-gray-500 hover:text-gray-700" @click="editOpen = false">X</button>
              </div>
              <form method="POST" :action="'{{ url('users') }}/' + (editUser?.id ?? '')">
                @method('PUT')
                @csrf
                <div class="grid grid-cols-1 gap-3">
                  <div><x-input-label for="edit_user_name" :value="__('Name')" /><x-text-input id="edit_user_name"
                      class="block mt-1 w-full" type="text" name="name" x-model="editUser.name" required /></div>
                  <div><x-input-label for="edit_user_email" :value="__('Email')" /><x-text-input id="edit_user_email"
                      class="block mt-1 w-full" type="email" name="email" x-model="editUser.email" required /></div>
                  <div>
                    <x-input-label for="edit_user_role" :value="__('Role')" />
                    <select id="edit_user_role" name="role" x-model="editUser.role" required
                      class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                      <option value="">Select a role</option>
                      @foreach($roles as $role)
                        <option value="{{ $role }}">{{ $role }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div x-show="editUser?.role === '{{ \App\Enums\RoleEnum::SALESPERSON->value }}'" x-cloak>
                    <x-input-label for="edit_user_leader_id" :value="__('Leader')" />
                    <select id="edit_user_leader_id" name="leader_id" x-model="editUser.leader_id"
                      :required="editUser?.role === '{{ \App\Enums\RoleEnum::SALESPERSON->value }}'"
                      class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                      <option value="">Select leader</option>
                      @foreach($leaders as $leader)
                        <option value="{{ $leader->id }}">{{ $leader->name }}</option>
                      @endforeach
                    </select>
                  </div>
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
