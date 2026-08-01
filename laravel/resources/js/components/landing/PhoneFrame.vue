<script setup lang="ts">
interface Props {
    label?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="relative">
        <div
            class="relative rounded-[2.75rem] bg-linear-160 from-[#3a3346] via-[#181322] to-[#3f3752] p-[3px] shadow-[0_50px_90px_-30px_rgba(46,28,84,0.55),0_10px_30px_-12px_rgba(46,28,84,0.4)]"
        >
            <div class="relative overflow-hidden rounded-[2.6rem] bg-black p-[7px]">
                <div class="relative h-[660px] w-[320px] overflow-hidden rounded-[2.15rem] bg-gray-50">
                    <!-- status bar -->
                    <div class="relative z-20 flex items-center justify-between px-6 pt-3 pb-1">
                        <span class="font-display text-[13px] font-bold text-ink">9:41</span>
                        <div class="flex items-center gap-1">
                            <svg width="17" height="11" viewBox="0 0 17 11" fill="none">
                                <rect x="0" y="7" width="3" height="4" rx="1" fill="#1B1725" />
                                <rect x="4.5" y="5" width="3" height="6" rx="1" fill="#1B1725" />
                                <rect x="9" y="2.5" width="3" height="8.5" rx="1" fill="#1B1725" />
                                <rect x="13.5" y="0" width="3" height="11" rx="1" fill="#1B1725" opacity="0.3" />
                            </svg>
                            <svg width="15" height="11" viewBox="0 0 15 11" fill="none">
                                <path
                                    d="M7.5 9.8 5.3 7.6a3.1 3.1 0 0 1 4.4 0L7.5 9.8ZM3.2 5.5a6.1 6.1 0 0 1 8.6 0l1.5-1.5a8.2 8.2 0 0 0-11.6 0l1.5 1.5Z"
                                    fill="#1B1725"
                                />
                            </svg>
                            <div class="flex h-[11px] w-[22px] items-center rounded-[3px] border border-ink/40 p-[1.5px]">
                                <div class="h-full w-[70%] rounded-[1px] bg-ink" />
                            </div>
                        </div>
                    </div>

                    <slot />

                    <!-- dynamic island -->
                    <div class="absolute top-2 left-1/2 z-30 h-[26px] w-[92px] -translate-x-1/2 rounded-full bg-black" />
                </div>
            </div>

            <!-- glass glare -->
            <div
                class="pointer-events-none absolute inset-0 rounded-[2.75rem]"
                style="
                    background: linear-gradient(
                        115deg,
                        rgba(255, 255, 255, 0.28) 0%,
                        rgba(255, 255, 255, 0) 38%,
                        rgba(255, 255, 255, 0) 62%,
                        rgba(255, 255, 255, 0.12) 100%
                    );
                "
            />
        </div>

        <p v-if="label" class="mt-5 text-center text-xs font-semibold tracking-widest text-primary/70 uppercase">
            {{ label }}
        </p>
    </div>
</template>
