$( document ).ready(function() {
    $("#sortableParameters").sortable({
        items: ".card"
    });
    $('[data-toggle="tooltip"]').tooltip();
    $('#hpdefault').parent().hide();
    $('#AddProject').on('click', function(){
        clone = $('#hpdefault').parent().clone();
        clone.appendTo("#sortableParameters").show();
        $("#sortableParameters").sortable({
            items: ".card"
        });
    });
    $("#full_config").on("click", function(){
        $("#accordion .collapse").addClass("show");
    });
});
