<script setup>
import { computed, onMounted, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import VueApexCharts from 'vue3-apexcharts'
import AppLayout from '../../Layouts/AppLayout.vue'
import { formatIDR, formatNumber } from '../../Shared/format.js'
import Icon from '../../Components/Icon.vue'
import SummaryCard from '../../Components/Dashboard/SummaryCard.vue'
import MonitorCard from '../../Components/Dashboard/MonitorCard.vue'
import DateRangeButton from '../../Components/Dashboard/DateRangeButton.vue'

const page = usePage()
const props = page.props

const loading = ref(true)
onMounted(() => setTimeout(() => (loading.value = false), 650))

const metrics = computed(() => props.metrics || {})
const series = computed(() => props.sales_series || [])
const topProducts = computed(() => props.top_products || [])

const mostProfitable = computed(() => [...topProducts.value].sort((a, b) => (b.revenue || 0) - (a.revenue || 0)))
const mostInDemand = computed(() => [...topProducts.value].sort((a, b) => (b.quantity || 0) - (a.quantity || 0)))
const bestSeller = computed(() => [...topProducts.value].sort((a, b) => (b.revenue || 0) - (a.revenue || 0)))

const chartCategories = computed(() => series.value.map((s) => {
    const [, m, d] = (s.date || '').split('-')
    return m && d ? `${d}-${m}` : s.date
}))

const chartSeries = computed(() => [
    { name: 'Total Pesanan', data: series.value.map((s) => s.orders || 0) },
    { name: 'Total Pendapatan', data: series.value.map((s) => s.revenue || 0) },
])

const chartOptions = computed(() => ({
    chart: {
        type: 'line',
        fontFamily: 'Inter, ui-sans-serif, system-ui, sans-serif',
        toolbar: { show: false },
        zoom: { enabled: false },
        height: 280,
    },
    colors: ['#2949B5', '#46C98B'],
    stroke: { width: [2, 2], curve: 'smooth' },
    markers: { size: 3, strokeWidth: 2 },
    grid: { borderColor: '#E8ECF5', strokeDashArray: 3, padding: { left: 8, right: 12 } },
    xaxis: {
        categories: chartCategories.value,
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: '#7C8497', fontSize: '10px', fontWeight: 500 } },
    },
    yaxis: [
        {
            title: { text: 'Total Pesanan', style: { color: '#7C8497', fontSize: '10px', fontWeight: 600 } },
            labels: { style: { colors: '#7C8497', fontSize: '10px' } },
            axisBorder: { show: false },
        },
        {
            opposite: true,
            title: { text: 'Pendapatan (Rp)', style: { color: '#7C8497', fontSize: '10px', fontWeight: 600 } },
            labels: {
                style: { colors: '#7C8497', fontSize: '10px' },
                formatter: (val) => new Intl.NumberFormat('id-ID', { notation: 'compact' }).format(Number(val || 0)),
            },
            axisBorder: { show: false },
        },
    ],
    legend: { show: false },
    tooltip: {
        shared: true,
        y: {
            formatter: (val, { seriesIndex }) => seriesIndex === 0 ? formatNumber(val) : formatIDR(val).replace(/^Rp\s*/, 'IDR '),
        },
    },
}))

const rightStats = computed(() => [
    { label: 'Total Pembayaran', value: metrics.value.paid_total ?? 0, currency: true, icon: 'wallet', info: 'Nilai total pesanan berstatus Dibayar' },
    { label: '% Sudah Bayar', value: metrics.value.paid_percentage ?? 0, suffix: '%', icon: 'percent', info: 'Proporsi pesanan berstatus Dibayar' },
    { label: '% Belum Bayar', value: metrics.value.unpaid_percentage ?? 0, suffix: '%', icon: 'percent', info: 'Proporsi pesanan berstatus Belum Dibayar' },
    { label: 'Biaya Kirim', value: metrics.value.total_shipping_cost ?? 0, currency: true, icon: 'truck', info: 'Akumulasi ongkos kirim seluruh pesanan' },
])

function fmtMoney(v) {
    return formatIDR(v).replace(/^Rp\s*/, 'IDR ')
}

function selectPeriod(key) {
    router.get('/', { period: key }, { preserveState: true, replace: false })
}
</script>

