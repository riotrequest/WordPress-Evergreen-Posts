jQuery(document).ready(function($) {
    // Toggle evergreen-active class when the checkbox state changes.
    $('#evergreen-post').on('change', function() {
        $(this).closest('.misc-pub-section').toggleClass('evergreen-active', this.checked);
    });
});
