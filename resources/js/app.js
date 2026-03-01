import './bootstrap';
import '../css/app.css';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('loanPageState', (extraState = {}) => ({
    selectedDeal: null,
    loanDetailLoading: false,
    modal: {},
    ...extraState,
    openModal(key) {
        this.modal[key] = true;
    },
    closeModal(key) {
        this.modal[key] = false;
    },
    isModalOpen(key) {
        return this.modal[key] === true;
    },
    async openLoanDetail(dealId, modalKey, loanId = null) {
        this.loanDetailLoading = true;
        this.selectedDeal = null;
        this.openModal(modalKey);
        try {
            const baseUrl = loanId
                ? `/loans/detail/by-loan/${loanId}`
                : `/loans/detail/${dealId}`;
            const response = await fetch(baseUrl, {
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json();
            this.selectedDeal = payload?.data ?? null;
        } catch (error) {
            console.error('Loan detail error:', error);
            this.selectedDeal = null;
        } finally {
            this.loanDetailLoading = false;
        }
    },
}));

Alpine.start();
