(function($){
    $.extend({
    progressBarStart: function progressBarStart(value,target,from,progress_id,box_style,back_style,bar_style){
        target = target || $("#progress_box");
        from = PARAM.ajax_url;
        progress_id = progress_id || "#progress";
        box_style = box_style || {
            padding:"10px",
            width:"100%",
            border:"1px solid #666666",
            background:"#ffffff"
        };
        back_style = back_style || {
            background:"#333333",
            width:"100%"
        };
        bar_style = bar_style || {
            background:"#4169e1",
            padding:"5px",
            color: "#ffffff"
        };
        var first_para = 0;
        gogo();
        function gogo(){
            setTimeout(function(){
                first_para==0?createBar():false;
                var action = PARAM.delete_all;
                var data = {action: action, nonce: PARAM.nonce, first: first_para == 0, post_type: PARAM.post_type};
                var processData = true;
                var contentType = "application/x-www-form-urlencoded; charset=UTF-8";
                if (value == 'import') {
                   data.action = PARAM.import_csv;
                   if (first_para == 0) {
                     fd = new FormData();
                     fd.append("action", data.action);
                     fd.append("nonce", data.nonce);
                     fd.append("first", data.first);
                     fd.append("import_file", $("#csvfile").prop('files')[0]);
                     fd.append("post_type", data.post_type);
                     data = fd;
                     processData = false;
                     contentType = false;
                   }
                }
                $.ajax({
                    data: data,
                    url: from,
                    type: 'post',
                    cache: false,
                    processData: processData,
                    contentType: contentType,
                    success:function(res){
                        $("#displaynonespan").html(res);
                        var num = Number($(progress_id).html());
                        $("#progress_bar_box div div").animate({width:num+"%"},100).html(num + "%");
                        //target.html("working: " + num + "%");
                        if (num < 100) {
                            gogo();
                        } else {
                            closeBar();
                            location.reload();
                        }
                    }
                });
                first_para++;
            },200);
        }
        function createBar(){
            $("body").append("<span id='displaynonespan' style='display:none'></span>");
            target.html("working:");
            target.append("<div id='progress_bar_box'><div><div></div></div></div>");
            target.css({width: "100%"});
            $("#progress_bar_box").css(box_style);
            $("#progress_bar_box div").css(back_style);
            $("#progress_bar_box div div").css({
                width:"0%"
            }).css(bar_style);
        }
    }});
    $(function(){
        $("button.ajax").click(function(){
            if ($(this).val() == 'delete_all') {
                if (!confirm('本当に削除しますか？')) {
                    $(this).modaal('close');
                    return false;
                }
            }
            if ($(this).val() == 'import' && $("#csvfile").val().length == 0) {
                alert("ファイルを選択してください");
                $(this).modaal('close');
                return false;
            }
            $(this).modaal('open');
            $(this).prop("disabled", true);
            closeBar();
            $.progressBarStart($(this).val());
            return false;
        });
    });
    $(".ajax").modaal({
        content_source: '#inline',
        overlay_close: false,
        is_locked: true
    });
})(jQuery)
function closeBar() {
    $("#displaynonespan").remove();
    $("#progress_box").empty();
}


