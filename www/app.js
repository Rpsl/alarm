$('document').ready(function(){
    var box = $('input');

    $(box).on('change', function(){
        $.post('/save', $('form').serialize(), function(data, status, xhr){

        });
    });
});