<template>
    <AppLayout>
        <div class="mx-auto max-w-[1200px]">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-[14px] font-semibold text-[#20252F]">
                        Selamat Datang, <span class="text-[#2949B5]">Product Dazo!</span>
                    </h1>
                </div>
                <DateRangeButton :from="props.from" :to="props.to" :period="props.period" @select="selectPeriod" />
            </div>

            <div v-if="!props.analytics_ok" class="mt-3 flex items-center gap-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-[12px] text-amber-800">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                Layanan analitik tidak tersedia — angka berikut dihitung lokal dan bisa kedaluwarsa.
            </div>

            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <template v-if="loading">
                    <div v-for="i in 4" :key="i" class="h-[75px] animate-pulse rounded-[4px] border border-[#E8ECF5] bg-white"></div>
                </template>
                <template v-else>
                    <SummaryCard label="Total Pesanan" :value="metrics.total_orders" icon="order" info="Jumlah seluruh pesanan pada periode terpilih" />
                    <SummaryCard label="Pendapatan Kotor" :value="metrics.total_revenue" icon="wallet" currency info="Total nilai pesanan sebelum dikurangi biaya kirim" />
                    <SummaryCard label="Pendapatan Bersih" :value="metrics.net_revenue" icon="trend" currency info="Pendapatan kotor dikurangi total biaya kirim" />
                    <SummaryCard label="Laba Kotor" :value="metrics.profit" icon="card" currency info="Estimasi margin 40% dari nilai produk (HPP 60%)" />
                </template>
            </div>

            <div class="mt-3 grid grid-cols-1 gap-3 xl:grid-cols-[1fr_260px]">
                <section class="rounded-[4px] border border-[#E8ECF5] bg-white p-4">
                    <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-[13px] font-semibold text-[#20252F]">Laporan Penjualan</h2>
                        <div class="flex items-center gap-3 text-[11px] text-[#7C8497]">
                            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#2949B5]"></span>Total Pesanan</span>
                            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#46C98B]"></span>Total Pendapatan</span>
                        </div>
                    </div>

                    <div v-if="loading" class="h-[280px] animate-pulse rounded-md bg-[#F7F9FE]"></div>
                    <div v-else-if="series.length === 0" class="flex h-[280px] flex-col items-center justify-center gap-2 text-[12px] text-[#7C8497]">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" class="opacity-40"><path d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>
                        Belum ada data penjualan pada periode ini
                    </div>
                    <VueApexCharts v-else type="line" height="280" :options="chartOptions" :series="chartSeries" />
                </section>

                <aside class="flex flex-col gap-3">
                    <template v-if="loading">
                        <div v-for="i in 4" :key="'s' + i" class="h-[64px] animate-pulse rounded-[4px] border border-[#E8ECF5] bg-white"></div>
                    </template>
                    <template v-else>
                        <div v-for="stat in rightStats" :key="stat.label" class="flex h-[64px] items-center justify-between rounded-[4px] border border-[#E8ECF5] bg-white px-3.5">
                            <div class="min-w-0">
                                <p class="flex items-center gap-1 text-[11px] text-[#7C8497]">
                                    {{ stat.label }}
                                    <span class="group relative inline-flex" tabindex="0">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="text-[#B4BACE]"><path d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                                        <span class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-1 w-max max-w-[200px] -translate-x-1/2 rounded-md bg-[#20252F] px-2.5 py-1.5 text-[11px] font-normal text-white opacity-0 shadow transition group-hover:opacity-100 group-focus:opacity-100">{{ stat.info }}</span>
                                    </span>
                                </p>
                                <p class="mt-0.5 flex items-baseline gap-1 text-[13px] font-bold text-[#20252F]">
                                    <span v-if="stat.currency" class="text-[11px] font-bold text-[#2949B5]">IDR</span>
                                    <span>{{ stat.currency ? formatIDR(stat.value).replace(/^Rp\s*/, '') : formatNumber(stat.value) }}</span>
                                    <span v-if="stat.suffix" class="text-[11px] text-[#2949B5]">{{ stat.suffix }}</span>
                                </p>
                            </div>
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#E7EDFF] text-[#2949B5] opacity-70">
                                <Icon :name="stat.icon" :size="17" />
                            </span>
                        </div>
                    </template>
                </aside>
            </div>

            <section class="mt-4">
                <h2 class="text-[13px] font-semibold text-[#20252F]">Monitoring</h2>
                <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-3">
                    <MonitorCard v-if="!loading && mostProfitable.length" title="Produk Yang Menguntungkan">
                        <div class="flex min-w-0 items-center justify-between gap-2 text-[11px] text-[#3E4657]">
                            <span class="truncate font-semibold">{{ mostProfitable[0].name }}</span>
                            <span class="shrink-0 font-bold text-[#2949B5]">{{ fmtMoney(mostProfitable[0].revenue) }}</span>
                        </div>
                    </MonitorCard>
                    <MonitorCard v-else-if="loading" title="Produk Yang Menguntungkan">
                        <div class="h-3 w-2/3 animate-pulse rounded bg-[#ECEEF5]"></div>
                    </MonitorCard>
                    <MonitorCard v-else title="Produk Yang Menguntungkan">
                        <span class="text-[11px] text-[#B4BACE]">Tidak ada Produk</span>
                    </MonitorCard>

                    <MonitorCard v-if="!loading && mostInDemand.length" title="Produk Permintaan Tertinggi">
                        <div class="flex min-w-0 items-center justify-between gap-2 text-[11px] text-[#3E4657]">
                            <span class="truncate font-semibold">{{ mostInDemand[0].name }}</span>
                            <span class="shrink-0 font-bold text-[#2949B5]">{{ formatNumber(mostInDemand[0].quantity) }} terjual</span>
                        </div>
                    </MonitorCard>
                    <MonitorCard v-else-if="loading" title="Produk Permintaan Tertinggi">
                        <div class="h-3 w-2/3 animate-pulse rounded bg-[#ECEEF5]"></div>
                    </MonitorCard>
                    <MonitorCard v-else title="Produk Permintaan Tertinggi">
                        <span class="text-[11px] text-[#B4BACE]">Tidak ada Produk</span>
                    </MonitorCard>

                    <MonitorCard v-if="!loading && bestSeller.length" title="Produk dengan Penjualan Tertinggi">
                        <div class="flex min-w-0 items-center justify-between gap-2 text-[11px] text-[#3E4657]">
                            <span class="truncate font-semibold">{{ bestSeller[0].name }}</span>
                            <span class="shrink-0 font-bold text-[#2949B5]">{{ fmtMoney(bestSeller[0].revenue) }}</span>
                        </div>
                    </MonitorCard>
                    <MonitorCard v-else-if="loading" title="Produk dengan Penjualan Tertinggi">
                        <div class="h-3 w-2/3 animate-pulse rounded bg-[#ECEEF5]"></div>
                    </MonitorCard>
                    <MonitorCard v-else title="Produk dengan Penjualan Tertinggi">
                        <span class="text-[11px] text-[#B4BACE]">Tidak ada Produk</span>
                    </MonitorCard>
                </div>
            </section>
        </div>
    </AppLayout>
</template>