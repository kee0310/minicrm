<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
      {{ __('Client / Profile') }}
    </h2>
  </x-slot>

  <div class="py-12">
    <div class="mx-auto sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 lg:sticky lg:top-6 self-start">
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Client Profile</h3>

            <div class="space-y-2 text-sm text-gray-700">
              <p><span class="font-semibold">Client ID:</span> {{ $client->client_id ?? '-' }}</p>
              <p><span class="font-semibold">Name:</span> {{ $client->name ?? '-' }}</p>
              <p><span class="font-semibold">Email:</span> {{ $client->email ?? '-' }}</p>
              <p><span class="font-semibold">Phone:</span> {{ $client->phone ?? '-' }}</p>
              <p><span class="font-semibold">Age:</span> {{ $client->age ?? '-' }}</p>
              <p><span class="font-semibold">IC/Passport:</span> {{ $client->ic_passport ?? '-' }}</p>
              <p><span class="font-semibold">Occupation:</span> {{ $client->occupation ?? '-' }}</p>
              <p><span class="font-semibold">Company:</span> {{ $client->company ?? '-' }}</p>
              <p><span class="font-semibold">Monthly Income:</span> {{ $client->monthly_income ?? '-' }}</p>
              <p><span class="font-semibold">Status:</span> {{ $client->status ?? '-' }}</p>
            </div>
          </div>
        </div>

        <div class="lg:col-span-2">
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Deals</h3>

            @if($deals->count())
              <div class="space-y-4">
                @foreach($deals as $deal)
                  <div class="rounded-lg border border-gray-200 p-4 my-2">
                    <div class="flex items-start justify-between">
                      <div>
                        <p class="text-sm text-gray-500">Deal ID</p>
                        <p class="font-semibold text-gray-900">{{ $deal->deal_id }}</p>
                      </div>
                      <span class="{{ $deal->pipeline->badge() }}">
                        {{ $deal->pipeline->value }}
                      </span>
                    </div>

                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-y-2 gap-x-4 text-sm text-gray-700">
                      <p><span class="font-semibold">Project:</span> {{ $deal->project_name }}</p>
                      <p><span class="font-semibold">Developer:</span> {{ $deal->developer ?? '-' }}</p>
                      <p><span class="font-semibold">Unit Number:</span> {{ $deal->unit_number ?? '-' }}</p>
                      <p><span class="font-semibold">Selling Price:</span> {{ number_format($deal->selling_price, 2) }}</p>
                      <p><span class="font-semibold">Commission %:</span>
                        {{ number_format($deal->commission_percentage, 2) }}</p>
                      <p><span class="font-semibold">Commission Amount:</span>
                        {{ number_format($deal->commission_amount, 2) }}</p>
                      <p><span class="font-semibold">Booking Fee:</span>
                        {{ $deal->booking_fee ? number_format($deal->booking_fee, 2) : '-' }}</p>
                      <p><span class="font-semibold">SPA Date:</span>
                        {{ optional($deal->spa_date)->format('Y-m-d') ?? '-' }}</p>
                      <p><span class="font-semibold">Deal Closing Date:</span>
                        {{ optional($deal->deal_closing_date)->format('Y-m-d') ?? '-' }}</p>
                      <p><span class="font-semibold">Salesperson:</span> {{ $deal->salesperson?->name ?? '-' }}</p>
                      <p><span class="font-semibold">Leader:</span> {{ $deal->leader?->name ?? '-' }}</p>
                      <p><span class="font-semibold">Created:</span>
                        {{ optional($deal->created_at)->format('Y-m-d H:i') ?? '-' }}</p>
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <div class="text-gray-600">No deals found for this client.</div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>