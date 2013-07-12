head
.js(connect+'/js/connect.js')
.js('resources/js/jquery.min.js')
.ready(function(){
    head
    .js('resources/js/bootstrap.min.js')
    .js('resources/js/onde.js')
    .js('resources/js/jquery.ui.min.js')
    .ready(function(){
        Connect({
            onlogin: function(user) {
                if(!logged) {
                    $.post(base+'login',JSON.stringify(user),function(){
                        location.reload();
                    });
                }
            },
            onlogout: function(nothing){
                if(logged) {
                    $.post(base+'logout',nothing,function(){
                        location.reload();
                    });
                }
            }
        });
        $("#login a").click(function(){ Connect.login(); });
        $("#logout a").click(function(){ Connect.logout(); });
        $('form button[class*="btn-danger"]').each(function(i,e){
            $(e).parent().parent().submit(function(){
                return confirm("Confirma excluir esse recurso?");
            });
        });
        $("select.families").change(function(evt){
            var el = $(evt.target),family = el.val(), status = el.parent().attr("id") ;
            if(family != "---") {
                $("#"+status+" ul").html('').append('<li>loading...</li>');
                $.getJSON(base+'work/'+family+'/'+status,function(r) {
                    var link = (status=='done')?'view':(status=='open')?'edit':(status=='validation')?'validate':'review';
                    $("#"+status+" ul").html('');
                    if(r.length >= 1) {
                        for(var i  in r) {
                            if(status == 'empty') {
                                $("#"+status+" ul").append('<li><i class="icon-leaf"></i>'
                                                           +'<a href="'+base+'specie/'+r[i]._id+'/">'
                                                           +r[i].scientificName+'</a></li>');
                            } else {
                                $("#"+status+" ul").append('<li><i class="icon-leaf"></i>'
                                                           +'<a href="'+base+'profile/'+r[i]._id+'/'+link+'">'
                                                           +r[i].taxon.scientificName+'</a></li>');
                            }
                        }
                    } else {
                        $("#"+status+" ul").append('<li>N/A</li>');
                    }
                });
            }
        });
        if($("html").attr("id") == "edit-page" || $("html").attr("id") == "review-page") {
            var form = new onde.Onde($("#data"));
            form.render(schema,data,{collapsedCollapsibles: true});
            $("#data").submit(function(e){
                e.preventDefault();
                var data = form.getData().data;
                $.post($("#data").attr("action"),JSON.stringify(data),function(){
                    location.href=$("#data").attr("action");
                });
                return false;
            });
            var got = {};
            $(".field-add.item-add").click(function(){
                setTimeout(function(){
                    $("input[type=text]").each(function(i,e){
                        var input = $(e);
                        if(!got[input.attr("name")] && (input.attr("name").match(/references\[[0-9]+\].citation$/)?true:false)) {
                            got[input.attr("name")] = true;
                            input.autocomplete({ source:base+"biblio" });
                            input.on('autocompleteselect',function(evt,ui){
                                var input = $(evt.target);
                                input.val(ui.item.label);
                                $("#"+input.attr("id").replace("citation","ref")).val(ui.item.value);
                                return false;
                            });
                        }
                    });
                },1000);
            });
            $("input[type=text]").each(function(i,e){
                var input = $(e);
                if((input.attr("name").match(/references\[[0-9]+\].citation$/)?true:false)) {
                    got[input.attr("name")] = true;
                    input.on('autocompleteselect',function(evt,ui){
                        var input = $(evt.target);
                        input.val(ui.item.label);
                        $("#"+input.attr("id").replace("citation","ref")).val(ui.item.value);
                        return false;
                    });
                }
            });
        }
        if($("html").attr("id") == "validate-page") {
            for(var i in schema.properties) {
                $("#field").append("<option>"+ schema.properties[i].label +"</option>");
            }
        }
    });
});
