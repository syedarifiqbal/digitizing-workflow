import { useDateFormat } from '@/Composables/useDateFormat';

export function useDashboard() {
    const { formatDate } = useDateFormat();

    const formatCurrency = (amount, currency = 'USD') => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency,
        }).format(amount || 0);
    };

    const statusColors = {
        received: 'bg-gray-100 text-gray-800',
        assigned: 'bg-blue-100 text-blue-800',
        in_progress: 'bg-yellow-100 text-yellow-800',
        submitted: 'bg-purple-100 text-purple-800',
        in_review: 'bg-indigo-100 text-indigo-800',
        revision_requested: 'bg-orange-100 text-orange-800',
        approved: 'bg-emerald-100 text-emerald-800',
        delivered: 'bg-green-100 text-green-800',
        closed: 'bg-slate-100 text-slate-800',
        cancelled: 'bg-red-100 text-red-800',
    };

    const getStatusColor = (status) => statusColors[status] || 'bg-gray-100 text-gray-800';

    const getPriorityClass = (priority) => {
        return priority === 'rush'
            ? 'bg-red-100 text-red-800'
            : 'bg-slate-100 text-slate-600';
    };

    return {
        formatCurrency,
        getStatusColor,
        getPriorityClass,
        formatDate,
    };
}
