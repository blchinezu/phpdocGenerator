
// EXPLORER
    function explorer_toggleEdit() {
        $("#explorer .breadcrumbs").toggleClass("editMode");
    }

    function explorer_loadDir(path) {

        $("#explorer .title").addClass('loading');

        $.ajax({
            type:   "POST",
            url:    "ajax.php",
            cache:  false,
            data:   {
                'func': 'explorer_loadDir',
                'path': path
            }
        }).done(function( msg ) {
            $("#explorer .content").html(msg);
            $("#explorer .breadcrumbs .normal")
                .animate({
                    scrollLeft: $("#explorer .breadcrumbs .normal").outerWidth()
                }, 250);
            genereazaPreviewComanda(0);

            $("#explorer .title").removeClass('loading');
        });
    }

    function explorer_loadDirManual() {
        explorer_loadDir( $("#explorer .breadcrumbs .edit input").val() );
    }

// PROIECTE
    function testNumeProiect() {

        var matches = $("#proiecte .project .target").filter(function(){
            return $(this).text() == $("#nume .target").html()
        }).parent();

        $("#proiecte .project.current").removeClass('current');
        $(matches).addClass('current');
    }
    function proiecte_get() {

        $("#proiecte .title").addClass('loading');

        $.ajax({
            type:   "POST",
            url:    "ajax.php",
            cache:  false,
            data:   {
                'func': 'proiecte_get'
            }
        }).done(function( msg ) {
            $("#proiecte .content").html(msg);

            $("#proiecte .title").removeClass('loading');

            testNumeProiect();
        });
    }
    function proiecte_loadExtern(proiect) {
        $("#nume input").val(proiect);
        nume_getTarget();
        explorer_loadDir('/');
        exclude_defaults();
    }
    function proiecte_loadSalvat(proiect) {
        $.ajax({
            type:   "POST",
            url:    "ajax.php",
            cache:  false,
            data:   {
                'func': 'proiecte_loadSalvat',
                'id':   proiect
            }
        }).done(function( msg ) {

            project = $.parseJSON(msg);

            if( project.length == 0 ) {
                alert(msg);
            }
            else {
                $("#nume input").val(project.name);
                nume_getTarget();
                explorer_loadDir(project.from);
                exclude_set(project.exclude);
                template_set(project.template);
            }
        });
    }
    function proiecte_remove(mod, proiect, preview) {

        if( typeof preview == 'undefined' )
            preview = 'true';
        else
            $("#proiecte .title").addClass('loading');

        $.ajax({
            type:   "POST",
            url:    "ajax.php",
            cache:  false,
            data:   {
                'func':    'proiecte_remove',
                'mod':     mod,
                'proiect': proiect,
                'preview': preview
            }
        }).done(function( msg ) {

            if( preview == 'true' ) {
                $("#dialog").html('<b>'+window.i18n['Are you sure you want to execute the following commands?']+'</b><br><br>'+msg).dialog({
                    title: window.i18n['REMOVE PROJECT?'],
                    width: '600px',
                    buttons: [
                        {
                            text: "Da",
                            click: function()
                            {
                                $(this).dialog("close");
                                proiecte_remove(mod, proiect, 'false');
                            }
                        },{
                            text: "NU",
                            click: function()
                            {
                                $(this).dialog("close");
                            }
                        }
                    ],
                });
            }
            else {
                alert(msg);
                proiecte_get();
            }
        });
    }
    function proiecte_open(url) {
        window.open(url,'_blank');
    }

// TEMPLATE
    function template_set(template) {
        $("#template select").val(template);
        genereazaPreviewComanda();
    }

// EXCLUDE
    function exclude_defaults() {

        $("#exclude .title").addClass('loading');

        $.ajax({
            type:   "POST",
            url:    "ajax.php",
            cache:  false,
            data:   {
                'func': 'exclude_defaults'
            }
        }).done(function( msg ) {
            exclude_set(msg);

            $("#exclude .title").removeClass('loading');
        });
    }
    function exclude_addRule(rule, e) {
        exclude_set( $("#exclude .content textarea").val() + "\n" + rule );
    }
    function exclude_set(rules) {
        $("#exclude .content textarea").val(rules).animate({
            scrollTop: $("#exclude .content textarea")[0].scrollHeight
        }, 0);
        genereazaPreviewComanda();
    }

