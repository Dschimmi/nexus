/**
 * Admin Panel Logic
 * Wird nur geladen, wenn Admin-Funktionen benötigt werden.
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Select All Checkbox Logic (Pages List)
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.page-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    }

    // 2. Delete Confirmation Logic
    const deleteBtn = document.getElementById('btn-delete-confirm');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function(e) {
            const text = this.getAttribute('data-confirm-text') || 'Are you sure?';
            if (!confirm(text)) {
                e.preventDefault();
            }
        });
    }
});