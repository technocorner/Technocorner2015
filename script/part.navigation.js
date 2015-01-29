/*
 * Headbar : Upper (floating) element contains menu
 */

$(document).ready(function() {
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

    $(window).scroll(function(e) {
        $('.nav-anchor').each(function () {
            var top = window.pageYOffset;
            var distance = top - $(this).offset().top;
            var anchor = null;

            if (distance < 70 && distance > -80) {
                if ($(this).attr('anchor')) {
                    anchor = $(this).attr('anchor');
                    console.log(anchor);
                }

                if (anchor) {
                    headbar.activateMenu('.' + $(this).attr('anchor'));
                }
            }
        });
    });

    $(window).bind('hashchange', headbar.onHashtagChanged);

    $('.menu-item').click(function () {
        headbar.activateMenu($(this));
    });

    $('.submenu-item').click(function () {
        headbar.activateSubMenu($(this));
    });
});

/*
 * An object responsible for headbar operation
 */
var headbar = {
    // Field or member variable
    active: 'landing', // Active menu
    height: 70,      // Height of headbar
    visible: false,  // Headbar is it visible?
    scrollOpt: {
        speed: 500,               // Integer. How fast to complete the scroll in milliseconds
        easing: 'easeInOutCubic', // Easing pattern to use
        updateURL: true,          // Boolean. Whether or not to update the URL with the anchor hash on scroll
        offset: 70,               // Integer. How far to offset the scrolling anchor location in pixels
    },
    hashMap: {
        front: 'landing',
        info: 'news',
        subevent: 'excerpt'
    },

    // Methods or Functions
    attachTo: function (elem) {
        $(elem).load('part.navigation.html #head-inner', null, headbar.init);
    },

    /*
     * External constructor
     */
    init: function () {
        headbar._init();
    },

    /*
     * Internal constructor
     */
    _init: function() {
        // Init properties
        this.scrollOpt.offset = this.height;

        // Detect crumpy url with hashtag
        hash = window.location.hash.replace('#', '');
        anchor = hash;

        // Is it a section
        if ($('#' + anchor).hasClass('section')) {
            // Yes, activate this
            this.activateMenuOrSub('.' + anchor);
        } else {
            // No, use filename
            filename = this.getUrlFileName(window.location.pathname);
            this.activateMenuOrSub('.' + filename);
        }

        // Scroll to it! Let set sail
        smoothScroll.animateScroll(null, '#' + this.active, headbar.scrollOpt);
    },

    activateMenuOrSub: function (item) {
        if ($(item).hasClass('submenu-item')) {
            this.activateSubMenu(item);
        } else if ($(item).hasClass('menu-item')) {
            this.activateMenu(item);
        }
    },

    activateMenu: function (item) {
        // Activate only one
        $(item).filter('.menu-item').addClass('menu-item-active');

        // Save the new active menu
        this.active = this.getMenuClass(item)? this.getMenuClass(item) : this.active;

        // Deactivate all
        $("li[class*='menu-item']").not(item).removeClass('menu-item-active');
    },

    activateSubMenu: function (item) {
        parent = $(item).filter('.submenu-item').parents('.menu-item');

        // Activate only one
        $(item).filter('.submenu-item').addClass('submenu-item-active');
        parent.addClass('menu-item-active');

        console.log("Parent %o : Child %o", parent, $(item));
        // Deactivate all
        $("li[class*='menu-item']").not(item).not(parent).removeClass('submenu-item-active');
    },

    getMenuClass: function (item) {
        if ($(item).length == 0) {
            return '';
        }

        return $(item).attr('class').replace('menu-item', '')
                                    .replace('menu-item-active', '')
                                    .replace(/\s/g, '');
    },

    getUrlFileName: function (path) {
        var name = path.split("/").pop()
                       .split('#')[0]
                       .split('.');
        name.splice(-1, 1);
        return name;
    },

    onHashtagChanged: function (e) {
        e.preventDefault();

        // Get hash
        hash = window.location.hash.replace('#', '');

        // Don't response on wrong type
        if ($('#' + hash).length == 0) {
            return;
        }

        // Check is it a section
        if ($('#' + hash).hasClass('section')) {
            console.log('success ' + hash);
            smoothScroll.animateScroll(null, '#' + hash, headbar.scrollOpt);
            return true;
        }
    }
};
