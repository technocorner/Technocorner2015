
// Ready action
var autoswitchSlide = false;
var sliderTimer;
var sliderTimerCount = 0;
var sliderTimerPaused = false;

var pauseElement = '[class^=pure-u-]';

function sliderTimerStart() {
    if (sliderTimerPaused) {
        return;
    }

    // sliderTimerCount++;
    NProgress.set(sliderTimerCount / 100);

    if (sliderTimerCount > 99) {
        console.log(NProgress.status);
        NProgress.set(1.0)
        NProgress.remove();
        $.fn.fullpage.moveSlideRight();
        console.log("switch slide");

        // Restart
        NProgress.set(0.0);
        sliderTimerCount = 0;
        console.log(NProgress.status);
    }
    sliderTimerCount++;
    console.log(sliderTimerCount);
}

function sliderTimerPause() {
    sliderTimerPaused = true;
}

function sliderTimerResume() {
    sliderTimerPaused = false;
}

$(document).ready(function() {
    NProgress.configure({
        showSpinner: false
    });
    $(pauseElement).mouseenter(function () {
        sliderTimerPause();
    });
    $(pauseElement).mouseleave(function () {
        console.log('leave');
        sliderTimerResume();
    });

    $('#fullpage').fullpage({
        navigation: true,
        navigationPosition: 'right',
        slidesNavigation: true,
        slidesNavPosition: 'bottom',

        easingcss3: 'ease-in-out',
        easing: 'easeInQuart',

        anchors: ['a-landing', 'a-excerpt', 'a-closing'],

        responsive: 768,

        afterLoad: function (anchorLink, index) {
            console.log(anchorLink);
            if (anchorLink == 'a-excerpt') {
                /* sliderTimerResume(); */
                NProgress.set(0.0);
                console.log("setInterval");
                if (!sliderTimer) {
                    sliderTimer = setInterval(sliderTimerStart, 100);
                }
            }
        },
        onLeave: function (index, nextIndex, direction) {
            if (index == 2) {
                console.log("Leave");
                clearInterval(sliderTimer);
                sliderTimer = null;
                NProgress.set(1.0)
                NProgress.remove();
            }
        }
    });
});
