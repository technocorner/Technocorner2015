$(document).ready(function() {
    /*
     * Headbar : Upper (floating) element contains menu
     */
    // Headbar menu object, contains it's properties
    headbar = {
        // Height of headbar
        height: 70,
        // Headbar is it visible?
        visible: false
    };

    /* When scrolled, headbar menu transform into fixed mode */
    $(window).scroll(function(e) {
        // Threshold position to transform headbar menu
        if ($(this).scrollTop() < 500) {
            headbar.visible = false;
            $('nav').removeClass('fixed-mode');
            return;
        }

        $('nav').addClass('fixed-mode');

        // Check it is already visible? If not, make it visible
        if (headbar.visible == false) {
            headbar.visible = true;
            $('nav').css('opacity', 0).animate({opacity: 1}, 500);
        }
    });
});
