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

  <div class="py-12">
    <div class="max-w-7x1 mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium">{{ __('List of clients') }}</h3>
            <a href="{{ route('clients.create') }}"
              class="inline-flex items-center px-4 py-2 my-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-wider hover:bg-green-800 focus:bg-green-800 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
              {{ __('Create Client') }}
            </a>
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
                    <th class="px-6 py-3">Status</th>
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
                      <td class="px-6 py-4">{{ $client->lead?->salesperson?->name }}</td>
                      <td class="px-6 py-4">{{ $client->lead?->leader?->name }}</td>
                      <td class="px-6 py-4">{{ $client->status }}</td>
                      <td class="px-6 py-4">
                        <a href="{{ route('clients.edit', $client) }}" class="text-indigo-600 hover:underline">Edit</a> |
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
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
