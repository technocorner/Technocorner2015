
// Ready action
var autoswitchSlide = false;
var sliderTimer;
var sliderTimerCount = 0;
var sliderTimerPaused = false;

var pauseElement = '[class^=pure-u-]';

var wallpaperPath = 'img/photo/'
var wallpaperList = ['lf14-1.jpg'];
var wallpaperIdx = 0;

function changeBackground(element, path) {
    $(element).css({
        backgroundImage: 'url(' + path + ')'
    });
}

function nextSliderBackground() {
    wallpaperIdx++;
    wallpaperIdx = wallpaperIdx % wallpaperList.length;
    changeBackground('#excerpt', wallpaperPath + wallpaperList[wallpaperIdx]);
}

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
        /* console.log("switch slide"); */

        // nextSliderBackground();

        // Restart
        NProgress.set(0.0);
        sliderTimerCount = 0;
        console.log(NProgress.status);
    }
    sliderTimerCount++;
    /* console.log(sliderTimerCount); */
}

function sliderTimerPause() {
    sliderTimerPaused = true;
}

function sliderTimerResume() {
    sliderTimerPaused = false;
}

/*
 * @brief Function called on Document Ready to setup elements
 *        using jQuery or other function.
 */
function DomSetup() {
    
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
        resize:false,

        afterLoad: function (anchorLink, index) {
            /* console.log(anchorLink); */
            if (anchorLink == 'a-excerpt') {
                /* sliderTimerResume(); */
                NProgress.set(0.0);
                /* console.log("setInterval"); */
                if (!sliderTimer) {
                    sliderTimer = setInterval(sliderTimerStart, 100);
                }
            }
        },
        onLeave: function (index, nextIndex, direction) {
            if (index == 2) {
                /* console.log("Leave"); */
                clearInterval(sliderTimer);
                sliderTimer = null;
                NProgress.set(1.0)
                NProgress.remove();
            }
        },
        afterResize: function () {
            console.log($('html').width());
            if ($('html').width() < 640) {
                // Change 3-column into one
                $('.pure-u-1-3')
                         .addClass('pure-u-1-1').addClass('mobile')
                         .removeClass('pure-u-1-3');
            } else {
                // Restore column
                $('.pure-u-1-1.mobile')
                         .addClass('pure-u-1-3')
                         .removeClass('pure-u-1-1').removeClass('mobile');
            }
        }
    });

    DomSetup();
});
