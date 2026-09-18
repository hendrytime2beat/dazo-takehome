<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import Icon from '../Components/Icon.vue'
import ChatWidget from '../Components/ChatWidget.vue'

const page = usePage()
const collapsed = ref(localStorage.getItem('dazo.sidebar.collapsed') === '1')
const drawerOpen = ref(false)

const toggleCollapsed = () => {
    collapsed.value = !collapsed.value
    localStorage.setItem('dazo.sidebar.collapsed', collapsed.value ? '1' : '0')
}

const toast = ref(null)
let toastTimer = null

const flash = page.props.flash
if (flash?.success || flash?.error) {
    toast.value = flash.success ? { type: 'success', text: flash.success } : { type: 'error', text: flash.error }
}

function notify(message, type = 'success') {
    toast.value = { type, text: message }
    clearTimeout(toastTimer)
    toastTimer = setTimeout(() => (toast.value = null), 3500)
}

function logout() {
    router.post('/logout')
}

const navigation = [
    { name: 'Dashboard', href: '/', icon: 'dashboard' },
    { name: 'Produk', href: '/products', icon: 'box' },
    { name: 'Order', href: '/orders', icon: 'order' },
    { name: 'Chat', href: '/chat', icon: 'chat', children: ['Chat Penjualan', 'Chat Pembeli'] },
    { name: 'Kontak', href: '/contacts', icon: 'users' },
    { name: 'Manajemen Toko', href: '/store', icon: 'store' },
    { name: 'Toko Digital', href: '/digital', icon: 'globe', children: ['Produk Digital', 'Voucher'] },
    { name: 'Alat', href: '/tools', icon: 'tools', children: ['Impor', 'Ekspor', 'Analisis'] },
    { name: 'Laporan', href: '/reports', icon: 'report' },
    { name: 'Pengaturan', href: '/settings', icon: 'gear' },
]

function isActive(item) {
    return (page.url || '').split('?')[0] === item.href
}

const user = computed(() => page.props.auth?.user)

const packageOpen = ref(false)
const fullscreenOpen = ref(false)
const isFullscreen = ref(false)

const businessPackages = [
    { label: 'Paket Bisnis', desc: 'Akses lengkap fitur toko', active: true },
    { label: 'Paket Pro', desc: 'Prioritas & analitik lanjutan' },
    { label: 'Paket Enterprise', desc: 'Multi-toko & API' },
]

const closeDropdowns = () => {
    packageOpen.value = false
    fullscreenOpen.value = false
}

const onDocumentClick = (e) => {
    if (!e.target.closest('[data-dropdown]')) closeDropdowns()
}

function toggleFullscreen() {
    try {
        if (document.fullscreenElement) {
            document.exitFullscreen()
        } else {
            document.documentElement.requestFullscreen()
        }
    } catch {
        /* ignore unsupported */
    }
}

const onFullscreenChange = () => {
    isFullscreen.value = Boolean(document.fullscreenElement)
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick)
    document.addEventListener('fullscreenchange', onFullscreenChange)
})

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick)
    document.removeEventListener('fullscreenchange', onFullscreenChange)
    clearTimeout(toastTimer)
})

defineExpose({ notify })
</script>

