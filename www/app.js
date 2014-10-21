$('document').ready(function(){
    $(document).on('ajaxError', function(e, xhr, options){
        alert('Произошла ошибка при сохранение.');
        location.reload();
    });

    $('input').on('change', function(){
        $.post('/save', $('form').serialize(), function(data, status, xhr){

        });
    });
});