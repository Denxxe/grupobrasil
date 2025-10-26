// Enforce client-side maxlength strictly: block typing beyond maxlength and trim pasted content.
document.addEventListener('DOMContentLoaded', function () {
    function isControlKey(e) {
        // Allow backspace, delete, arrows, home/end, tab
        const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Home','End','Tab'];
        if (allowed.includes(e.key)) return true;
        // Allow Ctrl/Meta combos (copy/paste/select all)
        if (e.ctrlKey || e.metaKey) return true;
        return false;
    }

    function enforceOnElement(el) {
        const max = parseInt(el.getAttribute('maxlength'));
        if (!max || isNaN(max)) return;

        // Prevent typing when at max length
        el.addEventListener('keydown', function (e) {
            if (isControlKey(e)) return;
            const selectionLength = (el.selectionEnd || 0) - (el.selectionStart || 0);
            if (el.value.length - selectionLength >= max) {
                e.preventDefault();
            }
        });

        // Trim on input (covers IME and some mobile behaviors)
        el.addEventListener('input', function (e) {
            if (el.value.length > max) {
                const cursor = el.selectionStart;
                el.value = el.value.slice(0, max);
                try { el.setSelectionRange(cursor > max ? max : cursor, el.selectionEnd); } catch (err) {}
            }
        });

        // Handle paste: trim pasted content so length won't exceed max
        el.addEventListener('paste', function (e) {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text');
            const start = el.selectionStart || 0;
            const end = el.selectionEnd || 0;
            const prefix = el.value.slice(0, start);
            const suffix = el.value.slice(end);
            const allowed = max - (prefix.length + suffix.length);
            const toInsert = paste.slice(0, Math.max(0, allowed));
            el.value = prefix + toInsert + suffix;
            const newPos = prefix.length + toInsert.length;
            try { el.setSelectionRange(newPos, newPos); } catch (err) {}
            // Trigger input event
            el.dispatchEvent(new Event('input', { bubbles: true }));
        });
    }

    // Apply to all inputs and textareas that have maxlength attribute
    const selectors = 'input[maxlength], textarea[maxlength]';
    document.querySelectorAll(selectors).forEach(enforceOnElement);

    // If new inputs are added dynamically, observe the DOM and apply
    const observer = new MutationObserver(function (mutationsList) {
        for (const m of mutationsList) {
            if (m.type === 'childList' && m.addedNodes.length) {
                m.addedNodes.forEach(node => {
                    if (!(node instanceof HTMLElement)) return;
                    node.querySelectorAll && node.querySelectorAll(selectors).forEach(enforceOnElement);
                    if (node.matches && node.matches(selectors)) enforceOnElement(node);
                });
            }
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });
});
