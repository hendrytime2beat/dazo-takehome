<script setup>
import { computed, ref } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import { formatIDR } from '../../Shared/format.js'

const pageProps = usePage().props
const products = computed(() => pageProps.products || [])

const form = useForm({
    customer_name: '',
    customer_email: '',
    customer_phone: '',
    shipping_address: '',
    payment_method: 'Transfer Bank',
    status: 'pending',
    shipping_cost: 0,
    items: [],
})

const selectedProductId = ref('')
const selectedQty = ref(1)

const cartLines = computed(() => {
    return form.items.map((item) => {
        const product = products.value.find((p) => p.id === Number(item.product_id))
        const unitPrice = product ? Number(product.price) : 0
        return { ...item, product, unit_price: unitPrice, line_total: unitPrice * Number(item.quantity) }
    })
})

const subtotal = computed(() => cartLines.value.reduce((sum, line) => sum + line.line_total, 0))
const total = computed(() => subtotal.value + Number(form.shipping_cost || 0))

function canAddItem() {
    if (!selectedProductId.value) return false
    const product = products.value.find((p) => p.id === Number(selectedProductId.value))
    if (!product) return false
    return Number(selectedQty.value) >= 1 && !form.items.some((i) => i.product_id === Number(selectedProductId.value))
}

function addItem() {
    if (!canAddItem()) return
    form.items.push({ product_id: Number(selectedProductId.value), quantity: Number(selectedQty.value) })
    selectedProductId.value = ''
    selectedQty.value = 1
    form.clearErrors('items')
}

function removeItem(index) {
    form.items.splice(index, 1)
}

function submit() {
    form.post('/orders')
}
</script>

<template>
    <AppLayout>
        <template #header-title>
            <Link href="/orders" class="text-slate-400 hover:text-slate-600">Orders</Link>
            <span class="mx-2 text-slate-300">/</span>
            Buat Order
        </template>

        <form class="grid grid-cols-1 gap-6 xl:grid-cols-3" @submit.prevent="submit">
            <!-- Left column -->
            <div class="space-y-6 xl:col-span-2">
                <!-- Customer -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Informasi Pelanggan</h2>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Nama <span class="text-rose-500">*</span></label>
                            <input v-model="form.customer_name" type="text" required
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                            <p v-if="form.errors.customer_name" class="mt-1 text-xs text-rose-600">{{ form.errors.customer_name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Email</label>
                            <input v-model="form.customer_email" type="email"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Telepon</label>
                            <input v-model="form.customer_phone" type="text"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700">Alamat Pengiriman</label>
                            <textarea v-model="form.shipping_address" rows="2"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-semibold text-slate-900">Item Produk</h2>
                        <p class="text-xs text-slate-400">Harga bersumber dari data server.</p>
                    </div>

                    <div class="mt-4 flex flex-col gap-3 rounded-lg bg-slate-50 p-3 sm:flex-row sm:items-end">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-slate-700">Produk</label>
                            <select v-model="selectedProductId"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                                <option value="">Pilih produk…</option>
                                <option v-for="product in products" :key="product.id" :value="product.id" :disabled="product.stock < 1">
                                    {{ product.name }} — {{ formatIDR(product.price) }} (stok {{ product.stock }})
                                </option>
                            </select>
                        </div>
                        <div class="w-full sm:w-32">
                            <label class="block text-sm font-medium text-slate-700">Kuantitas</label>
                            <input v-model.number="selectedQty" type="number" min="1"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                        </div>
                        <button type="button" @click="addItem" :disabled="!canAddItem()"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Tambah
                        </button>
                    </div>

                    <p v-if="form.errors.items" class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-600">{{ form.errors.items }}</p>

                    <ul v-if="cartLines.length" class="mt-4 divide-y divide-slate-100 rounded-xl border border-slate-200">
                        <li v-for="(line, index) in cartLines" :key="line.product_id" class="flex items-center gap-4 px-4 py-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-900">{{ line.product?.name }}</p>
                                <p class="text-xs text-slate-400">{{ formatIDR(line.unit_price) }} × {{ line.quantity }}</p>
                            </div>
                            <p class="font-semibold text-slate-900">{{ formatIDR(line.line_total) }}</p>
                            <button type="button" @click="removeItem(index)"
                                class="rounded-md p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </li>
                    </ul>
                    <p v-else class="mt-4 rounded-xl border border-dashed border-slate-300 py-10 text-center text-sm text-slate-400">
                        Belum ada item. Pilih produk di atas lalu klik "Tambah".
                    </p>
                </div>
            </div>

            <!-- Right column: summary -->
            <div class="space-y-6">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Ringkasan</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Subtotal</dt>
                            <dd class="font-medium text-slate-900">{{ formatIDR(subtotal) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Biaya Kirim</dt>
                            <dd>
                                <input v-model.number="form.shipping_cost" type="number" min="0" step="0.01"
                                    class="w-32 rounded-lg border border-slate-300 px-2.5 py-1 text-right text-sm outline-none focus:border-indigo-500" />
                            </dd>
                        </div>
                        <div class="flex justify-between border-t border-slate-100 pt-3 text-base">
                            <dt class="font-semibold text-slate-900">Total</dt>
                            <dd class="text-lg font-bold text-indigo-600">{{ formatIDR(total) }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Pembayaran</h2>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Metode</label>
                            <select v-model="form.payment_method"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                                <option>Transfer Bank</option>
                                <option>Kartu Kredit</option>
                                <option>E-Wallet</option>
                                <option>Cash on Delivery</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Status</label>
                            <select v-model="form.status"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                                <option value="pending">Belum Dibayar</option>
                                <option value="paid">Dibayar</option>
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" :disabled="form.processing || !form.items.length"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <svg v-if="form.processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                    </svg>
                    {{ form.processing ? 'Memproses…' : 'Buat Order' }}
                </button>
            </div>
        </form>
    </AppLayout>
</template>