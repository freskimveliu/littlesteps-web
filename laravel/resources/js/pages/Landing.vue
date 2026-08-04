<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import { useReveal } from '@/composables/useReveal';
import BrandMark from '@/components/landing/BrandMark.vue';
import FaqItem from '@/components/landing/FaqItem.vue';
import Ion from '@/components/landing/Ion.vue';
import KidAvatar from '@/components/landing/KidAvatar.vue';
import LandingNav from '@/components/landing/LandingNav.vue';
import MomentMarquee from '@/components/landing/MomentMarquee.vue';
import PhoneFrame from '@/components/landing/PhoneFrame.vue';
import PhotoStub from '@/components/landing/PhotoStub.vue';
import ScreenAwards from '@/components/landing/ScreenAwards.vue';
import ScreenHome from '@/components/landing/ScreenHome.vue';
import ScreenMap from '@/components/landing/ScreenMap.vue';
import ScreenTimeline from '@/components/landing/ScreenTimeline.vue';
import StoreButtons from '@/components/landing/StoreButtons.vue';
import type { IconName } from '@/components/landing/icons';

useReveal();

const HOW: { icon: IconName; title: string; body: string }[] = [
    {
        icon: 'happy',
        title: 'Add your little one',
        body: 'Name, birthday, a photo if you have one. We build their map from their age. No setup, no blank page.',
    },
    {
        icon: 'map',
        title: 'Follow the path',
        body: 'A winding map of everything worth remembering, one chapter at a time. Tap the glowing milestone to capture it.',
    },
    {
        icon: 'camera',
        title: 'One moment a day',
        body: 'A photo, a sentence, a number on the scale. Sixty seconds today becomes the story you read together in fifteen years.',
    },
];

const CHAPTERS: { name: string; age: string; blurb: string; stage: string; color: string; icon: IconName }[] = [
    { name: 'The First Hello', age: '0–3 months', blurb: 'Those first blurry, sleepless, wonderful weeks', stage: 'Newborn', color: '#FFB5C2', icon: 'happy' },
    { name: 'Waking to the World', age: '3–6 months', blurb: 'Laughs, rolls, and the first real look around', stage: 'Explorer', color: '#FFCBA4', icon: 'search' },
    { name: 'On the Move', age: '6–12 months', blurb: 'Sitting, crawling, and suddenly nowhere is safe', stage: 'Adventurer', color: '#FFE5A0', icon: 'sunny' },
    { name: 'Little Explorer', age: '1–2 years', blurb: 'Walking, talking, and testing every limit', stage: 'Toddler', color: '#B5EAD7', icon: 'walk' },
    { name: 'Words and Wonder', age: '2–3 years', blurb: 'Questions, opinions, and very big feelings', stage: 'Little Talker', color: '#A0D8EF', icon: 'chatbubbles' },
    { name: 'A Mind of Their Own', age: '3–4 years', blurb: 'Why? Why? Why? — and stories all their own', stage: 'Dreamer', color: '#C5B3E6', icon: 'cloud' },
    { name: 'Making and Believing', age: '4–5 years', blurb: 'Drawing, building, pretending, befriending', stage: 'Creator', color: '#F5B7C5', icon: 'color-palette' },
    { name: 'Ready for the World', age: '5–6 years', blurb: 'School, a lost tooth, and one very big kid', stage: 'Big Kid', color: '#87CEEB', icon: 'star' },
];

const CATEGORIES: { name: string; description: string; color: string; icon: IconName }[] = [
    { name: 'Firsts', description: 'First smile, first steps, first word', color: '#C08400', icon: 'sparkles' },
    { name: 'Growth', description: 'Weight, height, teeth, shoe size', color: '#2E9E5B', icon: 'trending-up' },
    { name: 'Family', description: 'Family tree, visitors, letters to baby', color: '#E23C68', icon: 'heart' },
    { name: 'Events', description: 'Birthdays, holidays, trips, ceremonies', color: '#CF5B12', icon: 'gift' },
    { name: 'Daily Life', description: 'Favorite foods, routines, funny habits', color: '#9C8A00', icon: 'sunny' },
    { name: 'Emotional', description: 'What makes them laugh, fears, comfort objects', color: '#2F91C4', icon: 'cloud' },
    { name: 'Creative', description: 'First drawing, favorite songs, pretend play', color: '#8A5CD1', icon: 'color-palette' },
    { name: 'Medical', description: 'Vaccinations, doctor visits, allergies', color: '#DC3A3A', icon: 'medkit' },
];

