<x-modal name="dashboard-pipeline-stage-modal" :show="false" maxWidth="4xl" :simple="true" :lockScroll="false"
    :centered="true">
    <div class="border-b border-slate-200 px-5 py-3">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 id="pipeline-stage-modal-title" class="text-base font-semibold text-slate-900">Pipeline Stage
                </h3>
                <p id="pipeline-stage-modal-subtitle" class="mt-0.5 text-xs text-slate-500"></p>
            </div>
            <button type="button" class="text-slate-500 hover:text-slate-700"
                x-on:click="$dispatch('close-modal', 'dashboard-pipeline-stage-modal')">
                <span class="sr-only">Close</span>
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
    </div>
    <div class="max-h-[70vh] overflow-y-auto mb-2">
        <div id="pipeline-stage-modal-loading" class="hidden py-8 text-center text-sm text-slate-500">
            Loading stage details...</div>
        <div id="pipeline-stage-modal-empty" class="hidden py-8 text-center text-sm text-slate-500">
            No records found for this stage.</div>
        <div id="pipeline-stage-modal-error" class="hidden py-8 text-center text-sm text-rose-600">
            Failed to load stage details.</div>
        <div id="pipeline-stage-modal-table-wrap" class="hidden overflow-x-auto px-5 py-4">
            <table class="w-full divide-y divide-slate-200 text-sm border border-slate-200">
                <thead class="bg-slate-100 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-3 py-2 text-center">No.</th>
                        <th class="px-3 py-2">Deal ID</th>
                        <th class="px-3 py-2">Project Name</th>
                        <th class="px-3 py-2">Salesperson</th>
                        <th class="px-3 py-2">Leader</th>
                        <th class="px-3 py-2">Created Date</th>
                        <th id="pipeline-stage-modal-stage-date-header" class="px-3 py-2">Stage Date</th>
                    </tr>
                </thead>
                <tbody id="pipeline-stage-modal-tbody" class="divide-y divide-slate-200 bg-white text-slate-700">
                </tbody>
            </table>
        </div>
    </div>
</x-modal>
