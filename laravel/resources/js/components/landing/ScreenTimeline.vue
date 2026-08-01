<script setup lang="ts">
import Ion from './Ion.vue';
import PhoneTabBar from './PhoneTabBar.vue';
import PhotoStub from './PhotoStub.vue';
</script>

<template>
    <div class="absolute inset-0 top-9 overflow-hidden bg-gray-50">
        <div class="px-5 pt-2">
            <h3 class="font-display text-[22px] leading-tight font-extrabold text-gray-900">Story So Far</h3>
            <p class="text-[11px] text-gray-500">Elena's adventure through time</p>
            <div class="mt-2 flex gap-2">
                <span class="flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-[10px] font-bold text-primary">
                    <Ion name="book" :size="10" />
                    62 memories
                </span>
                <span class="flex items-center gap-1 rounded-full bg-butter/50 px-2.5 py-1 text-[10px] font-bold text-[#8a6a00]">
                    <Ion name="images" :size="10" />
                    148 photos
                </span>
            </div>
        </div>

        <div class="mt-4 pl-5">
            <div class="relative border-l-2 border-primary/15 pl-4">
                <span class="absolute -left-[7px] top-1 h-3 w-3 rounded-full border-2 border-white bg-primary" />
                <p class="text-[10px] font-extrabold tracking-widest text-primary/60">TODAY</p>

                <div class="mt-2 mr-4 overflow-hidden rounded-3xl bg-white shadow-sm shadow-primary/10">
                    <PhotoStub variant="meadow" class="h-24 w-full" />
                    <div class="p-3">
                        <div class="flex items-center gap-1.5">
                            <span class="flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-[9px] font-extrabold text-primary">
                                <Ion name="star" :size="8" />
                                First Sentence
                            </span>
                            <span class="flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[9px] font-extrabold text-amber-600">
                                <Ion name="happy" :size="9" />
                                Joyful
                            </span>
                        </div>
                        <p class="mt-1.5 font-display text-[14px] leading-tight font-extrabold text-gray-900">
                            "More berries please!"
                        </p>
                        <p class="mt-0.5 text-[10px] leading-snug text-gray-500">
                            Four words, one small hand pointing at the bowl, and the whole kitchen went quiet.
                        </p>
                    </div>
                </div>

                <span class="absolute -left-[7px] top-[268px] h-3 w-3 rounded-full border-2 border-white bg-primary/40" />
                <p class="mt-4 text-[10px] font-extrabold tracking-widest text-gray-400">3 DAYS AGO</p>

                <div class="mt-2 mr-4 flex gap-3 overflow-hidden rounded-3xl bg-white p-3 shadow-sm shadow-primary/10">
                    <PhotoStub variant="nursery" class="h-16 w-16 shrink-0 rounded-2xl" />
                    <div class="min-w-0 flex-1">
                        <span class="flex w-fit items-center gap-1 rounded-full bg-[#2E9E5B]/15 px-2 py-0.5 text-[9px] font-extrabold text-[#1d6b3c]">
                            <Ion name="trending-up" :size="8" />
                            2 Year Measurements
                        </span>
                        <p class="mt-1 font-display text-[13px] leading-tight font-extrabold text-gray-900">
                            12.4 kg · 88 cm
                        </p>
                        <p class="mt-0.5 text-[10px] text-gray-500">Right on the line she's been walking since birth.</p>
                    </div>
                </div>

                <div class="mt-3 mr-4 flex gap-3 overflow-hidden rounded-3xl bg-white p-3 opacity-70 shadow-sm">
                    <PhotoStub variant="evening" class="h-16 w-16 shrink-0 rounded-2xl" />
                    <div class="min-w-0 flex-1">
                        <span class="flex w-fit items-center gap-1 rounded-full bg-[#E23C68]/15 px-2 py-0.5 text-[9px] font-extrabold text-[#a01f45]">
                            <Ion name="heart" :size="8" />
                            Family Tradition
                        </span>
                        <p class="mt-1 font-display text-[13px] leading-tight font-extrabold text-gray-900">
                            Sunday pancakes
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <PhoneTabBar active="timeline" />
    </div>
</template>
