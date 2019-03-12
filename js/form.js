(function($, window, document, _){

    $(document).on('click','#submit',function(){
        $('#errText').empty();
        var nameValue = _.escape($('#ComponentName').val());
        var labelName = _.escape($('#LavelName').val());
        if(!(nameValue && labelName))
        {
            $('#errText').empty().text('テキストボックス名及びラベル名は必須です');
            return false;
        }

        if(!(/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(nameValue)))
        {
            $('#errText').empty().text('テキストボックス名に利用できる文字は半角英数字です。また先頭文字には数字を使うことができません');
            return false;
        }
        //var args = top.tinymce.activeEditor.windowManager.getParams();
        var inputTag = '<div id="inputTextDiv"><p><label for="'+ nameValue +'">'+labelName+'</label></p><p><input type="text" name="' + nameValue + '" id="' + nameValue + '" class="form-control"></p></div>';
        top.tinymce.activeEditor.selection.setContent(inputTag);
        top.tinymce.activeEditor.windowManager.close();
        return false;
    });

    $(document).on('click','#cancel',function(){
        $('#ComponentName').val('');
        $('#LavelName').val('');
        $('#errText').empty();
        top.tinymce.activeEditor.windowManager.close();
        return false;
    });

})(jQuery, window, document, _);