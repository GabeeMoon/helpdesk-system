$(document).ready(function() {
    // --- Dark Mode Toggle ---
    const darkModeToggle = $('#darkModeToggle');
    const body = $('body');
    
    // Check local storage
    if (localStorage.getItem('darkMode') === 'enabled') {
        body.addClass('dark-mode');
        if(darkModeToggle.length) darkModeToggle.prop('checked', true);
    }

    if(darkModeToggle.length) {
        darkModeToggle.on('change', function() {
            if ($(this).is(':checked')) {
                body.addClass('dark-mode');
                localStorage.setItem('darkMode', 'enabled');
            } else {
                body.removeClass('dark-mode');
                localStorage.setItem('darkMode', 'disabled');
            }
        });
    }

    // --- Sidebar Toggle ---
    $('#menu-toggle').click(function(e) {
        e.preventDefault();
        $('#wrapper').toggleClass('toggled');
    });

    // Close sidebar when clicking outside on mobile
    $(document).click(function(e) {
        if ($(window).width() < 768) {
            if (!$(e.target).closest('#sidebar-wrapper, #menu-toggle').length && $('#wrapper').hasClass('toggled')) {
                $('#wrapper').removeClass('toggled');
            }
        }
    });

    // --- Tooltips ---
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // --- Skeleton Loading Helper ---
    window.showSkeleton = function(selector, rows = 3) {
        let html = '';
        for(let i=0; i<rows; i++) {
            html += `
                <div class="skeleton-row mb-3 p-3 rounded bg-light">
                    <div class="d-flex justify-content-between">
                        <div class="skeleton-text w-50 bg-secondary opacity-25 rounded" style="height: 20px;"></div>
                        <div class="skeleton-text w-25 bg-secondary opacity-25 rounded" style="height: 20px;"></div>
                    </div>
                </div>`;
        }
        $(selector).html(html);
    };
});
