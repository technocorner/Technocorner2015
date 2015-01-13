$(document).ready(function() {
    /* Load fullpage-js, for more info see fullpage-js doc */
    $('#fullpage').fullpage({
        autoScrolling: false,
        navigation: false,
        navigationPosition: 'right',
        slidesNavigation: false,
        slidesNavPosition: 'bottom',
        easingcss3: 'ease-in-out',
        easing: 'easeInQuart',

        fixedElements: 'header',
        // anchors: [landing, video, news],

        responsive: 768,
        resize: false,

        afterLoad: function (anchorLink, index) {},
        onLeave: function (index, nextIndex, direction) {},
        afterResize: function () {}
    });

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
    $(window).scroll(function(e){
        // Threshold position to transform headbar menu
        if ($(this).scrollTop() < 500) {
            headbar.visible = false;
            $('header').removeClass('fixed-mode');
            return;
        }

        $('header').addClass('fixed-mode');

        // Check it is already visible? If not, make it visible
        if (headbar.visible == false) {
            headbar.visible = true;
            $('header').css('opacity', 0).animate({opacity: 1}, 500);
        }
    });
});
