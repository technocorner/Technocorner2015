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

    $('.galeri-photobox').photobox('a', {
        time:0,
        thumbs:false,
    });

    // Load navigator headbar and footer
    headbar.attachTo('nav#headbar');
    $('footer').load('part.footer.html #footer-inner');

    smoothScroll.init({
        speed: 500,               // Integer. How fast to complete the scroll in milliseconds
        easing: 'easeInOutCubic', // Easing pattern to use
        updateURL: true,          // Boolean. Whether or not to update the URL with the anchor hash on scroll
        offset: 70,               // Integer. How far to offset the scrolling anchor location in pixels
    });
});
