import { usePage } from '@inertiajs/vue3';

export function useDateFormat() {
    const page = usePage();

    const getFormat = () => {
        return page.props.tenant_settings?.date_format || 'MM/DD/YYYY';
    };

    const getTimeFormat = () => {
        return page.props.tenant_settings?.time_format || 'h:mm A';
    };

    /**
     * Format a date string or Date object according to tenant settings.
     * @param {string|Date|null} date
     * @param {boolean} includeTime - Whether to include time (HH:mm)
     * @returns {string}
     */
    const formatDate = (date, includeTime = false) => {
        if (!date) return '';

        // Laravel's toDateTimeString() produces "YYYY-MM-DD HH:MM:SS" with no timezone.
        // Since Laravel stores UTC, we normalise these to ISO 8601 UTC ("...T...Z") so
        // the browser parses them as UTC and getHours()/getMinutes() return local time.
        let dateValue = date;
        if (typeof dateValue === 'string' && /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(dateValue)) {
            dateValue = dateValue.replace(' ', 'T') + 'Z';
        }

        const d = new Date(dateValue);
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
            const h24 = d.getHours();
            const h12 = h24 % 12 || 12;
            const ampm = h24 >= 12 ? 'PM' : 'AM';
            const mm = String(d.getMinutes()).padStart(2, '0');
            const ss = String(d.getSeconds()).padStart(2, '0');

            switch (getTimeFormat()) {
                case 'h:mm:ss A':
                    formatted += ` ${h12}:${mm}:${ss} ${ampm}`;
                    break;
                case 'HH:mm':
                    formatted += ` ${String(h24).padStart(2, '0')}:${mm}`;
                    break;
                case 'HH:mm:ss':
                    formatted += ` ${String(h24).padStart(2, '0')}:${mm}:${ss}`;
                    break;
                case 'h:mm A':
                default:
                    formatted += ` ${h12}:${mm} ${ampm}`;
                    break;
            }
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
