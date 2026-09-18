export function formatIDR(value) {
    const n = Number(value ?? 0)
    return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Math.round(n))
}

export function formatNumber(value) {
    return new Intl.NumberFormat('id-ID').format(Number(value ?? 0))
}

export const statusLabel = {
    paid: 'Dibayar',
    pending: 'Belum Dibayar',
    cancelled: 'Dibatalkan',
}

export const statusClass = {
    paid: 'bg-emerald-100 text-emerald-700 ring-emerald-200',
    pending: 'bg-amber-100 text-amber-700 ring-amber-200',
    cancelled: 'bg-rose-100 text-rose-700 ring-rose-200',
}