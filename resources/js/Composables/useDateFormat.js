import { usePage } from '@inertiajs/vue3';

export function useDateFormat() {
    const page = usePage();

    const getFormat = () => {
        return page.props.tenant_settings?.date_format || 'MM/DD/YYYY';
    };

    /**
     * Format a date string or Date object according to tenant settings.
     * @param {string|Date|null} date
     * @param {boolean} includeTime - Whether to include time (HH:mm)
     * @returns {string}
     */
    const formatDate = (date, includeTime = false) => {
        if (!date) return '';

        const d = new Date(date);
        if (isNaN(d.getTime())) return '';

        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();

        const format = getFormat();

        let formatted;
        switch (format) {
            case 'DD/MM/YYYY':
                formatted = `${day}/${month}/${year}`;
                break;
            case 'YYYY-MM-DD':
                formatted = `${year}-${month}-${day}`;
                break;
            case 'DD-MM-YYYY':
                formatted = `${day}-${month}-${year}`;
                break;
            case 'DD.MM.YYYY':
                formatted = `${day}.${month}.${year}`;
                break;
            case 'MM/DD/YYYY':
            default:
                formatted = `${month}/${day}/${year}`;
                break;
        }

        if (includeTime) {
            const hours = String(d.getHours()).padStart(2, '0');
            const minutes = String(d.getMinutes()).padStart(2, '0');
            formatted += ` ${hours}:${minutes}`;
        }

        return formatted;
    };

    /**
     * Get the HTML5 date input format string for the tenant's date format.
     * Note: HTML date inputs always use YYYY-MM-DD internally.
     * This returns the display format for reference.
     */
    const getPlaceholder = () => {
        return getFormat().toLowerCase();
    };

    return {
        formatDate,
        getFormat,
        getPlaceholder,
    };
}
