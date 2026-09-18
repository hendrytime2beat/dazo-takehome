<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import Icon from '../Icon.vue'
import { formatIDR, formatNumber } from '../../Shared/format.js'

const props = defineProps({
    from: { type: String, default: '' },
    to: { type: String, default: '' },
    period: { type: String, default: '30d' },
})

const emit = defineEmits(['select'])

const open = ref(false)
const root = ref(null)

const options = [
    { key: '7d', label: '7 hari terakhir' },
    { key: '30d', label: '30 hari terakhir' },
    { key: '90d', label: '90 hari terakhir' },
    { key: 'all', label: 'Semua riwayat' },
]

function pick(key) {
    emit('select', key)
    open.value = false
}

const onDocClick = (e) => {
    if (root.value && !root.value.contains(e.target)) open.value = false
}

onMounted(() => document.addEventListener('click', onDocClick))
onUnmounted(() => document.removeEventListener('click', onDocClick))

function fmt(d) {
    if (!d) return '2000-01-01'
    const parts = d.split('-')
    if (parts.length === 3) return `${parts[2]}-${parts[1]}-${parts[0]}`
    return d
}
</script>

<template>
    <div ref="root" class="relative">
        <button
            type="button"
            class="flex h-9 items-center gap-2 rounded-md border border-[#E8ECF5] bg-white px-3 text-[12px] font-medium text-[#3E4657] shadow-sm transition hover:border-[#C9D6F5]"
            @click="open = !open"
            :aria-expanded="open"
        >
            <Icon name="calendar" :size="15" class="text-[#2949B5]" />
            <span class="whitespace-nowrap">{{ fmt(from) }} <span class="text-[#B4BACE]">s/d</span> {{ fmt(to) }}</span>
            <Icon name="chevron-down" :size="12" :class="open ? 'rotate-180' : ''" class="text-[#7C8497] transition" />
        </button>

        <transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 translate-y-1"
            leave-active-class="transition ease-in duration-100"
            leave-to-class="opacity-0 translate-y-1"
        >
            <div v-if="open" class="absolute right-0 top-11 z-20 w-52 overflow-hidden rounded-md border border-[#E8ECF5] bg-white shadow-xl">
                <div class="border-b border-[#E8ECF5] px-3 py-2 text-[11px] font-semibold text-[#7C8497]">Periode Laporan</div>
                <button
                    v-for="opt in options"
                    :key="opt.key"
                    type="button"
                    class="flex w-full items-center justify-between px-3 py-2 text-left text-[12px] text-[#3E4657] transition hover:bg-[#F0F3FA]"
                    @click="pick(opt.key)"
                >
                    {{ opt.label }}
                    <span v-if="period === opt.key" class="flex h-4 w-4 items-center justify-center rounded-full bg-[#2949B5]">
                        <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-white"><path d="m5 13 4 4L19 7" /></svg>
                    </span>
                </button>
            </div>
        </transition>
    </div>
</template>