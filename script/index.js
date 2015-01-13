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
    $('nav#headbar').load('part.navigation.html #head-inner');
    $('footer').load('part.footer.html #footer-inner');
});
