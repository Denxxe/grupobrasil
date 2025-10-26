(function(){
    // Initializes comment counters and enforces maxlength on textareas named 'contenido'
    function initCommentCounters(maxChars = 800) {
        const MAX = maxChars;

        function setColor(countSpan, remaining) {
            if (!countSpan) return;
            // Color thresholds: >100 green, 25-100 yellow, <=25 red
            if (remaining > 100) {
                countSpan.style.color = '#16a34a'; // green-600
            } else if (remaining > 25) {
                countSpan.style.color = '#f59e0b'; // yellow-500
            } else {
                countSpan.style.color = '#ef4444'; // red-500
            }
        }

        function updateCounter(textarea) {
            const countSpan = textarea.parentElement.querySelector('.char-count');
            if (countSpan) {
                const len = textarea.value.length;
                countSpan.textContent = len;
                setColor(countSpan, MAX - len);
            }
        }

        document.querySelectorAll('textarea[name="contenido"]').forEach(txt => {
            // Ensure maxlength attribute exists
            txt.setAttribute('maxlength', MAX);

            // Initialize counter value
            updateCounter(txt);

            // Input guard
            txt.addEventListener('input', function (e) {
                if (this.value.length > MAX) {
                    this.value = this.value.substring(0, MAX);
                }
                updateCounter(this);
            });

            // Paste handler: trim pasted content if it would exceed MAX
            txt.addEventListener('paste', function (e) {
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                const allowed = MAX - this.value.length;
                if (paste.length > allowed) {
                    e.preventDefault();
                    const part = paste.substring(0, allowed);
                    const start = this.selectionStart || 0;
                    const end = this.selectionEnd || 0;
                    this.value = this.value.substring(0, start) + part + this.value.substring(end);
                    const pos = start + part.length;
                    this.setSelectionRange(pos, pos);
                    updateCounter(this);
                }
            });

            // Keydown: prevent further input when at limit for non-control keys
            txt.addEventListener('keydown', function(e) {
                const len = this.value.length;
                const allowedKeys = ['Backspace','ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Delete','Tab'];
                if (len >= MAX && !allowedKeys.includes(e.key) && !e.ctrlKey && !e.metaKey) {
                    e.preventDefault();
                }
            });
        });
    }

    // Expose globally so other pages can initialize if needed
    window.initCommentCounters = initCommentCounters;

    // Auto-initialize on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function() {
        try { initCommentCounters(800); } catch (err) { console.error('initCommentCounters error', err); }
    });

})();
