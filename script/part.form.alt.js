/*
 * Toggle input element between disable and enable
 * @param element: jQuery selector
 * @param status: "on" and "off" value
 */
function toggleElement(element, status) {
    if (status == 'on') {
        $(element).removeAttr("disabled");
    } else {
        $(element).attr("disabled", "");
    }
}

var captcha;

$(document).ready(function () {
    alertify.set('notifier','delay', 10);
    alertify.set('notifier','position', 'bottom-right');

    captcha = new SliderCaptcha('#captcha', {
        authValue: "Anda bukan robot :)",
        hintText: "Geser ke kanan"
    });

    /*
     * Set submit form callback
     */
    $('form').submit(function(event){
        // Captcha response is either blank or true
        // If captcha blank (wrong)
        if ($(this).attr('data-valid') !== 'true') {
            alertify.error('Mohon geser slider terlebih dahulu');
            return;
        }
    });
});
