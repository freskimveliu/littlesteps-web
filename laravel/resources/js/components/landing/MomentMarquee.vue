<script setup lang="ts">
import Ion from './Ion.vue';
import type { IconName } from './icons';

interface Moment {
    label: string;
    icon: IconName;
    color: string;
}

const ROW_ONE: Moment[] = [
    { label: 'Coming Home', icon: 'heart', color: '#E23C68' },
    { label: 'First Smile', icon: 'happy', color: '#C08400' },
    { label: 'Favorite Lullaby', icon: 'musical-notes', color: '#9C8A00' },
    { label: 'Belly Laughs', icon: 'sunny', color: '#2F91C4' },
    { label: 'First Solid Food', icon: 'restaurant-outline', color: '#9C8A00' },
    { label: 'Peek-a-Boo!', icon: 'eye', color: '#8A5CD1' },
    { label: 'First Steps', icon: 'footsteps', color: '#C08400' },
    { label: 'First Word', icon: 'chatbubbles', color: '#C08400' },
    { label: 'Comfort Object', icon: 'bed', color: '#2F91C4' },
    { label: 'First Birthday', icon: 'gift', color: '#CF5B12' },
];

const ROW_TWO: Moment[] = [
    { label: 'First Scribble', icon: 'color-palette', color: '#8A5CD1' },
    { label: "First 'I Love You'", icon: 'heart-circle', color: '#E23C68' },
    { label: 'Their Fears', icon: 'moon', color: '#2F91C4' },
    { label: 'Pretend Play', icon: 'telescope', color: '#8A5CD1' },
    { label: 'Family Tradition', icon: 'people', color: '#E23C68' },
    { label: 'First Day of School', icon: 'school', color: '#C08400' },
    { label: 'First Lost Tooth', icon: 'star', color: '#C08400' },
    { label: 'Biggest Dream', icon: 'bulb', color: '#9C8A00' },
    { label: 'Letter to Future Self', icon: 'mail', color: '#E23C68' },
    { label: 'Preschool Graduation', icon: 'medal', color: '#CF5B12' },
];
</script>

<template>
    <div class="relative">
        <div class="pointer-events-none absolute inset-x-0 top-16 -z-10 h-64">
            <div class="drift absolute top-0 left-1/2 h-64 w-[46rem] -translate-x-1/2 rounded-full bg-primary/8 blur-3xl" />
        </div>

        <p class="px-6 text-center font-display text-2xl font-extrabold text-ink sm:text-3xl">
            A childhood is made of
            <span class="text-primary">small things</span>
        </p>
        <p class="mt-2 px-6 text-center text-sm text-ink/50">
            LittleSteps asks about them one at a time, while they are still true
        </p>

        <!-- The padding keeps the pill edges and their shadows clear of the mask box. -->
        <div
            class="marquee relative mt-7 space-y-3 py-4"
            style="
                mask-image: linear-gradient(90deg, transparent, #000 14%, #000 86%, transparent);
                -webkit-mask-image: linear-gradient(90deg, transparent, #000 14%, #000 86%, transparent);
                mask-size: 100% 100%;
                -webkit-mask-size: 100% 100%;
                mask-repeat: no-repeat;
                -webkit-mask-repeat: no-repeat;
            "
        >
            <div class="marquee-track gap-3">
                <span
                    v-for="(moment, i) in [...ROW_ONE, ...ROW_ONE]"
                    :key="`a-${i}`"
                    class="flex shrink-0 items-center gap-2.5 rounded-full bg-white px-5 py-3 shadow-sm shadow-primary/10 ring-1 ring-ink/5"
                >
                    <Ion :name="moment.icon" :size="16" :style="{ color: moment.color }" />
                    <span class="font-display text-lg font-bold whitespace-nowrap text-ink/80">{{ moment.label }}</span>
                </span>
            </div>

            <div class="marquee-track marquee-track--reverse gap-3">
                <span
                    v-for="(moment, i) in [...ROW_TWO, ...ROW_TWO]"
                    :key="`b-${i}`"
                    class="flex shrink-0 items-center gap-2.5 rounded-full bg-white px-5 py-3 shadow-sm shadow-primary/10 ring-1 ring-ink/5"
                >
                    <Ion :name="moment.icon" :size="16" :style="{ color: moment.color }" />
                    <span class="font-display text-lg font-bold whitespace-nowrap text-ink/80">{{ moment.label }}</span>
                </span>
            </div>
        </div>
    </div>
</template>
