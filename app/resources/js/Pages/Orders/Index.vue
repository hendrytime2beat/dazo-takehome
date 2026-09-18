<script setup>
import { computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import { formatIDR, statusClass, statusLabel } from '../../Shared/format.js'

const pageProps = usePage().props

const orders = computed(() => pageProps.orders || {})
const filters = computed(() => pageProps.filters || { status: '' })

const tabs = [
    { key: '', label: 'Semua' },
    { key: 'pending', label: 'Belum Dibayar' },
    { key: 'paid', label: 'Dibayar' },
    { key: 'cancelled', label: 'Dibatalkan' },
]

function changeTab(status) {
    if (status === filters.value.status) return
    router.get('/orders', { status }, { preserveState: true, preserveScroll: true, replace: true })
}
</script>

<template>
    <AppLayout>
        <template #header-title>Orders</template>
        <template #header-actions>
            <Link
                href="/orders/create"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Buat Order
            </Link>
        </template>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-4 pt-4">
                <div class="flex flex-wrap gap-1 rounded-lg">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        @click="changeTab(tab.key)"
                        class="rounded-md px-3.5 py-1.5 text-sm font-medium transition-colors"
                        :class="filters.status === tab.key ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-semibold">No. Order</th>
                            <th class="px-5 py-3 font-semibold">Pelanggan</th>
                            <th class="hidden px-5 py-3 font-semibold md:table-cell">Item</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 text-right font-semibold">Total</th>
                            <th class="px-5 py-3 font-semibold">Dibuat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="order in orders.data" :key="order.id" class="hover:bg-slate-50/60">
                            <td class="px-5 py-3.5 font-mono text-xs font-medium text-slate-700">{{ order.order_number }}</td>
                            <td class="px-5 py-3.5">
                                <p class="font-medium text-slate-900">{{ order.customer_name }}</p>
                                <p class="text-xs text-slate-400">{{ order.customer_email || '—' }}</p>
                            </td>
                            <td class="hidden px-5 py-3.5 text-slate-500 md:table-cell">{{ order.items_count }} item</td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                                    :class="statusClass[order.status] || statusClass.pending"
                                >
                                    {{ statusLabel[order.status] || order.status }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right font-semibold text-slate-900">{{ formatIDR(order.total_amount) }}</td>
                            <td class="px-5 py-3.5 text-slate-500">{{ order.created_at }}</td>
                        </tr>
                        <tr v-if="!orders.data?.length">
                            <td colspan="6" class="px-5 py-14 text-center text-sm text-slate-400">
                                Belum ada order. Klik "Buat Order" untuk membuat order pertama.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="orders.last_page > 1" class="flex items-center justify-between border-t border-slate-100 px-5 py-3">
                <p class="text-sm text-slate-500">Hal {{ orders.current_page }} dari {{ orders.last_page }}</p>
                <div class="flex gap-2">
                    <Link :href="orders.prev_page_url || '#'" :class="orders.prev_page_url ? 'text-slate-700 hover:bg-slate-100' : 'cursor-not-allowed text-slate-300'" class="rounded-lg px-3 py-1.5 text-sm font-medium">← Sebelumnya</Link>
                    <Link :href="orders.next_page_url || '#'" :class="orders.next_page_url ? 'text-slate-700 hover:bg-slate-100' : 'cursor-not-allowed text-slate-300'" class="rounded-lg px-3 py-1.5 text-sm font-medium">Selanjutnya →</Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>