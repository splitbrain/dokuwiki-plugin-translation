/**
 * Remove go button from translation dropdown
 */
jQuery(function(){
    var $frm = jQuery('#translation__dropdown');
    if(!$frm.length) return;
    $frm.find('input[name=go]').hide();
    $frm.find('select[name=id]').change(function(){
        location.href = this.value;
    });
});
