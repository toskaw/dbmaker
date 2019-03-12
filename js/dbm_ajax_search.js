(function($){
    function viewModel() {
        var self = this;
        self.object = ko.observableArray();
    }
    var vm = new viewModel();
    ko.applyBindings(vm);

    $.extend({
    ajaxStart: function ajaxStart(value,target,from){
        target = target || $("#result_box");
        from = PARAM.ajax_url;
        gogo();
        function gogo(){
            setTimeout(function(){
                var action = PARAM.search;
                var data = "&action=" +  action +"&nonce=" + PARAM.nonce;
                var processData = true;
                var contentType = "application/x-www-form-urlencoded; charset=UTF-8";
                $.ajax({
                    data: $("form.ajax-search-form").serialize() + data,
                    url: from,
                    type: 'post',
                    cache: false,
                    processData: processData,
                    contentType: contentType,
                    dataType: 'json',
                    success:function(res){
                        vm.object(res.data);
                        $("button.ajax").prop("disabled", false);
                    }
                });
            },200);
        }
    }});
    $(function(){
        $("button.ajax").click(function(){
            $(this).prop("disabled", true);
            $.ajaxStart($(this).val());
            return false;
        });
    });

})(jQuery)


