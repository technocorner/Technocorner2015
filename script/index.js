$(document).ready(function() {
    $('#fullpage').fullpage({
        autoScrolling: false,
        navigation: false,
        navigationPosition: 'right',
        slidesNavigation: false,
        slidesNavPosition: 'bottom',
        easingcss3: 'ease-in-out',
        easing: 'easeInQuart',
        anchors: [],

        responsive: 768,
        resize:false,

        afterLoad: function (anchorLink, index) {},
        onLeave: function (index, nextIndex, direction) {},
        afterResize: function () {}
    });
});