// GENEREAZA COMANDA
    window.gpc_ht = false;
    window.gpc_ha = false;
    function genereazaPreviewComanda(delay) {

        $("#comanda .title").addClass('loading');

        if( window.gpc_ht ) clearTimeout(window.gpc_ht);
        if( window.gpc_ha ) window.gpc_ha.abort();
        if( typeof delay == 'undefined' ) delay = 250;

        window.gpc_ht = setTimeout(function(){
            window.gpc_ha = $.ajax({
                type:   "POST",
                url:    "ajax.php",
                cache:  false,
                data:   {
                    'func':      'genereazaPreviewComanda',
                    'directory': $("#explorer #pwd").val(),
                    'target':    $("#nume input").val(),
                    'exclude':   $("#exclude textarea").val(),
                    'template':  $("#template select").val()
                }
            }).done(function( msg ) {
                $("#comanda input").val(msg);

                $("#comanda .title").removeClass('loading');

                testNumeProiect();
            });
        }, delay);
    }

// NUME
    window.ngt_ht = false;
    window.ngt_ha = false;
    function nume_getTarget(delay) {

        $("#nume .title").addClass('loading');

        if( window.ngt_ht ) clearTimeout(window.ngt_ht);
        if( window.ngt_ha ) window.ngt_ha.abort();
        if( typeof delay == 'undefined' ) delay = 50;

        window.ngt_ht = setTimeout(function(){
            window.ngt_ha = $.ajax({
                type:   "POST",
                url:    "ajax.php",
                cache:  false,
                data:   {
                    'func':      'nume_getTarget',
                    'target':    $("#nume input").val()
                }
            }).done(function( msg ) {
                $("#nume .target").html(msg);

                $("#nume .title").removeClass('loading');

                testNumeProiect();
                genereazaPreviewComanda();
            });
        }, delay);
    }

// GENEREAZA DOCUMENTATIE
    window.periodicStats = false;
    window.runPeriodicStats = false;
    function genereazaDocumentatie() {
        var btn = $("#comanda button");

        if( $('body').hasClass('generating') ) {
            alert(window.i18n['Process already launched!']);
            return false;
        }

        clearAllStats();
        $('body').addClass('generating');
        
        window.runPeriodicStats = true;
        setTimeout(function(){
            getAllStats();
            window.periodicStats = setInterval(getAllStats,5000);
        }, 1000);

        $.ajax({
            type:   "POST",
            url:    "ajax.php",
            cache:  false,
            data:   {
                'func':      'genereazaDocumentatie',
                'directory': $("#explorer #pwd").val(),
                'target':    $("#nume input").val(),
                'exclude':   $("#exclude textarea").val(),
                'template':  $("#template select").val()
            }
        }).done(function( msg ) {

            getAllStats(true);

            proiecte_get();

            $("#butonInchidere").show();

            if( msg != 'OK' ) {
                alert(msg);
            }
        });
    }
    function clearAllStats() {
        $("#serverTime .content").html('-');
        $("#serverLoad .content").html('-');
        $("#running .content").html('-');
        $("#phpdocLog .content").html('-');
    }
    function getAllStats(stop) {

        $("#liveStats > .tab > .title").addClass('loading');
        $.ajax({
            type:   "POST",
            url:    "ajax.php",
            cache:  false,
            data:   {
                'func':   'getAllStats',
                'target': $("#nume input").val(),
                'cmd':    $("#comanda input").val()
            }
        }).done(function( msg ) {

            var stats = $.parseJSON(msg);

            $("#serverTime .content").html( stats.time );
            $("#serverLoad .content").html( stats.load );
            $("#running .content")   .html( stats.proc );
            $("#phpdocLog .content") .html( stats.log  );

            $("#liveStats > .tab > .title").removeClass('loading');

            if( stats.proc.indexOf(window.i18n['STOPPED']) > -1 )
                stopGettingStats();
        });

        if( window.runPeriodicStats == false || stop )
            return stopGettingStats();
    }
    function stopGettingStats() {
        clearInterval(window.periodicStats);
        window.runPeriodicStats = false;
        return false;
    }
    function closeStats() {
        $("#butonInchidere").hide();
        $("body").removeClass('generating');
    }
    function forceStopPhpdoc() {
        $.ajax({
            type:   "POST",
            url:    "ajax.php",
            cache:  false,
            data:   {
                'func':   'forceStopPhpdoc',
                'target': $("#nume input").val(),
                'cmd':    $("#comanda input").val()
            }
        }).done(function( msg ) {

            $("#running .content").html( msg );
        });
    }

$(document).ready(function(){

    explorer_loadDir('/wrk/apache');
    $("#nume input").val('');
    exclude_defaults();
    proiecte_get();
})
