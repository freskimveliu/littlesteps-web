const DATE = new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
const DATE_TIME = new Intl.DateTimeFormat('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
});

const EMPTY = '—';

function parse(value: string | null | undefined): Date | null {
    if (!value) return null;

    // A plain Y-m-d is read as UTC midnight, which shows as the day before in
    // any negative offset — so it is built as a local date instead.
    const plain = /^\d{4}-\d{2}-\d{2}$/.exec(value);
    const date = plain ? new Date(`${value}T00:00:00`) : new Date(value);

    return Number.isNaN(date.getTime()) ? null : date;
}

export function formatDate(value: string | null | undefined, fallback = EMPTY): string {
    const date = parse(value);

    return date ? DATE.format(date) : fallback;
}

export function formatDateTime(value: string | null | undefined, fallback = EMPTY): string {
    const date = parse(value);

    return date ? DATE_TIME.format(date) : fallback;
}
