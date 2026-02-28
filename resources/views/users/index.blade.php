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
        <div class="p-6 text-gray-900">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium">{{ __('List of users') }}</h3>

            <div class="flex items-center justify-end">
              <a href="{{ route('users.create') }}"
                class="inline-flex items-center px-4 py-2 my-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-wider hover:bg-green-800 focus:bg-green-800 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Create User') }}
              </a>
            </div>
          </div>

          <!-- Search form -->
          <div class="mb-4">
            <form method="GET" action="{{ route('users.index') }}" class="flex items-center space-x-2 text-xs">
              <div>
                <input type="search" name="search" placeholder="Search" value="{{ request('search') }}"
                  class="w-full rounded-md border-gray-300 shadow-sm px-3 py-2 text-xs" />
              </div>


              <div class="flex items-center space-x-2 text-[0.6rem]">
                <button type="submit"
                  class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white uppercase tracking-wider hover:bg-indigo-700 focus:outline-none">Search</button>
                <a href="{{ route('users.index') }}"
                  class="inline-flex items-center px-3 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-gray-700 hover:bg-gray-300">Clear</a>
              </div>
            </form>
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
                      <tr>
                        <td class="px-6 py-4 text-gray-900">{{ $user->name }}</td>
                        <td class="px-6 py-4">{{ $user->email }}</td>
                        <td class="px-6 py-4">{{ $user->getRoleNames()->join(', ') }}</td>
                        <td class="px-6 py-4">{{ optional($user->created_at)->format('Y-m-d') }}</td>
                        <td class="px-6 py-4">
                          <a href="{{ route('users.edit', $user) }}" class="text-indigo-600 hover:underline">Edit</a> |
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

        </div>
      </div>
    </div>
  </div>
</x-app-layout>