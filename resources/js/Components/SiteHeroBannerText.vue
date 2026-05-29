<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
});
</script>

<template>
    <Link
        href="/"
        class="absolute inset-x-0 top-4 z-[255] flex justify-center px-14 md:hidden"
        aria-label="Justus Group Home"
    >
        <img
            src="/logohitam.png"
            alt="Justus Group"
            class="h-auto w-[148px] max-w-[calc(100%-7rem)] object-contain"
        />
    </Link>

    <div class="hero-copy mx-auto flex w-full max-w-7xl flex-1 flex-col items-center justify-center px-3 py-8 text-center sm:px-6 sm:py-10">
        <img
            src="/logohitam.png"
            alt=""
            aria-hidden="true"
            class="mb-6 hidden h-auto w-[320px] object-contain md:block"
        />
        <h1 v-if="title" class="hero-title font-normal uppercase text-white">
            {{ title }}
        </h1>
        <p v-if="subtitle" class="hero-subtitle mt-2 font-normal italic text-white/90 md:mt-3">
            {{ subtitle }}
        </p>
        <slot />
    </div>
</template>

<style scoped>
.hero-copy {
    width: 100%;
}

.hero-title {
    font-family: 'Montserrat', Arial, Helvetica, sans-serif;
    font-size: clamp(0.8125rem, 4.1vw, 1.125rem);
    line-height: 1.2;
    letter-spacing: 0.035em;
    white-space: nowrap;
}

.hero-subtitle {
    font-family: 'Montserrat', Arial, Helvetica, sans-serif;
    font-size: clamp(0.6875rem, 3.45vw, 0.875rem);
    line-height: 1.25;
    white-space: nowrap;
}

@media (min-width: 768px) {
    .hero-title {
        font-size: 2.75rem;
        line-height: 1.08;
        letter-spacing: 0.035em;
        white-space: normal;
    }

    .hero-subtitle {
        font-size: 1.25rem;
        line-height: 1.35;
        white-space: normal;
    }
}
</style>
