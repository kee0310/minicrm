<?php

namespace App\Http\Controllers;

use App\Enums\PipelineEnum;
use App\Models\Deal;
use Illuminate\Http\Request;

class LegalController extends LoanController
{
    // Render legal table for loan approved deals.
    public function index()
    {
        $deals = $this->scopeDealsForLoanAccess(
            Deal::with(['client', 'legalCase'])
        )
            ->whereIn('pipeline', [
                PipelineEnum::LOAN_APPROVED->value,
                PipelineEnum::LEGAL_PROCESSING->value,
                PipelineEnum::COMPLETED->value,
            ])
            ->latest()
            ->get();

        $statusOptions = ['Drafting', 'Pending Bank', 'Pending Customer Signature', 'Completed'];
        $newCaseCounts = $this->getLoanNewCaseCounts();
        $canManageLoanRecords = $this->canManageLoanRecords();

        return view('legals.index', compact('deals', 'statusOptions', 'newCaseCounts', 'canManageLoanRecords'));
    }

    // Create/update legal details for a deal in legal workflow.
    public function update(Request $request, Deal $deal)
    {
        $this->ensureCanManageLoanRecords();
        $this->ensureCanViewDeal($deal);

        abort_unless(
            in_array($deal->pipeline?->value, [
                PipelineEnum::LOAN_APPROVED->value,
                PipelineEnum::LEGAL_PROCESSING->value,
                PipelineEnum::COMPLETED->value,
            ], true),
            422,
            'Only loan approved/legal processing/completed deals can be updated in legal.'
        );

        $data = $request->validate([
            'status' => ['required', 'string', 'in:Drafting,Pending Bank,Pending Customer Signature,Completed'],
            'lawyer_firm' => ['required', 'string', 'max:255'],
            'spa_date' => ['required', 'date'],
            'loan_agreement_date' => ['required', 'date'],
            'completion_date' => ['required', 'date'],
            'stamp_duty' => ['nullable', 'boolean'],
        ]);

        $legalCase = $deal->legalCase()->updateOrCreate(
            ['deal_id' => $deal->id],
            [
                'status' => $data['status'],
                'lawyer_firm' => $data['lawyer_firm'],
                'spa_date' => $data['spa_date'],
                'loan_agreement_date' => $data['loan_agreement_date'],
                'completion_date' => $data['completion_date'],
                'stamp_duty' => (bool) ($data['stamp_duty'] ?? false),
            ]
        );

        if ($legalCase->status === 'Completed') {
            $deal->update(['pipeline' => PipelineEnum::COMPLETED->value]);
        } else {
            $deal->update(['pipeline' => PipelineEnum::LEGAL_PROCESSING->value]);
        }

        return redirect()->route('legals.index')->with('success', 'Legal case updated.');
    }
}
