<div class="my-4 mx-8 flex flex-wrap gap-3">

  <!-- Borrower Profile -->
  <a href="{{ route('loans.borrower-profile') }}" style="clip-path: polygon(0 0, 85% 0, 100% 50%, 85% 100%, 0 100%);"
    class="px-4 py-2 pr-6 h-min font-bold text-sm inline-flex items-center transition-all duration-200 border-pink-500
    {{ request()->routeIs('loans.borrower-profile*')
  ? 'border-l-[5px] bg-pink-100 text-pink-800'
  : 'bg-pink-500 text-white hover:bg-pink-100 hover:text-pink-800 hover:border-l-[5px] text-xs' }}">
    Borrower Profile
  </a>

  <!-- Pre Qualification -->
  <a href="{{ route('loans.pre-qualification') }}" style="clip-path: polygon(0 0, 85% 0, 100% 50%, 85% 100%, 0 100%);"
    class="px-4 py-2 pr-6 h-min font-bold text-sm inline-flex items-center transition-all duration-200 border-amber-500
    {{ request()->routeIs('loans.pre-qualification*')
  ? 'border-l-[5px] bg-amber-100 text-amber-800'
  : 'bg-amber-500 text-white hover:bg-amber-100 hover:text-amber-800 hover:border-l-[5px] text-xs' }}">
    Pre-Qualification
  </a>

  <!-- Bank Submission -->
  <a href="{{ route('loans.bank-submission-tracking') }}"
    style="clip-path: polygon(0 0, 85% 0, 100% 50%, 85% 100%, 0 100%);" class="px-4 py-2 pr-6 h-min font-bold text-sm inline-flex items-center transition-all duration-200 border-purple-500
    {{ request()->routeIs('loans.bank-submission-tracking*')
  ? 'border-l-[5px] bg-purple-100 text-purple-800'
  : 'bg-purple-500 text-white hover:bg-purple-100 hover:text-purple-800 hover:border-l-[5px] text-xs' }}">
    Bank Submission Tracking
  </a>

  <!-- Approval Analysis -->
  <a href="{{ route('loans.approval-analysis') }}" style="clip-path: polygon(0 0, 85% 0, 100% 50%, 85% 100%, 0 100%);"
    class="px-4 py-2 pr-6 h-min font-bold text-sm inline-flex items-center transition-all duration-200 border-teal-500
    {{ request()->routeIs('loans.approval-analysis*')
  ? 'border-l-[5px] bg-teal-100 text-teal-800'
  : 'bg-teal-500 text-white hover:bg-teal-100 hover:text-teal-800 hover:border-l-[5px] text-xs' }}">
    Approval Analysis
  </a>

  <!-- Disbursement -->
  <a href="{{ route('loans.disbursement') }}" style="clip-path: polygon(0 0, 85% 0, 100% 50%, 85% 100%, 0 100%);" class="px-4 py-2 pr-6 h-min font-bold text-sm inline-flex items-center transition-all duration-200 border-sky-500
    {{ request()->routeIs('loans.disbursement*')
  ? 'border-l-[5px] bg-sky-100 text-sky-800'
  : 'bg-sky-500 text-white hover:bg-sky-100 hover:text-sky-800 hover:border-l-[5px] text-xs' }}">
    Disbursement
  </a>

</div>