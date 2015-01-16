$(document).ready(function() {
    // Load navigator headbar and footer
    $('nav#headbar').load('part.navigation.html #head-inner');
    $('footer').load('part.footer.html #footer-inner');

    /*
     * Technical README:
     * Each page contains many section e.g. Description, Rules, Download, etc.
     * Each section has menu-item (on the leftside of page, has class "lomba-menu-item section") and
     * it's corresponding article <div> element (on the right of page, contains text, has class "article section").
     * See the HTML for implementation.
     */

    /*
     * Once one of menu-item is clicked, it does the following
     */
    $('.lomba-menu-item').click(function(){
        // Detect what section class it is? e.g. download?
        sectionClass = '.'                           // Add dot as class notation
                     + $(this).attr('class')         // Get element class list e.g. "lomba-menu-item download"
                     .replace('lomba-menu-item', '') // Remove .lomba-menu-item class from list e.g. " download"
                     .replace(' ', '');              // Remove spaces remaining e.g. "download"

        // Hide all article sections
        $('.article').hide(500);

        // Remove menuitem activate class
        $('.lomba-menu-item').removeClass('lomba-menu-item-click');

        // Show a section, the one we want
        $('.article' + sectionClass).show(500);

        // Activate clicked menu-item
        $(this).addClass('lomba-menu-item-click');
    });
    $('.article').hide();
    $('.lomba-menu-item.eventdetail').click();
});
