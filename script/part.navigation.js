$(document).ready(function() {
    /*
     * Headbar : Upper (floating) element contains menu
     */
    // Headbar menu object, contains it's properties

    /* When scrolled, headbar menu transform into fixed mode */
    $(window).scroll(function(e) {
        // Threshold position to transform headbar menu
        if ($(this).scrollTop() < 500) {
            headbar.visible = false;
            $('nav#headbar').removeClass('fixed-mode');
            return;
        }

        $('nav#headbar').addClass('fixed-mode');

        // Check it is already visible? If not, make it visible
        if (headbar.visible == false) {
            headbar.visible = true;
            $('nav#headbar').css('opacity', 0).animate({opacity: 1}, 500);
        }
    });

    // Scroll to them

});

headbar = {
    // Active
    active: 'index',
    // Height of headbar
    height: 70,
    // Headbar is it visible?
    visible: false
};

function headbarAttachTo(elem) {
    $(elem).load('part.navigation.html #head-inner', null, headbarInit);
}

function headbarInit() {
    headbarActivateMenu('.' + headbarGetKeyLocation());

    // Event handler
    $('.menu-item').click(function () {
        headbarActivateMenu($(this));
        headbar.active = headbarGetKeyLocation();
    });

    $('.submenu-item').click(function () {
        headbarActivateSubMenu($(this));
    });

    anchor = $('.menu-item-active').attr('class')
                                   .replace('menu-item', '')
                                   .replace('menu-item-active', '')
                                   .replace(/\s/g, '');             // Remove space

    smoothScroll.animateScroll(null, '#' + anchor, {
        speed: 500,               // Integer. How fast to complete the scroll in milliseconds
        easing: 'easeInOutCubic', // Easing pattern to use
        updateURL: true,          // Boolean. Whether or not to update the URL with the anchor hash on scroll
        offset: 70,               // Integer. How far to offset the scrolling anchor location in pixels
    });
}

function headbarGetKeyLocation() {
    /* Auto detect based on location / URL */
    // Filter suffix
    keyLocation = document.location.toString().split('/');
    keyLocation = keyLocation[keyLocation.length - 1];

    // Filter html
    keyLocation = keyLocation.replace('.html', '');

    // Filter 'file#part' => 'part'
    if (keyLocation.search('#') != -1) {
        keyLocation = keyLocation.split('#')[1];
    }

    return keyLocation;
}

function headbarActivateMenu(item) {
    // Activate only one
    $(item).addClass('menu-item-active');

    // Deactivate all
    $("li[class*='menu-item']").not(item).removeClass('menu-item-active');
}

function headbarActivateSubMenu(item) {
    parent = $(item).parent('menu-item');

    // Activate only one
    $(item).addClass('submenu-item-active');
    parent.addClass('menu-item-active');

    // Deactivate all
    $("li[class*='menu-item']").not(item).not(parent).removeClass('submenu-item-active');
}
