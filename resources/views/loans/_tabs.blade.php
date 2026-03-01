<div class="my-4 mx-8 flex flex-wrap gap-3">
  @php
    $newCaseCounts = $newCaseCounts ?? [
      'borrower_profile' => 0,
      'pre_qualification' => 0,
      'bank_submission_tracking' => 0,
      'approval_analysis' => 0,
      'disbursement' => 0,
    ];
  @endphp

  <!-- Borrower Profile -->
  <div class="relative inline-block">
    <a href="{{ route('loans.borrower-profile') }}" style="clip-path: polygon(0 0, 85% 0, 100% 50%, 85% 100%, 0 100%);"
      class="pl-2 py-2 pr-7 h-min font-bold text-sm inline-flex items-center transition-all duration-200 border-pink-500
      {{ request()->routeIs('loans.borrower-profile*')
  ? 'border-l-[5px] bg-pink-100 text-pink-800'
  : 'bg-pink-500 text-white hover:bg-pink-100 hover:text-pink-800 hover:border-l-[5px] text-xs' }}">
      Borrower Profile
    </a>
    @if(($newCaseCounts['borrower_profile'] ?? 0) > 0)
      <span
        class="absolute -top-2 -left-2 z-10 inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full bg-red-600 text-white text-xs">
        {{ $newCaseCounts['borrower_profile'] }}
      </span>
    @endif
  </div>

  <!-- Pre Qualification -->
  <div class="relative inline-block">
    <a href="{{ route('loans.pre-qualification') }}" style="clip-path: polygon(0 0, 85% 0, 100% 50%, 85% 100%, 0 100%);"
      class="px-4 py-2 pr-7 h-min font-bold text-sm inline-flex items-center transition-all duration-200 border-amber-500
      {{ request()->routeIs('loans.pre-qualification*')
  ? 'border-l-[5px] bg-amber-100 text-amber-800'
  : 'bg-amber-500 text-white hover:bg-amber-100 hover:text-amber-800 hover:border-l-[5px] text-xs' }}">
      Pre-Qualification
    </a>
    @if(($newCaseCounts['pre_qualification'] ?? 0) > 0)
      <span
        class="absolute -top-2 -left-2 z-10 inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full bg-red-600 text-white text-xs">
        {{ $newCaseCounts['pre_qualification'] }}
      </span>
    @endif
  </div>

  <!-- Bank Submission -->
  <div class="relative inline-block">
    <a href="{{ route('loans.bank-submission-tracking') }}"
      style="clip-path: polygon(0 0, 88% 0, 100% 50%, 88% 100%, 0 100%);" class="px-4 py-2 pr-7 h-min font-bold text-sm inline-flex items-center transition-all duration-200 border-purple-500
      {{ request()->routeIs('loans.bank-submission-tracking*')
  ? 'border-l-[5px] bg-purple-100 text-purple-800'
  : 'bg-purple-500 text-white hover:bg-purple-100 hover:text-purple-800 hover:border-l-[5px] text-xs' }}">
      Bank Submission Tracking
    </a>
    @if(($newCaseCounts['bank_submission_tracking'] ?? 0) > 0)
      <span
        class="absolute -top-2 -left-2 z-10 inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full bg-red-600 text-white text-xs">
        {{ $newCaseCounts['bank_submission_tracking'] }}
      </span>
    @endif
  </div>

  <!-- Approval Analysis -->
  <div class="relative inline-block">
    <a href="{{ route('loans.approval-analysis') }}" style="clip-path: polygon(0 0, 85% 0, 100% 50%, 85% 100%, 0 100%);"
      class="px-4 py-2 pr-7 h-min font-bold text-sm inline-flex items-center transition-all duration-200 border-teal-500
      {{ request()->routeIs('loans.approval-analysis*')
  ? 'border-l-[5px] bg-teal-100 text-teal-800'
  : 'bg-teal-500 text-white hover:bg-teal-100 hover:text-teal-800 hover:border-l-[5px] text-xs' }}">
      Approval Analysis
    </a>
    @if(($newCaseCounts['approval_analysis'] ?? 0) > 0)
      <span
        class="absolute -top-2 -left-2 z-10 inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full bg-red-600 text-white text-xs">
        {{ $newCaseCounts['approval_analysis'] }}
      </span>
    @endif
  </div>

  <!-- Disbursement -->
  <div class="relative inline-block">
    <a href="{{ route('loans.disbursement') }}" style="clip-path: polygon(0 0, 85% 0, 100% 50%, 85% 100%, 0 100%);"
      class="px-4 py-2 pr-7 h-min font-bold text-sm inline-flex items-center transition-all duration-200 border-sky-500
      {{ request()->routeIs('loans.disbursement*')
  ? 'border-l-[5px] bg-sky-100 text-sky-800'
  : 'bg-sky-500 text-white hover:bg-sky-100 hover:text-sky-800 hover:border-l-[5px] text-xs' }}">
      Disbursement
    </a>
    @if(($newCaseCounts['disbursement'] ?? 0) > 0)
      <span
        class="absolute -top-2 -left-2 z-10 inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full bg-red-600 text-white text-xs">
        {{ $newCaseCounts['disbursement'] }}
      </span>
    @endif
  </div>

</div>


