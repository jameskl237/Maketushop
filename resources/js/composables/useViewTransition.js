import { router } from '@inertiajs/vue3';

// Integrate View Transitions API with Inertia page navigations.
// The trick: on 'start' we kick off startViewTransition, which takes
// a screenshot. On 'finish' (DOM swapped) we let the animation play.
export function initViewTransitions() {
    if (typeof document === 'undefined') return;
    if (!document.startViewTransition) return;

    let finishTransition = null;

    router.on('start', () => {
        const vt = document.startViewTransition(() => {
            // This promise resolves when Inertia finishes swapping the DOM
            return new Promise((resolve) => {
                finishTransition = resolve;
            });
        });

        vt.finished.catch(() => {});
    });

    router.on('finish', () => {
        finishTransition?.();
        finishTransition = null;
    });
}
