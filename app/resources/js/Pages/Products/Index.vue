<script setup>
import { computed, ref, watch } from 'vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import { formatIDR } from '../../Shared/format.js'

const pageProps = usePage().props

const products = computed(() => pageProps.products)
const filters = computed(() => pageProps.filters || { search: '' })

const modalOpen = ref(false)
const editing = ref(null)

const form = useForm({
    sku: '',
    name: '',
    category: '',
    description: '',
    price: '',
    stock: 0,
    is_active: true,
})

const search = ref(filters.value.search || '')

let debounceTimer = null

watch(search, (value) => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        router.get('/products', { search: value }, { preserveState: true, replace: true })
    }, 300)
})

function openCreate() {
    editing.value = null
    form.reset()
    form.clearErrors()
    modalOpen.value = true
}

function openEdit(product) {
    editing.value = product
    form.reset()
    form.clearErrors()
    form.setError({})
    form.sku = product.sku
    form.name = product.name
    form.category = product.category || ''
    form.description = product.description || ''
    form.price = product.price
    form.stock = product.stock
    form.is_active = Boolean(product.is_active)
    modalOpen.value = true
}

function submit() {
    form.transform((data) => ({
        ...data,
        is_active: Boolean(data.is_active),
    })).post(editing.value ? `/products/${editing.value.id}` : '/products', {
        preserveScroll: true,
        onSuccess: () => {
            modalOpen.value = false
        },
    })
}

function remove(product) {
    if (!confirm(`Hapus produk "${product.name}"?`)) return
    router.delete(`/products/${product.id}`, { preserveScroll: true })
}
</script>

<template>
    <AppLayout>
        <template #header-title>Produk</template>
        <template #header-actions>
            <button
                type="button"
                @click="openCreate"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Produk
            </button>
        </template>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <!-- toolbar -->
            <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative w-full sm:max-w-xs">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Cari nama atau SKU…"
                        class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                    />
                </div>
                <p class="text-sm text-slate-500">{{ products.total }} produk</p>
            </div>

            <!-- table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-semibold">SKU</th>
                            <th class="px-5 py-3 font-semibold">Nama</th>
                            <th class="hidden px-5 py-3 font-semibold md:table-cell">Kategori</th>
                            <th class="px-5 py-3 text-right font-semibold">Harga</th>
                            <th class="px-5 py-3 text-right font-semibold">Stok</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="product in products.data" :key="product.id" class="hover:bg-slate-50/60">
                            <td class="px-5 py-3.5 font-mono text-xs text-slate-500">{{ product.sku }}</td>
                            <td class="px-5 py-3.5">
                                <p class="font-medium text-slate-900">{{ product.name }}</p>
                            </td>
                            <td class="hidden px-5 py-3.5 text-slate-500 md:table-cell">
                                <span v-if="product.category" class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                                    {{ product.category }}
                                </span>
                                <span v-else class="text-slate-300">—</span>
                            </td>
                            <td class="px-5 py-3.5 text-right font-semibold text-slate-900">{{ formatIDR(product.price) }}</td>
                            <td class="px-5 py-3.5 text-right">
                                <span
                                    class="font-semibold"
                                    :class="product.stock <= 5 ? 'text-rose-600' : 'text-slate-900'"
                                >
                                    {{ product.stock }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                                    :class="product.is_active ? 'bg-emerald-100 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-500 ring-slate-200'"
                                >
                                    {{ product.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex justify-end gap-1.5">
                                    <button
                                        type="button"
                                        @click="openEdit(product)"
                                        class="rounded-md p-1.5 text-slate-400 hover:bg-indigo-50 hover:text-indigo-600"
                                        title="Edit"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        @click="remove(product)"
                                        class="rounded-md p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600"
                                        title="Hapus"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!products.data.length">
                            <td colspan="7" class="px-5 py-14 text-center text-sm text-slate-400">
                                {{ filters.search ? 'Tidak ada produk yang cocok dengan pencarian.' : 'Belum ada produk. Klik "Tambah Produk" untuk mulai.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- pagination -->
            <div v-if="products.last_page > 1" class="flex items-center justify-between border-t border-slate-100 px-5 py-3">
                <p class="text-sm text-slate-500">
                    Hal {{ products.current_page }} dari {{ products.last_page }}
                </p>
                <div class="flex gap-2">
                    <Link
                        :href="products.prev_page_url || '#'"
                        :class="products.prev_page_url ? 'text-slate-700 hover:bg-slate-100' : 'cursor-not-allowed text-slate-300'"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium"
                    >← Sebelumnya</Link>
                    <Link
                        :href="products.next_page_url || '#'"
                        :class="products.next_page_url ? 'text-slate-700 hover:bg-slate-100' : 'cursor-not-allowed text-slate-300'"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium"
                    >Selanjutnya →</Link>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="modalOpen = false" />
            <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                <h2 class="text-lg font-semibold text-slate-900">{{ editing ? 'Edit Produk' : 'Tambah Produk' }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">Harga dan stok akan dipakai oleh transaksi order.</p>

                <form class="mt-5 space-y-4" @submit.prevent="submit">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">SKU</label>
                            <input v-model="form.sku" type="text" required
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                            <p v-if="form.errors.sku" class="mt-1 text-xs text-rose-600">{{ form.errors.sku }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Kategori</label>
                            <input v-model="form.category" type="text"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Nama Produk</label>
                        <input v-model="form.name" type="text" required
                            class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Deskripsi</label>
                        <textarea v-model="form.description" rows="2"
                            class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Harga</label>
                            <input v-model="form.price" type="number" step="0.01" min="0" required
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                            <p v-if="form.errors.price" class="mt-1 text-xs text-rose-600">{{ form.errors.price }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Stok</label>
                            <input v-model="form.stock" type="number" min="0" required
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                            <p v-if="form.errors.stock" class="mt-1 text-xs text-rose-600">{{ form.errors.stock }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Status</label>
                            <select v-model="form.is_active"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                                <option :value="true">Aktif</option>
                                <option :value="false">Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="modalOpen = false"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-60">
                            {{ form.processing ? 'Menyimpan…' : 'Simpan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>