head
.js(connect+'/js/connect.js')
.js('resources/js/jquery.min.js')
.ready(function(){
    head
    .js('resources/js/bootstrap.min.js')
    .js('resources/js/onde.js')
    .ready(function(){
        var base = window.base;

        Connect({
            onlogin: function(user) {
                if(!test && !logged) {
                    $.post(base+'login',JSON.stringify(user),function(){
                        location.reload();
                    });
                }
            },
            onlogout: function(nothing){
                if(!test && logged) {
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
                return test || confirm("Confirma excluir esse recurso?");
            });
        });

        $("form.send-to").submit(function(){
            if($("html").attr("id") == "validate-page") {
                return test || confirm("Confirma essa ação? Por favor conferir também se validou todos os pontos de ocorrências em 'abrir mapa'.");
            } else {
                return test || confirm("Confirm?");
            }
        });

        if($("html").attr("id") == "edit-page" || $("html").attr("id") == "review-page") {

            var form = new onde.Onde($("#data"));
            form.render(schema,data,{collapsedCollapsibles: true});

            $("#data").submit(function(e){
                e.preventDefault();
                $("#data .actions button").attr("disabled",true).addClass("disabled").text("Wait...");
                var data = form.getData().data;
                $.post($("#data").attr("action"),JSON.stringify(data),function(r){
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

            setInterval(function(){
                var habits = $("select")
                                .filter(
                                    function(i,f){ 
                                        return $(f).attr("id").match(/ecology-habitat/);
                                    }
                                ).map(function(i,f){return f.value});

                $.getJSON(base+'habitats2fito?habitats='+encodeURIComponent(JSON.stringify(habits)),function(data){
                    var label = $("li[id*='fitofisionomies']>label").first();
                    if(data.length >= 1) {
                        label.html("Fitofisionomies: <small>recommends: "+data+"</small>");
                    } else {
                        label.html("Fitofisionomies:");
                    }
                });
            },1000);
        }

        if($("html").attr("id") == "validate-page") {
            for(var i in schema.properties) {
                $("#field").append("<option>"+ schema.properties[i].label +"</option>");
            }
        }

        $(".tab-pane:eq(0)").addClass('active');

    });
});
