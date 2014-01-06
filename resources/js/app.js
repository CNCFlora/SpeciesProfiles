head
.js(connect+'/js/connect.js')
.js('resources/js/jquery.min.js')
.ready(function(){
    head
    .js('resources/js/bootstrap.min.js')
    .js('resources/js/onde.js')
    .js('resources/js/jquery.ui.min.js')
    .ready(function(){
        var base = window.base;
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
        $("form.send-to").submit(function(){
            if($("html").attr("id") == "validate-page") {
                return confirm("Confirma essa ação? Por favor conferir também se validou todos os pontos de ocorrências em 'abrir mapa'.");
            } else {
                return confirm("Confirm?");
            }
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
                                                           +'<a href="'+base+'profile/'+r[i]._id+'">'
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
            var temp = window.localStorage.getItem("temp:form:"+data._id);
            if(temp != null) {
                try {
                    //data = JSON.parse( temp );
                } catch(Exception) { }
            }
            form.render(schema,data,{collapsedCollapsibles: true});
            setInterval(function(){
                window.localStorage.setItem("temp:form:"+data._id,JSON.stringify( form.getData().data ));
            },1000);
            $("#data").submit(function(e){
                e.preventDefault();
                $("#data .actions button").attr("disabled",true).addClass("disabled").text("Wait...");
                var data = form.getData().data;
                $.post($("#data").attr("action"),JSON.stringify(data),function(r){
                    window.localStorage.removeItem("temp:form:"+data._id);
                    if(r.error){
                        var err = JSON.parse( r.reason.substr("Must follow schema: ".length) );
                        alert("Error: "+err.message+" at "+err.dataPath.substr(1));
                        $("#data .actions button").attr("disabled",false).removeClass("disabled").text("Save");
                    } else {
                        location.href=$("#data").attr("action");
                    }
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
        if(location.hash == "#occurrences") {
            $("a[href='#occ']").click();
        }
        if($("#map").length >= 1) {
            head.js("resources/js/map.js")
                .ready(function(){
                    map.init();
                });
        }
        if($("html").attr("id") == "control-page") {
            $(".collapse").collapse();
            $(".tab-pane:eq(0)").addClass('active');
        }
    });
});