<template>
    <div class="min-h-screen bg-[#F7F9FE] text-[#20252F]">
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-[-4px]"
            leave-active-class="transition ease-in duration-150"
            leave-to-class="opacity-0 translate-y-[-4px]"
        >
            <div
                v-if="toast"
                :class="[
                    'fixed left-1/2 top-4 z-[100] flex -translate-x-1/2 items-center gap-2 rounded-md px-4 py-2.5 text-[13px] font-medium text-white shadow-lg',
                    toast.type === 'error' ? 'bg-rose-500' : 'bg-emerald-500',
                ]"
                role="status"
            >
                <svg v-if="toast.type === 'success'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                <span>{{ toast.text }}</span>
                <button class="ml-3 opacity-70 hover:opacity-100" @click="toast = null" aria-label="Tutup notifikasi"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
        </transition>

        <aside
            :class="collapsed ? 'w-[64px]' : 'w-[200px]'"
            class="fixed inset-y-0 left-0 z-40 hidden flex-col border-r border-[#E8ECF5] bg-white transition-[width] duration-200 lg:flex"
        >
            <div class="flex h-14 shrink-0 items-center justify-between border-b border-[#E8ECF5] px-2.5">
                <div v-if="!collapsed" class="flex items-center gap-1.5 overflow-hidden">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-[#2949B5] text-[13px] font-bold text-white">d</span>
                    <span class="whitespace-nowrap text-[15px] font-bold tracking-tight text-[#20252F]">dazo<span class="text-[#2949B5]">.id</span></span>
                </div>
                <a
                    v-else
                    href="/"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[#2949B5] text-[15px] font-bold text-white"
                    aria-label="dazo.id"
                >d</a>

                <button
                    type="button"
                    class="ml-auto flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-[#E8ECF5] bg-[#F7F9FE] text-[#7C8497] transition hover:bg-[#EDF2FF] hover:text-[#2949B5]"
                    :title="collapsed ? 'Perluas sidebar' : 'Lipat sidebar'"
                    :aria-label="collapsed ? 'Perluas sidebar' : 'Lipat sidebar'"
                    @click="toggleCollapsed"
                >
                    <Icon :name="collapsed ? 'chevron-right' : 'chevron-left'" :size="13" />
                </button>
            </div>

            <nav class="flex-1 space-y-0.5 overflow-y-auto px-2 py-3" aria-label="Menu utama">
                <template v-for="item in navigation" :key="item.name">
                    <div class="group relative">
                        <a
                            :href="item.href"
                            :title="collapsed ? item.name : undefined"
                            :class="[
                                'flex h-[38px] items-center rounded-[4px] px-2 text-[12px] font-medium transition',
                                isActive(item) ? 'bg-[#2949B5] text-white shadow-sm' : 'text-[#3E4657] hover:bg-[#EDF2FF] hover:text-[#2949B5]',
                            ]"
                        >
                            <span :class="[collapsed ? 'mx-auto' : 'icon-shell', isActive(item) ? 'text-white' : 'text-[#7C8497] group-hover:text-[#2949B5]']">
                                <Icon :name="item.icon" :size="17" />
                            </span>
                            <span v-if="!collapsed" class="flex-1 truncate">{{ item.name }}</span>
                            <Icon v-if="!collapsed && item.children" :name="'chevron-down'" :size="12" class="opacity-50" />
                        </a>
                    </div>
                </template>
            </nav>

            <div class="shrink-0 border-t border-[#E8ECF5] p-2">
                <button
                    type="button"
                    class="flex h-[38px] w-full items-center rounded-[4px] px-2 text-[12px] font-medium text-[#3E4657] transition hover:bg-[#EDF2FF] hover:text-[#2949B5]"
                    @click="logout"
                >
                    <span :class="collapsed ? 'icon-shell mx-auto' : 'icon-shell'"><Icon name="logout" :size="17" /></span>
                    <span v-if="!collapsed">Keluar</span>
                </button>
            </div>
        </aside>

        <transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150"
            leave-to-class="opacity-0"
        >
            <div v-if="drawerOpen" class="fixed inset-0 z-40 bg-black/40 lg:hidden" @click="drawerOpen = false"></div>
        </transition>

        <aside
            :class="drawerOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-50 flex w-[240px] flex-col border-r border-[#E8ECF5] bg-white transition-transform duration-200 lg:hidden"
        >
            <div class="flex h-14 shrink-0 items-center justify-between border-b border-[#E8ECF5] px-3">
                <div class="flex items-center gap-1.5">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-[#2949B5] text-[13px] font-bold text-white">d</span>
                    <span class="text-[15px] font-bold tracking-tight text-[#20252F]">dazo<span class="text-[#2949B5]">.id</span></span>
                </div>
                <button type="button" class="flex h-8 w-8 items-center justify-center rounded-md text-[#7C8497] hover:bg-[#F0F3FA]" @click="drawerOpen = false" aria-label="Tutup menu">
                    <Icon name="x" :size="18" />
                </button>
            </div>
            <nav class="flex-1 space-y-0.5 overflow-y-auto px-2 py-3">
                <a
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    :class="['flex h-[40px] items-center gap-3 rounded-[4px] px-3 text-[13px] font-medium', isActive(item) ? 'bg-[#2949B5] text-white' : 'text-[#3E4657] hover:bg-[#EDF2FF]']"
                >
                    <span :class="isActive(item) ? 'text-white' : 'text-[#7C8497]'"><Icon :name="item.icon" :size="18" /></span>
                    <span class="flex-1">{{ item.name }}</span>
                    <Icon v-if="item.children" name="chevron-down" :size="12" class="opacity-50" />
                </a>
            </nav>
            <div class="shrink-0 border-t border-[#E8ECF5] p-3">
                <button type="button" class="flex w-full items-center gap-3 rounded-[4px] px-3 py-2 text-[13px] font-medium text-[#3E4657] hover:bg-[#EDF2FF]" @click="logout">
                    <span class="text-[#7C8497]"><Icon name="logout" :size="18" /></span>
                    Keluar
                </button>
            </div>
        </aside>

        <div :class="collapsed ? 'lg:pl-[64px]' : 'lg:pl-[200px]'" class="transition-[padding] duration-200">
            <header class="sticky top-0 z-30 flex h-14 items-center justify-between gap-3 border-b border-[#E8ECF5] bg-[#F7F9FE]/85 px-4 backdrop-blur lg:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-[#3E4657] hover:bg-[#EDF2FF] lg:hidden" @click="drawerOpen = true" aria-label="Buka menu">
                        <Icon name="menu" :size="20" />
                    </button>
                    <slot name="header-title" />
                </div>

                <div class="flex items-center gap-2">
                    <slot name="header-actions" />

                    <div class="hidden items-center gap-1 sm:flex">
                        <button type="button" class="flex h-9 w-9 items-center justify-center rounded-md text-[#7C8497] transition hover:bg-[#EDF2FF] hover:text-[#2949B5]" title="Promo & hadiah" aria-label="Promo & hadiah">
                            <Icon name="gift" :size="19" />
                        </button>
                        <button type="button" class="flex h-9 w-9 items-center justify-center rounded-md text-[#7C8497] transition hover:bg-[#EDF2FF] hover:text-[#2949B5]" title="Dokumentasi & bantuan" aria-label="Dokumentasi & bantuan">
                            <Icon name="help" :size="19" />
                        </button>
                        <button type="button" class="flex h-9 w-9 items-center justify-center rounded-md text-[#7C8497] transition hover:bg-[#EDF2FF] hover:text-[#2949B5]" :title="isFullscreen ? 'Keluar layar penuh' : 'Layar penuh'" :aria-label="isFullscreen ? 'Keluar layar penuh' : 'Layar penuh'" @click="toggleFullscreen">
                            <Icon name="expand" :size="18" />
                        </button>
                    </div>

                    <div class="relative hidden sm:block" data-dropdown>
                        <button
                            type="button"
                            class="flex h-9 items-center gap-2 rounded-md border border-[#E8ECF5] bg-white px-3 text-[12px] font-semibold text-[#3E4657] shadow-sm transition hover:border-[#C9D6F5] hover:text-[#2949B5]"
                            @click.stop="fullscreenOpen = !fullscreenOpen"
                        >
                            <Icon name="briefcase" :size="16" class="text-[#2949B5]" />
                            <span class="whitespace-nowrap">Business Package</span>
                            <Icon name="chevron-down" :size="12" class="opacity-60" />
                        </button>
                        <transition
                            enter-active-class="transition ease-out duration-150"
                            enter-from-class="opacity-0 translate-y-1"
                            leave-active-class="transition ease-in duration-100"
                            leave-to-class="opacity-0 translate-y-1"
                        >
                            <div v-if="fullscreenOpen" class="absolute right-0 top-11 z-20 w-56 overflow-hidden rounded-md border border-[#E8ECF5] bg-white shadow-xl">
                                <div class="border-b border-[#E8ECF5] px-3 py-2 text-[11px] font-semibold text-[#7C8497]">Pilihan Paket</div>
                                <button v-for="pkg in businessPackages" :key="pkg.label" type="button" class="flex w-full items-center gap-2 px-3 py-2 text-left transition hover:bg-[#F0F3FA]" @click="fullscreenOpen = false">
                                    <div class="min-w-0 flex-1">
                                        <p class="flex items-center gap-1.5 text-[12px] font-semibold text-[#20252F]">
                                            <Icon name="briefcase" :size="14" class="text-[#2949B5]" />
                                            {{ pkg.label }}
                                        </p>
                                        <p class="mt-0.5 truncate text-[11px] text-[#7C8497]">{{ pkg.desc }}</p>
                                    </div>
                                    <span v-if="pkg.active" class="rounded-full bg-[#E7EDFF] px-2 py-0.5 text-[10px] font-semibold text-[#2949B5]">Aktif</span>
                                </button>
                            </div>
                        </transition>
                    </div>

                    <a href="/" class="ml-1 flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#2949B5] text-[13px] font-bold text-white" :title="user?.name || 'Akun'">
                        {{ (user?.name || '?').charAt(0) }}
                    </a>
                </div>
            </header>

            <main class="p-4 lg:p-6">
                <slot />
            </main>
        </div>

        <ChatWidget />
    </div>
</template>

<style scoped>
.icon-shell {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
</style>