const LEVELS: { name: string; xp: number; icon: IconName }[] = [
    { name: 'New Explorer', xp: 0, icon: 'leaf' },
    { name: 'Memory Maker', xp: 120, icon: 'camera' },
    { name: 'Story Keeper', xp: 350, icon: 'book' },
    { name: 'Moment Collector', xp: 750, icon: 'albums' },
    { name: 'Rising Star', xp: 1400, icon: 'shield-checkmark' },
    { name: 'Memory Wizard', xp: 2400, icon: 'sparkles' },
    { name: 'Chronicle Keeper', xp: 3800, icon: 'library' },
    { name: 'Storyteller', xp: 5600, icon: 'chatbubbles' },
    { name: 'Time Traveller', xp: 8000, icon: 'time' },
    { name: 'Master Archivist', xp: 11000, icon: 'folder-open' },
    { name: 'Keeper of Years', xp: 14800, icon: 'calendar' },
    { name: 'Legend', xp: 19500, icon: 'diamond' },
    { name: 'Hall of Fame', xp: 25000, icon: 'trophy' },
    { name: 'Living Legacy', xp: 32000, icon: 'star' },
];

const TROPHIES: { name: string; description: string; icon: IconName }[] = [
    { name: 'First Week', description: 'A memory on seven different days', icon: 'flame' },
    { name: 'First Chapter', description: 'You finished a whole chapter', icon: 'ribbon' },
    { name: 'Shutterbug', description: 'Twenty-five photos kept', icon: 'camera' },
    { name: 'Right on Time', description: 'Ten milestones caught at the right age', icon: 'today' },
    { name: 'Hundred Days', description: 'A memory on a hundred days', icon: 'flash' },
    { name: 'Every Corner', description: 'Sixty milestones caught at the right age', icon: 'bookmarks' },
    { name: 'Never Missed a Beat', description: 'Eighty milestones caught at the right age', icon: 'time' },
    { name: 'The Whole Map', description: 'Every chapter, start to finish', icon: 'sparkles' },
];

const FEATURES: { icon: IconName; title: string; body: string; tone?: 'wide' }[] = [
    {
        icon: 'people',
        title: 'One app, every child',
        body: 'Each child gets their own map, their own XP and their own trophies, and switching between them takes a tap.',
    },
    {
        icon: 'trending-up',
        title: 'Growth that draws itself',
        body: 'Log a weight or a height inside a milestone and the chart on the home screen fills in behind it. No spreadsheet, no separate app.',
    },
    {
        icon: 'shield-checkmark-outline',
        title: 'Safe even if the phone is not',
        body: 'Every memory and photo is kept in your own private account rather than on the handset alone. Add an email and the whole map follows you to the next phone.',
    },
    {
        icon: 'color-wand',
        title: 'Bend the map to your family',
        body: 'Rename anything, move a milestone to another chapter, add one of your own, or take off what will never apply. The guided map is a starting point, not a cage.',
    },
    {
        icon: 'image',
        title: 'Recap cards worth sending',
        body: 'Turn any captured memory into a designed card (photo, date, mood, chapter) and share it with the grandparents in two taps.',
    },
    {
        icon: 'notifications-outline',
        title: 'Nudges, never nagging',
        body: 'Milestone reminders, a daily quest, a streak alert at bedtime. Each one is a switch you own, and off is a perfectly good setting.',
    },
];
</script>

