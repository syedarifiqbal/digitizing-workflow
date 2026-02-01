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
        received: 'bg-slate-100 text-slate-700 ring-slate-600/20',
        assigned: 'bg-blue-50 text-blue-700 ring-blue-600/20',
        in_progress: 'bg-purple-50 text-purple-700 ring-purple-600/20',
        submitted: 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
        in_review: 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
        approved: 'bg-teal-50 text-teal-700 ring-teal-600/20',
        delivered: 'bg-green-50 text-green-700 ring-green-600/20',
        closed: 'bg-slate-100 text-slate-700 ring-slate-600/20',
        cancelled: 'bg-red-50 text-red-700 ring-red-600/20',
    };

    const getStatusColor = (status) => statusColors[status] || 'bg-slate-100 text-slate-700';

    const getPriorityClass = (priority) => {
        return priority === 'rush'
            ? 'bg-red-50 text-red-700 ring-red-600/20'
            : 'bg-slate-50 text-slate-700 ring-slate-600/20';
    };

    return {
        formatCurrency,
        getStatusColor,
        getPriorityClass,
        formatDate,
    };
}
