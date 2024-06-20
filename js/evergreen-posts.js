jQuery(document).ready(function($) {
    $('#evergreen-post').change(function() {
        $(this).closest('.misc-pub-section').toggleClass('evergreen-active', this.checked);
    });
});
