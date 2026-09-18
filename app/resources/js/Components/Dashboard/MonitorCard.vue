<script setup>
defineProps({
    title: { type: String, required: true },
    emptyText: { type: String, default: 'Tidak ada Produk' },
})
</script>

<template>
    <div class="flex flex-col overflow-hidden rounded-[4px] border border-[#E8ECF5] bg-white">
        <div class="flex h-7 shrink-0 items-center justify-center bg-[#2949B5] px-2">
            <span class="truncate text-[10px] font-bold uppercase tracking-wide text-white">{{ title }}</span>
        </div>
        <div class="flex h-[44px] items-center justify-center gap-2 overflow-hidden px-3">
            <slot />
        </div>
    </div>
</template>