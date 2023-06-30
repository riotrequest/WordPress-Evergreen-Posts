jQuery(document).ready(function($) {
    $('#evergreen-button').click(function() {
        $(this).toggleClass('evergreen-active');
        var buttonText = $(this).hasClass('evergreen-active') ? 'Evergreen' : 'Evergreen';
        $(this).text(buttonText);
    });
});
