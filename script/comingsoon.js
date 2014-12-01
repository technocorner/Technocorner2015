
// Ready action
var autoswitchSlide = false;
$(document).ready(function() {
    $('#fullpage').fullpage({
        navigation: true,
        navigationPosition: 'right',
        /* navigationTooltips: ['firstSlide', 'secondSlide'] */
        slidesNavigation: true,
        slidesNavPosition: 'bottom',
        loopHorizontal: true,

        anchors: ['a-landing', 'a-excerpt', 'a-closing'],

        afterSlideLoad:  function (anchorLink, index, slideAnchor, slideIndex) {
            console.log(anchorLink);
            if (anchorLink == 'a-excerpt' && autoswitchSlide == false) {
                autoswitchSlide = true;
                setInterval(function () {
                    $.fn.fullpage.moveSlideRight();
                }, 10000);
            }
        }
    });

    // Hide slide nav arrows
    /* $('.fp-controlArrow').hide(); */
});