<template>
    <Head>
        <title>LittleSteps: every little step, kept forever</title>
        <meta
            name="description"
            content="LittleSteps turns your child's first six years into a guided adventure: gentle prompts for every age, one memory a day, trophies, streaks and a map you'll still be scrolling in twenty years."
        />
        <meta name="theme-color" content="#7E5EBF" />
    </Head>

    <div class="landing-root min-h-dvh overflow-x-clip bg-canvas font-sans text-ink">
        <LandingNav />

        <!-- ─────────────────────────────────────────────────────────── hero -->
        <section id="top" class="relative overflow-hidden px-5 pt-28 pb-20 lg:px-10 lg:pt-36">
            <div class="pointer-events-none absolute inset-0 z-0">
                <div class="drift absolute -top-40 -left-32 h-[34rem] w-[34rem] rounded-full bg-primary/25 blur-3xl" />
                <div class="drift absolute -top-20 right-0 h-[28rem] w-[28rem] rounded-full bg-butter/60 blur-3xl" style="--float-delay: -6s" />
                <div class="drift absolute top-[28rem] left-1/3 h-[26rem] w-[26rem] rounded-full bg-violet-soft/20 blur-3xl" style="--float-delay: -12s" />
                <div
                    class="absolute inset-0 opacity-[0.35]"
                    style="
                        background-image: radial-gradient(rgba(126, 94, 191, 0.18) 1px, transparent 1px);
                        background-size: 26px 26px;
                        mask-image: radial-gradient(70% 60% at 50% 30%, #000, transparent);
                    "
                />
            </div>

            <div class="relative z-10 mx-auto grid max-w-7xl items-center gap-16 lg:grid-cols-[1.05fr_1fr]">
                <div>
                    <span
                        class="reveal inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-xs font-bold tracking-wide text-primary shadow-sm shadow-primary/10 ring-1 ring-primary/10"
                    >
                        <Ion name="sparkles" :size="14" />
                        A keepsake for the first six years
                    </span>

                    <h1
                        class="reveal mt-6 font-display text-[2.6rem] leading-[0.95] font-extrabold tracking-tight text-ink sm:text-6xl lg:text-[4.75rem]"
                        style="--reveal-delay: 80ms"
                    >
                        Every little step,<br />
                        <span class="relative inline-block">
                            <span class="relative z-10 text-primary">kept forever</span>
                            <svg class="absolute -bottom-2 left-0 z-0 w-full" height="18" viewBox="0 0 300 18" fill="none" preserveAspectRatio="none">
                                <path d="M3 12c60-8 140-11 294-6" stroke="#FFE5A0" stroke-width="9" stroke-linecap="round" />
                            </svg>
                        </span>
                    </h1>

                    <p class="reveal mt-7 max-w-xl text-lg leading-relaxed text-ink/60" style="--reveal-delay: 160ms">
                        The photos pile up in a camera roll nobody scrolls. LittleSteps turns them into a story:
                        a guided map of <strong class="font-semibold text-ink">the moments worth keeping</strong>,
                        one gentle prompt a day, from the first hello to the first day of school.
                    </p>

                    <div class="reveal mt-9" style="--reveal-delay: 240ms">
                        <StoreButtons />
                    </div>

                    <div class="reveal mt-7 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-ink/50" style="--reveal-delay: 320ms">
                        <span class="flex items-center gap-2">
                            <Ion name="checkmark-circle" :size="17" class="text-primary" />
                            Free to start
                        </span>
                        <span class="flex items-center gap-2">
                            <Ion name="lock-closed" :size="15" class="text-primary" />
                            Your memories stay yours
                        </span>
                        <span class="flex items-center gap-2">
                            <Ion name="hourglass" :size="15" class="text-primary" />
                            One gentle prompt a day
                        </span>
                    </div>
                </div>

                <!-- hero device -->
                <div class="reveal flex justify-center lg:justify-end" style="--reveal-delay: 200ms">
                    <div class="relative">
                        <div class="absolute -inset-10 z-0 rounded-full bg-primary/10 blur-3xl" />

                        <div class="relative z-10">
                            <PhoneFrame>
                                <ScreenHome />
                            </PhoneFrame>
                        </div>

                        <div class="float-slow absolute -top-10 -left-24 z-20 hidden w-52 rounded-3xl bg-white p-3 shadow-2xl shadow-primary/20 sm:block">
                            <div class="flex items-center gap-2.5">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary">
                                    <Ion name="ribbon" :size="19" class="text-white" />
                                </span>
                                <div>
                                    <p class="text-[10px] font-extrabold tracking-widest text-primary/60">TROPHY EARNED</p>
                                    <p class="font-display text-sm font-extrabold text-ink">First Chapter</p>
                                </div>
                            </div>
                            <div class="mt-2 flex w-fit items-center gap-1 rounded-full bg-butter/60 px-2.5 py-1 text-[11px] font-extrabold text-primary">
                                <Ion name="star" :size="11" />
                                +200 XP
                            </div>
                        </div>

                        <div
                            class="float-fast absolute -right-10 bottom-28 z-20 hidden items-center gap-2.5 rounded-3xl bg-white px-4 py-3 shadow-2xl shadow-primary/20 sm:flex"
                            style="--float-delay: -1.5s"
                        >
                            <Ion name="flame" :size="26" class="flicker text-[#FF6B6B]" />
                            <div>
                                <p class="font-display text-xl leading-none font-extrabold text-ink">12</p>
                                <p class="text-[10px] font-bold text-ink/40">day streak</p>
                            </div>
                        </div>

                        <div
                            class="float-slow absolute -bottom-6 -left-16 z-20 hidden items-center gap-2 rounded-2xl bg-ink px-4 py-2.5 shadow-2xl shadow-ink/25 md:flex"
                            style="--float-delay: -3s"
                        >
                            <Ion name="star" :size="14" class="text-butter" />
                            <span class="font-display text-sm font-extrabold text-white">+40 XP</span>
                            <span class="text-[11px] text-white/50">First Sentence</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ──────────────────────────────────────────────────────── moments -->
        <section class="overflow-hidden pb-24">
            <div class="reveal">
                <MomentMarquee />
            </div>
        </section>

        <!-- ───────────────────────────────────────────────────── how it works -->
        <section class="px-5 pb-28 lg:px-10">
            <div class="mx-auto max-w-7xl">
                <div class="reveal mx-auto max-w-2xl text-center">
                    <p class="text-xs font-extrabold tracking-[0.2em] text-primary/70 uppercase">How it works</p>
                    <h2 class="mt-3 font-display text-4xl font-extrabold tracking-tight text-ink sm:text-5xl">
                        Sixty seconds a day. That's the whole ask.
                    </h2>
                </div>

                <div class="mt-14 grid gap-6 md:grid-cols-3">
                    <div
                        v-for="(item, i) in HOW"
                        :key="item.title"
                        class="reveal group relative overflow-hidden rounded-[2rem] bg-white p-8 shadow-sm shadow-primary/5 transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-primary/15"
                        :style="`--reveal-delay: ${i * 110}ms`"
                    >
                        <span class="absolute -top-6 -right-3 font-display text-[7rem] leading-none font-extrabold text-primary/5">
                            {{ i + 1 }}
                        </span>
                        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary transition-colors duration-300 group-hover:bg-primary group-hover:text-white">
                            <Ion :name="item.icon" :size="26" />
                        </span>
                        <h3 class="relative mt-6 font-display text-2xl font-extrabold text-ink">{{ item.title }}</h3>
                        <p class="relative mt-3 leading-relaxed text-ink/55">{{ item.body }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ──────────────────────────────────────────────────────────── map -->
        <section id="map" class="relative overflow-hidden px-5 py-28 lg:px-10">
            <div class="pointer-events-none absolute inset-0 z-0 bg-linear-180 from-transparent via-primary/5 to-transparent" />

            <div class="relative z-10 mx-auto grid max-w-7xl items-center gap-16 lg:grid-cols-2">
                <div class="reveal order-2 flex justify-center lg:order-1">
                    <div class="relative">
                        <div class="absolute -inset-8 z-0 rounded-[3rem] bg-butter/40 blur-3xl" />
                        <div class="relative z-10">
                            <PhoneFrame>
                                <ScreenMap />
                            </PhoneFrame>
                        </div>
                    </div>
                </div>

                <div class="order-1 lg:order-2">
                    <p class="reveal text-xs font-extrabold tracking-[0.2em] text-primary/70 uppercase">The adventure map</p>
                    <h2 class="reveal mt-3 font-display text-4xl font-extrabold tracking-tight text-ink sm:text-5xl" style="--reveal-delay: 60ms">
                        A path, not a blank page
                    </h2>
                    <p class="reveal mt-5 text-lg leading-relaxed text-ink/60" style="--reveal-delay: 120ms">
                        Every baby book starts with an empty line and the question <em>what was I supposed to write here?</em>
                        LittleSteps answers it. Your child's map already knows what comes next. It just waits for you at the
                        glowing milestone.
                    </p>

                    <ul class="mt-9 space-y-5">
                        <li
                            v-for="(item, i) in [
                                { icon: 'footsteps', title: 'It moves with them', body: 'Chapters unlock as your child grows. Nothing ahead is hidden: you can peek, you just cannot capture it yet.' },
                                { icon: 'checkmark-circle', title: 'Done is done, visibly', body: 'Completed milestones turn violet and stay on the path. The map becomes a picture of the years you have already kept.' },
                                { icon: 'create-outline', title: 'The map is yours', body: 'Not every milestone fits every family. Rename one, move it, take it off the map, or write your own in its place.' },
                            ]"
                            :key="item.title"
                            class="reveal flex gap-4"
                            :style="`--reveal-delay: ${140 + i * 90}ms`"
                        >
                            <span class="mt-0.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                <Ion :name="(item.icon as IconName)" :size="20" />
                            </span>
                            <div>
                                <p class="font-display text-lg font-bold text-ink">{{ item.title }}</p>
                                <p class="mt-1 leading-relaxed text-ink/55">{{ item.body }}</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- ─────────────────────────────────────────────────────── memories -->
        <section id="memories" class="px-5 py-28 lg:px-10">
            <div class="mx-auto grid max-w-7xl items-center gap-16 lg:grid-cols-2">
                <div>
                    <p class="reveal text-xs font-extrabold tracking-[0.2em] text-primary/70 uppercase">Memories</p>
                    <h2 class="reveal mt-3 font-display text-4xl font-extrabold tracking-tight text-ink sm:text-5xl" style="--reveal-delay: 60ms">
                        The details you swear you'll remember
                    </h2>
                    <p class="reveal mt-5 text-lg leading-relaxed text-ink/60" style="--reveal-delay: 120ms">
                        The word she made up for water. The way he fell asleep mid-sentence. LittleSteps asks for the small
                        things while they are still true (a photo, three lines, how it felt) and files them on a timeline
                        that reads like a book.
                    </p>

                    <div class="reveal mt-9 grid gap-4 sm:grid-cols-2" style="--reveal-delay: 180ms">
                        <div class="rounded-3xl bg-white p-6 shadow-sm shadow-primary/5">
                            <p class="font-display text-lg font-bold text-ink">Up to 3 photos</p>
                            <p class="mt-1.5 text-sm leading-relaxed text-ink/55">Per memory, in a swipeable carousel. Enough to tell it, few enough to choose.</p>
                        </div>
                        <div class="rounded-3xl bg-white p-6 shadow-sm shadow-primary/5">
                            <p class="font-display text-lg font-bold text-ink">A mood for each one</p>
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                <span
                                    v-for="mood in [
                                        { label: 'Joyful', icon: 'happy', color: '#E0A100' },
                                        { label: 'Proud', icon: 'ribbon', color: '#D45D75' },
                                        { label: 'Funny', icon: 'balloon', color: '#E05555' },
                                        { label: 'Tender', icon: 'heart', color: '#8A5CD1' },
                                    ]"
                                    :key="mood.label"
                                    class="flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold"
                                    :style="{ color: mood.color, backgroundColor: `${mood.color}1a` }"
                                >
                                    <Ion :name="(mood.icon as IconName)" :size="12" />
                                    {{ mood.label }}
                                </span>
                            </div>
                        </div>
                        <div class="rounded-3xl bg-primary p-6 text-white shadow-lg shadow-primary/25 sm:col-span-2">
                            <div class="flex items-start gap-4">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/20">
                                    <Ion name="hourglass" :size="20" />
                                </span>
                                <div>
                                    <p class="font-display text-lg font-bold">One free memory a day</p>
                                    <p class="mt-1.5 text-sm leading-relaxed text-white/70">
                                        Deliberately. A journal you can dump into is a journal you abandon by March, so
                                        LittleSteps asks for one and makes it count. Chapter milestones have their own allowance.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="reveal flex justify-center" style="--reveal-delay: 100ms">
                    <div class="relative">
                        <div class="absolute -inset-8 z-0 rounded-[3rem] bg-primary/10 blur-3xl" />
                        <div class="relative z-10">
                            <PhoneFrame>
                                <ScreenTimeline />
                            </PhoneFrame>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ──────────────────────────────────────────────────────── rewards -->
        <section id="rewards" class="relative overflow-hidden px-5 py-28 lg:px-10">
            <div class="pointer-events-none absolute inset-0 z-0">
                <div class="drift absolute top-10 -right-24 h-[26rem] w-[26rem] rounded-full bg-butter/50 blur-3xl" />
                <div class="drift absolute bottom-0 -left-24 h-[24rem] w-[24rem] rounded-full bg-primary/15 blur-3xl" style="--float-delay: -8s" />
            </div>

            <div class="relative z-10 mx-auto max-w-7xl">
                <div class="reveal mx-auto max-w-3xl text-center">
                    <p class="text-xs font-extrabold tracking-[0.2em] text-primary/70 uppercase">XP · levels · trophies · streaks</p>
                    <h2 class="mt-3 font-display text-4xl font-extrabold tracking-tight text-ink sm:text-5xl">
                        Because remembering deserves a reward
                    </h2>
                    <p class="mt-5 text-lg leading-relaxed text-ink/60">
                        Not points for their own sake, but a reason to open the app on the ordinary Tuesday that turns out to be
                        the day he says your name for the first time.
                    </p>
                </div>

                <div class="mt-16 grid items-center gap-16 lg:grid-cols-[1fr_auto]">
                    <div class="order-2 lg:order-1">
                        <!-- level ladder -->
                        <div class="reveal rounded-[2rem] bg-white p-7 shadow-sm shadow-primary/5">
                            <div class="flex items-center justify-between">
                                <p class="font-display text-xl font-extrabold text-ink">Climb the whole ladder</p>
                                <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary">Moment Collector</span>
                            </div>

                            <div class="mt-6 flex flex-wrap gap-2">
                                <span
                                    v-for="(level, i) in LEVELS"
                                    :key="level.name"
                                    class="group/level flex items-center gap-1.5 rounded-full px-3 py-2 text-xs font-bold transition-transform duration-200 hover:-translate-y-0.5"
                                    :class="i < 4 ? 'text-white' : 'bg-primary/8 text-primary/45'"
                                    :style="i < 4 ? { backgroundColor: `rgba(126, 94, 191, ${0.55 + i * 0.15})` } : undefined"
                                    :title="`${level.xp.toLocaleString()} XP`"
                                >
                                    <Ion :name="level.icon" :size="13" />
                                    {{ level.name }}
                                </span>
                            </div>

                            <div class="mt-6 h-2.5 overflow-hidden rounded-full bg-primary/10">
                                <div class="h-full w-[27%] rounded-full bg-linear-90 from-primary to-violet-soft" />
                            </div>
                            <p class="mt-2 text-sm text-ink/45">1,240 XP earned · 160 XP to Rising Star</p>
                        </div>

                        <!-- trophies -->
                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <div
                                v-for="(trophy, i) in TROPHIES"
                                :key="trophy.name"
                                class="reveal flex items-center gap-3 rounded-3xl bg-white p-4 shadow-sm shadow-primary/5 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-primary/10"
                                :style="`--reveal-delay: ${i * 60}ms`"
                            >
                                <span
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full"
                                    :class="i < 3 ? 'bg-primary text-white' : 'bg-primary/10 text-primary/50'"
                                >
                                    <Ion :name="trophy.icon" :size="19" />
                                </span>
                                <div class="min-w-0">
                                    <p class="font-display text-base leading-tight font-bold text-ink">{{ trophy.name }}</p>
                                    <p class="truncate text-xs text-ink/45">{{ trophy.description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="reveal order-1 flex justify-center lg:order-2">
                        <PhoneFrame>
                            <ScreenAwards />
                        </PhoneFrame>
                    </div>
                </div>
            </div>
        </section>

        <!-- ───────────────────────────────────────────────────── chapters -->
        <section id="chapters" class="px-5 py-28 lg:px-10">
            <div class="mx-auto max-w-7xl">
                <div class="reveal mx-auto max-w-3xl text-center">
                    <p class="text-xs font-extrabold tracking-[0.2em] text-primary/70 uppercase">The chapters</p>
                    <h2 class="mt-3 font-display text-4xl font-extrabold tracking-tight text-ink sm:text-5xl">
                        From the first hello to the first school bag
                    </h2>
                    <p class="mt-5 text-lg leading-relaxed text-ink/60">
                        Every milestone is written by parents who kept forgetting to write things down, and arranged so the right
                        question always arrives at the right age.
                    </p>
                </div>

                <div class="mt-16 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <article
                        v-for="(chapter, i) in CHAPTERS"
                        :key="chapter.name"
                        class="reveal group relative overflow-hidden rounded-[2rem] bg-white p-6 shadow-sm shadow-primary/5 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-2xl hover:shadow-primary/15"
                        :style="`--reveal-delay: ${(i % 4) * 90}ms`"
                    >
                        <div
                            class="absolute inset-x-0 top-0 h-1.5 transition-all duration-300 group-hover:h-2.5"
                            :style="{ backgroundColor: chapter.color }"
                        />
                        <div class="flex items-center justify-between">
                            <span
                                class="flex h-12 w-12 items-center justify-center rounded-2xl"
                                :style="{ backgroundColor: `${chapter.color}55` }"
                            >
                                <Ion :name="chapter.icon" :size="22" class="text-ink/70" />
                            </span>
                            <span class="font-display text-sm font-extrabold text-ink/30">{{ String(i + 1).padStart(2, '0') }}</span>
                        </div>

                        <p class="mt-5 text-xs font-extrabold tracking-widest text-ink/40 uppercase">{{ chapter.age }}</p>
                        <h3 class="mt-1 font-display text-xl leading-tight font-extrabold text-ink">{{ chapter.name }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink/55">{{ chapter.blurb }}</p>

                        <p class="mt-5 flex w-fit items-center gap-1.5 rounded-full bg-primary/8 px-3 py-1.5 text-xs font-bold text-primary">
                            <Ion name="footsteps" :size="13" />
                            The {{ chapter.stage }} stage
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <!-- ───────────────────────────────────────────────────── categories -->
        <section class="px-5 pb-28 lg:px-10">
            <div class="mx-auto max-w-7xl">
                <div class="reveal grid gap-10 rounded-[2.5rem] bg-white p-8 shadow-sm shadow-primary/5 sm:p-12 lg:grid-cols-[0.9fr_1.1fr]">
                    <div>
                        <p class="text-xs font-extrabold tracking-[0.2em] text-primary/70 uppercase">Every kind of memory</p>
                        <h2 class="mt-3 font-display text-4xl font-extrabold tracking-tight text-ink">
                            A whole childhood, not just the chapters
                        </h2>
                        <p class="mt-5 leading-relaxed text-ink/60">
                            First milestones matter. So does the song only they know the words to, the fear of the vacuum cleaner,
                            and the exact number on the scale at nine months. Every memory carries its category, so the
                            timeline can be read a hundred different ways.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div
                            v-for="(category, i) in CATEGORIES"
                            :key="category.name"
                            class="reveal flex items-start gap-3 rounded-2xl p-4 transition-transform duration-300 hover:-translate-y-0.5"
                            :style="`--reveal-delay: ${i * 50}ms; background-color: ${category.color}12`"
                        >
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white"
                                :style="{ backgroundColor: category.color }"
                            >
                                <Ion :name="category.icon" :size="18" />
                            </span>
                            <div>
                                <p class="font-display text-base font-bold" :style="{ color: category.color }">{{ category.name }}</p>
                                <p class="text-xs leading-snug text-ink/50">{{ category.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ───────────────────────────────────────────────────────── extras -->
        <section class="px-5 pb-28 lg:px-10">
            <div class="mx-auto max-w-7xl">
                <div class="reveal mx-auto max-w-2xl text-center">
                    <p class="text-xs font-extrabold tracking-[0.2em] text-primary/70 uppercase">The rest of it</p>
                    <h2 class="mt-3 font-display text-4xl font-extrabold tracking-tight text-ink sm:text-5xl">
                        Small things, thought about properly
                    </h2>
                </div>

                <div class="mt-14 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="(feature, i) in FEATURES"
                        :key="feature.title"
                        class="reveal rounded-[2rem] bg-white p-7 shadow-sm shadow-primary/5 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-primary/10"
                        :style="`--reveal-delay: ${(i % 3) * 90}ms`"
                    >
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-butter/60 text-[#8a6a00]">
                            <Ion :name="feature.icon" :size="22" />
                        </span>
                        <h3 class="mt-5 font-display text-xl font-extrabold text-ink">{{ feature.title }}</h3>
                        <p class="mt-2.5 leading-relaxed text-ink/55">{{ feature.body }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ────────────────────────────────────────────────────── keepsake -->
        <section class="px-5 pb-28 lg:px-10">
            <div class="reveal mx-auto grid max-w-7xl items-center gap-12 rounded-[2.5rem] bg-linear-135 from-primary via-violet-soft to-violet-deep p-8 text-white shadow-2xl shadow-primary/25 sm:p-14 lg:grid-cols-2">
                <div>
                    <h2 class="font-display text-4xl leading-tight font-extrabold sm:text-5xl">
                        In twenty years, this is the app they ask you to open
                    </h2>
                    <p class="mt-5 text-lg leading-relaxed text-white/70">
                        Not a folder of 14,000 photos. A story in your words, with the dates right. The one
                        thing you can hand over that cannot be re-downloaded.
                    </p>

                    <div class="mt-8 flex items-center gap-4">
                        <div class="flex -space-x-3">
                            <span class="rounded-full ring-4 ring-primary/60"><KidAvatar :size="44" variant="a" /></span>
                            <span class="rounded-full ring-4 ring-primary/60"><KidAvatar :size="44" variant="b" /></span>
                            <span class="rounded-full ring-4 ring-primary/60"><KidAvatar :size="44" variant="c" /></span>
                        </div>
                        <p class="text-sm text-white/70">Every child in the family gets their own map.</p>
                    </div>
                </div>

                <div class="relative">
                    <div class="rotate-2 rounded-[2rem] bg-white p-4 shadow-2xl shadow-black/25 transition-transform duration-500 hover:rotate-0">
                        <PhotoStub variant="meadow" class="h-44 w-full rounded-2xl" />
                        <div class="px-2 pt-4 pb-2">
                            <div class="flex items-center gap-2">
                                <span class="flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-[11px] font-extrabold text-primary">
                                    <Ion name="star" :size="10" />
                                    First Steps
                                </span>
                                <span class="text-[11px] font-semibold text-ink/40">14 March 2025</span>
                            </div>
                            <p class="mt-2 font-display text-2xl font-extrabold text-ink">Three milestones to the sofa</p>
                            <p class="mt-1 text-sm leading-relaxed text-ink/55">
                                Then she sat down hard and laughed like she had planned it that way.
                            </p>
                            <div class="mt-4 flex items-center justify-between border-t border-ink/5 pt-3">
                                <span class="flex items-center gap-2">
                                    <BrandMark :size="24" />
                                    <span class="font-display text-sm font-extrabold text-ink/60">LittleSteps</span>
                                </span>
                                <span class="flex items-center gap-1 text-[11px] font-bold text-ink/35">
                                    <Ion name="happy" :size="13" />
                                    Joyful
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ──────────────────────────────────────────────────────────── faq -->
        <section id="faq" class="px-5 pb-28 lg:px-10">
            <div class="mx-auto max-w-3xl">
                <div class="reveal text-center">
                    <p class="text-xs font-extrabold tracking-[0.2em] text-primary/70 uppercase">Questions</p>
                    <h2 class="mt-3 font-display text-4xl font-extrabold tracking-tight text-ink sm:text-5xl">
                        The things parents ask first
                    </h2>
                </div>

                <div class="reveal mt-12 space-y-3">
                    <FaqItem question="Do I have to fill in every milestone?">
                        Not even close. The map is a suggestion, not a checklist. Take off anything that does not fit your
                        family, rename a milestone to match what actually happened, or add your own. Nothing nags you about the
                        ones you leave empty.
                    </FaqItem>
                    <FaqItem question="My child is already three. Have I missed it?">
                        No. LittleSteps starts wherever you are and opens the chapter for their age, but every earlier
                        chapter stays open too. Plenty of parents fill in the first year from memory over a few quiet
                        evenings.
                    </FaqItem>
                    <FaqItem question="Can I track more than one child?">
                        Yes, and they never share a page. Each child gets their own map, their own memories, their own XP
                        and trophies, and switching between them takes a tap.
                    </FaqItem>
                    <FaqItem question="Why only one free memory a day?">
                        Because a journal you can dump into is a journal you abandon. One a day keeps it precious and
                        keeps the timeline readable. Milestones on the chapter map have their own daily allowance, so a big
                        day is never cut short.
                    </FaqItem>
                    <FaqItem question="Where do my photos and stories live?">
                        In your own private account, so a lost phone is not a lost childhood. Nothing is public, and
                        nothing is shared unless you tap share.
                    </FaqItem>
                    <FaqItem question="Does it cost anything?">
                        Starting costs nothing: add a child, follow the map, capture a memory a day. If a paid tier
                        arrives later it will be for the extras, never for the memories you have already kept.
                    </FaqItem>
                    <FaqItem question="When can I download it?">
                        LittleSteps is in its final stretch of testing. The buttons at the top of this page are the first
                        place the store links will appear.
                    </FaqItem>
                </div>
            </div>
        </section>

        <!-- ──────────────────────────────────────────────────────────── cta -->
        <section id="get" class="px-5 pb-24 lg:px-10">
            <div
                class="reveal relative mx-auto max-w-5xl overflow-hidden rounded-[2.5rem] bg-linear-135 from-primary via-violet-soft to-violet-deep px-8 py-16 text-center shadow-2xl shadow-primary/30 sm:px-16"
            >
                <div class="pointer-events-none absolute inset-0 z-0">
                    <div class="drift absolute -top-24 -left-16 h-72 w-72 rounded-full bg-white/20 blur-3xl" />
                    <div class="drift absolute -right-16 -bottom-24 h-72 w-72 rounded-full bg-butter/35 blur-3xl" style="--float-delay: -7s" />
                </div>

                <div class="relative">
                    <div class="float-slow mx-auto w-fit rounded-[1.9rem] bg-white/15 p-3 ring-1 ring-white/25 backdrop-blur-sm">
                        <BrandMark :size="72" />
                    </div>
                    <h2 class="mt-8 font-display text-4xl leading-tight font-extrabold text-white sm:text-5xl">
                        Start the adventure
                    </h2>
                    <p class="mx-auto mt-5 max-w-xl text-lg leading-relaxed text-white/60">
                        Add your little one, and the first chapter of their map opens tonight. The rest arrives one gentle
                        question at a time.
                    </p>

                    <div class="mt-10 flex justify-center">
                        <StoreButtons tone="light" />
                    </div>
                </div>
            </div>
        </section>

        <!-- ───────────────────────────────────────────────────────── footer -->
        <footer class="border-t border-primary/10 px-5 py-12 lg:px-10">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-6 sm:flex-row">
                <div class="flex items-center gap-3">
                    <BrandMark :size="36" />
                    <div>
                        <p class="font-display text-lg leading-none font-extrabold text-ink">
                            Little<span class="text-primary">Steps</span>
                        </p>
                        <p class="mt-1 text-xs text-ink/40">Every little step, kept forever.</p>
                    </div>
                </div>

                <p class="text-sm text-ink/40">© {{ new Date().getFullYear() }} LittleSteps. Made for the ones who keep growing.</p>
            </div>
        </footer>
    </div>
</template>
