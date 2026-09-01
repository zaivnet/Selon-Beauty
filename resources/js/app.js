import { AttendanceFaceDetector, hasAcceptableFace, evaluateFaceAcceptance } from './attendance-face-detector';

window.AttendanceFaceDetector = AttendanceFaceDetector;
window.hasAcceptableAttendanceFace = hasAcceptableFace;
window.evaluateAttendanceFaceAcceptance = evaluateFaceAcceptance;

/* ==========================================================================
   UI Performance & Transitions (Sprint 07)
   ========================================================================== */

document.addEventListener('DOMContentLoaded', () => {
    const pageLoader = document.getElementById('app-page-loader');

    // 1. Global Page Transitions (Internal links only)
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a[href]');
        if (!link || !pageLoader) return;

        const href = link.getAttribute('href');
        const target = link.getAttribute('target');
        
        // Ignore external, hash, new tab, download, javascript, tel, mailto
        if (
            !href ||
            href.startsWith('#') ||
            href.startsWith('javascript:') ||
            href.startsWith('tel:') ||
            href.startsWith('mailto:') ||
            href.hasAttribute?.('download') ||
            target === '_blank' ||
            e.ctrlKey || e.metaKey || e.shiftKey || e.altKey
        ) {
            return;
        }

        // Check if it's an internal navigation (same origin)
        try {
            const url = new URL(href, window.location.origin);
            if (url.origin === window.location.origin && url.pathname !== window.location.pathname) {
                // 1. Prevent immediate native navigation to allow paint
                e.preventDefault();

                // 2. Show loader immediately
                pageLoader.classList.add('active');

                // 3. Guarantee paint opportunity before navigation blocks the thread
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        window.location.href = href;
                    });
                });
            }
        } catch (err) {
            // Ignore invalid URLs
        }
    });

    // 2. Global Form Button Loading State
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!form) return;

        // Find the submit button(s)
        const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
        submitButtons.forEach(btn => {
            // Don't modify if it already has a specific loading UI
            if (btn.hasAttribute('data-no-loading')) return;

            // Set loading state
            btn.setAttribute('disabled', 'true');
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            
            // Only change text if it has data-loading-submit or doesn't have an icon-only setup
            if (btn.tagName.toLowerCase() === 'button') {
                const loadingText = btn.getAttribute('data-loading-submit') || 'Memproses...';
                // Only replace text if it seems like a primary textual button
                if (btn.textContent.trim().length > 0 && !btn.querySelector('svg:not(.w-4):not(.w-5)')) {
                    btn.innerHTML = `<svg class="animate-spin -ml-1 mr-2 h-4 w-4 inline text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>${loadingText}`;
                }
            }
        });
    });

    // 3. Fallback: Ensure loader doesn't get stuck (bfcache or long loads)
    window.addEventListener('pageshow', (e) => {
        if (pageLoader && pageLoader.classList.contains('active')) {
            pageLoader.classList.remove('active');
        }
        
        // Also re-enable any disabled form buttons from bfcache
        document.querySelectorAll('form button[disabled]').forEach(btn => {
            if (btn.innerHTML.includes('animate-spin')) {
                // If we changed innerHTML, we can't easily revert without storing the old HTML. 
                // But generally reloading from bfcache means the user came back. Let's just remove disabled.
                btn.removeAttribute('disabled');
                btn.classList.remove('opacity-75', 'cursor-not-allowed');
            }
        });
    });
});
