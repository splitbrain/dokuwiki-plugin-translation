/**
 * Remove go button from translation dropdown
 */
jQuery(function(){
    var $frm = jQuery('#translation__dropdown');
    if(!$frm.length) return;
    $frm.find('input[name=go]').hide();
    $frm.find('select[name=id]').change(function(){
        var id = jQuery(this).val();
        // this should hopefully detect rewriting good enough:
        var action = $frm.attr('action');
            var link = action + '?id=' + id;
        if(action.substr(action.length-1) == '/'){
            link = action + id;
        }

        window.location.href=link;
    });
});
