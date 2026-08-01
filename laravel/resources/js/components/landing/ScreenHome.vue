<script setup lang="ts">
import Ion from './Ion.vue';
import KidAvatar from './KidAvatar.vue';
import PhoneTabBar from './PhoneTabBar.vue';

const STATS = [
    { value: '34/48', label: 'Steps' },
    { value: '62', label: 'Memories' },
    { value: '9', label: 'Badges' },
];
</script>

<template>
    <div class="absolute inset-0 top-9 overflow-hidden bg-gray-50">
        <!-- child hero -->
        <div class="relative mx-4 mt-2 overflow-hidden rounded-3xl bg-linear-135 from-primary to-violet-soft p-4 pb-5">
            <div class="absolute -top-10 -right-8 h-28 w-28 rounded-full bg-white/10" />
            <div class="absolute -bottom-12 right-10 h-24 w-24 rounded-full bg-butter/10" />

            <div class="relative flex items-center justify-between">
                <span class="flex items-center gap-1 rounded-full bg-white/20 px-2.5 py-1 text-[10px] font-extrabold text-white">
                    <Ion name="shield-checkmark" :size="11" />
                    LEVEL 4
                </span>
                <span class="flex items-center gap-1 rounded-full bg-white/20 px-2.5 py-1 text-[10px] font-extrabold text-white">
                    <Ion name="star" :size="11" class="text-butter" />
                    1,240 XP
                </span>
            </div>

            <div class="relative mt-3 flex items-center">
                <div class="rounded-full bg-white/30 p-1">
                    <KidAvatar :size="52" variant="a" />
                </div>
                <div class="ml-3 flex-1">
                    <p class="font-display text-xl leading-tight font-extrabold text-white">Elena</p>
                    <p class="text-[11px] text-white/70">2 years, 4 months</p>
                    <p class="mt-0.5 flex items-center gap-1 text-[10px] font-bold text-white/70">
                        <Ion name="map" :size="10" />
                        Little Talker Stage
                    </p>
                </div>
                <div class="flex flex-col items-center">
                    <Ion name="flame" :size="20" class="flicker text-[#FF6B6B]" />
                    <span class="font-display text-base leading-none font-extrabold text-white">12</span>
                    <span class="text-[9px] text-white/50">Streak</span>
                </div>
            </div>

            <div class="relative mt-3">
                <div class="flex items-center justify-between text-[10px] font-bold text-white/80">
                    <span>Moment Collector</span>
                    <span>490 / 650</span>
                </div>
                <div class="mt-1 h-2 overflow-hidden rounded-full bg-black/20">
                    <div class="h-full w-[75%] rounded-full bg-butter" />
                </div>
            </div>

            <div class="relative mt-3 flex gap-2">
                <div
                    v-for="stat in STATS"
                    :key="stat.label"
                    class="flex-1 rounded-2xl bg-white/15 py-2 text-center"
                >
                    <p class="font-display text-sm leading-none font-extrabold text-white">{{ stat.value }}</p>
                    <p class="mt-1 text-[9px] text-white/60">{{ stat.label }}</p>
                </div>
            </div>
        </div>

        <!-- today's mission -->
        <div class="mt-4 px-4">
            <div class="mb-2 flex items-center gap-2">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-[#FBBF24]">
                    <Ion name="compass" :size="11" class="text-white" />
                </span>
                <span class="text-[10px] font-extrabold tracking-widest text-gray-500">TODAY'S MISSION</span>
            </div>

            <div class="relative overflow-hidden rounded-3xl border border-amber-200 bg-linear-135 from-amber-100 via-amber-50 to-yellow-100 p-3.5">
                <Ion name="sparkles" :size="16" class="absolute top-3 right-3.5 text-amber-500/30" />
                <div class="flex items-start gap-2.5">
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#FBBF24] shadow-md shadow-amber-500/30">
                        <Ion name="compass" :size="18" class="text-white" />
                    </span>
                    <p class="font-display text-[13px] leading-snug font-bold text-amber-900">
                        What made your little one smile today?
                    </p>
                </div>
                <div class="mt-3 flex items-center justify-between">
                    <span class="flex items-center gap-1 rounded-full bg-white/70 px-2.5 py-1 text-[10px] font-extrabold text-amber-600">
                        <Ion name="star" :size="10" />
                        +15 XP
                    </span>
                    <span class="flex items-center gap-1 rounded-full bg-amber-500 px-3.5 py-1.5 text-[11px] font-extrabold text-white shadow-md shadow-amber-500/40">
                        <Ion name="arrow-forward" :size="12" />
                        Start Quest
                    </span>
                </div>
            </div>
        </div>

        <!-- adventure path -->
        <div class="mt-4 px-4">
            <div class="mb-2 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-primary">
                        <Ion name="map" :size="11" class="text-white" />
                    </span>
                    <span class="text-[10px] font-extrabold tracking-widest text-gray-500">ADVENTURE PATH</span>
                </div>
                <span class="text-[10px] font-bold text-primary">See map</span>
            </div>

            <div class="relative overflow-hidden rounded-3xl border border-primary/15 bg-linear-135 from-primary/12 via-white to-primary/8 p-3">
                <div class="absolute -top-8 -right-5 h-24 w-24 rounded-full bg-primary/5" />
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#8f6300] shadow-md shadow-amber-900/25">
                        <Ion name="chatbubbles" :size="22" class="text-white" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5">
                            <span class="rounded-full bg-[#C08400]/25 px-1.5 py-0.5 text-[9px] font-extrabold text-[#6b4a00]">Firsts</span>
                            <span class="flex items-center gap-0.5 rounded-full bg-amber-50 px-1.5 py-0.5 text-[9px] font-extrabold text-amber-600">
                                <Ion name="star" :size="8" />
                                +40
                            </span>
                        </div>
                        <p class="mt-0.5 font-display text-[14px] leading-tight font-extrabold text-gray-900">First Sentence</p>
                        <p class="truncate text-[10px] text-gray-500">Two words became a whole thought.</p>
                    </div>
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary">
                        <Ion name="arrow-forward" :size="15" class="text-white" />
                    </span>
                </div>
            </div>

            <div class="mt-2 ml-6 space-y-1.5 border-l-2 border-primary/12 pl-5">
                <div v-for="(step, i) in ['First Show of Empathy', 'Favorite Bedtime Story']" :key="step" class="flex items-center gap-2.5 py-0.5">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-primary/15 bg-gray-100 text-[9px] font-extrabold text-gray-400">
                        {{ i + 2 }}
                    </span>
                    <span class="text-[11px] font-medium text-gray-400">{{ step }}</span>
                </div>
            </div>
        </div>

        <PhoneTabBar active="home" />
    </div>
</template>
