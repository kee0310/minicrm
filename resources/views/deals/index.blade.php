<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
      {{ __('Deals') }}
    </h2>
  </x-slot>

  <div class="py-12">
    <div class="max-w-7x1 mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium">{{ __('List of deals') }}</h3>

            <div class="flex items-center justify-end">
              <a href="{{ route('deals.create') }}"
                class="inline-flex items-center px-4 py-2 my-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-wider hover:bg-green-800 focus:bg-green-800 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Create Deal') }}
              </a>
            </div>
          </div>

          <div class="mb-4">
            <form method="GET" action="{{ route('deals.index') }}" class="flex items-center space-x-2 text-xs">
              <div>
                <input type="search" name="search" placeholder="Search lead" value="{{ request('search') }}"
                  class="w-full rounded-md border-gray-300 shadow-sm px-3 py-2 text-xs" />
              </div>
              <div class="flex items-center space-x-2 text-[0.6rem]">
                <button type="submit"
                  class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white uppercase tracking-wider hover:bg-indigo-700 focus:outline-none">Search</button>
                <a href="{{ route('deals.index') }}"
                  class="inline-flex items-center px-3 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-gray-700 hover:bg-gray-300">Clear</a>
              </div>
            </form>
          </div>

          @if(isset($deals) && $deals->count())
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  <tr>
                    <th class="px-6 py-3">Deal ID</th>
                    <th class="px-6 py-3">Lead</th>
                    <th class="px-6 py-3">Project</th>
                    <th class="px-6 py-3">Selling Price</th>
                    <th class="px-6 py-3">Commission</th>
                    <th class="px-6 py-3">Stage</th>
                    <th class="px-6 py-3">Salesperson</th>
                    <th class="px-6 py-3">Created</th>
                    <th class="px-6 py-3">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 text-sm text-gray-500 whitespace-nowrap">
                  @foreach($deals as $deal)
                    <tr>
                      <td class="px-6 py-4 text-gray-900">{{ $deal->deal_id }}</td>
                      <td class="px-6 py-4">{{ $deal->lead->name }}</td>
                      <td class="px-6 py-4">{{ $deal->project_name }}</td>
                      <td class="px-6 py-4">{{ number_format($deal->selling_price,2) }}</td>
                      <td class="px-6 py-4">{{ number_format($deal->commission_amount,2) }}</td>
                      <td class="px-6 py-4">{{ $deal->pipeline->name }}</td>
                      <td class="px-6 py-4">{{ $deal->salesperson->name }}</td>
                      <td class="px-6 py-4">{{ optional($deal->created_at)->format('Y-m-d') }}</td>
                      <td class="px-6 py-4">
                        <a href="{{ route('deals.edit', $deal) }}" class="text-indigo-600 hover:underline">Edit</a> |
                        <form method="POST" action="{{ route('deals.destroy', $deal) }}" class="inline"
                          onsubmit="return confirm('Confirm to delete deal {{ $deal->deal_id }}?');">
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
              {{ $deals->links() }}
            </div>
          @else
            <div class="text-gray-600">{{ __('No deals found.') }}</div>
          @endif

        </div>
      </div>
    </div>
  </div>
</x-app-layout>
