/**
 * Remove go button from translation dropdown
 */
addInitEvent(function(){
    var frm = $('translation__dropdown');
    if(!frm) return;
    frm.elements['go'].style.display = 'none';
    addEvent(frm.elements['id'],'change',function(e){
        var id = e.target.options[e.target.selectedIndex].value;
        // this should hopefully detect rewriting good enough:
        if(frm.action.substr(frm.action.length-1) == '/'){
            var link = frm.action + id;
        }else{
            var link = frm.action + '?id=' + id;
        }

        window.location.href= link;
    });
});
