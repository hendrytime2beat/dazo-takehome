<script setup>
import { Link } from '@inertiajs/vue3'
</script>

<template>
    <main class="flex min-h-screen w-full items-center justify-center bg-white px-6 py-12 font-sans">
        <div class="w-full max-w-[420px] text-center">
            <a href="/" class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl bg-[#4F7DF9]" aria-label="DAZO Sales">
                <svg class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M8 10.5h8M8 14h5" stroke-linecap="round" />
                    <path d="M20.5 7.5h-17c-.6 0-1 .6-1 1.1v8.8c0 .6.4 1.1 1 1.1h5.5l1.7 2.3c.3.4.9.4 1.2 0l1.7-2.3h6.9c.6 0 1-.5 1-1.1V8.6c0-.6-.5-1.1-1-1.1Z" />
                </svg>
            </a>
            <h1 class="mt-8 text-2xl font-semibold text-slate-900">Lupa Password</h1>
            <p class="mt-2 text-[13px] leading-relaxed text-slate-500">
                Pada versi demo ini pengiriman email reset password belum diaktifkan. Gunakan kredensial demo
                <span class="font-medium text-slate-700">admin@dazo.test / password</span> untuk masuk.
            </p>
            <Link href="/login" class="mt-6 inline-flex h-10 items-center justify-center rounded-md bg-[#4F7DF9] px-6 text-[13px] font-semibold text-white transition hover:bg-[#3D6BEC]">
                Kembali ke Login
            </Link>
        </div>
    </main>
</template>