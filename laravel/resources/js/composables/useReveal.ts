import { onBeforeUnmount, onMounted } from 'vue';

/**
 * Adds `.is-in` to every `.reveal` element once it scrolls into view, so the
 * landing page animates without pulling in an animation library.
 */
export function useReveal(root?: () => HTMLElement | null) {
    let observer: IntersectionObserver | null = null;

    onMounted(() => {
        const scope = root?.() ?? document;
        const targets = Array.from(scope.querySelectorAll<HTMLElement>('.reveal'));

        if (!('IntersectionObserver' in window)) {
            targets.forEach((el) => el.classList.add('is-in'));
            return;
        }

        observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    entry.target.classList.add('is-in');
                    observer?.unobserve(entry.target);
                });
            },
            { rootMargin: '0px 0px -10% 0px', threshold: 0.08 },
        );

        targets.forEach((el) => observer?.observe(el));
    });

    onBeforeUnmount(() => observer?.disconnect());
}
