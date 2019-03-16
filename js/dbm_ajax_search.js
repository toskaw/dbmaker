(function($){
    function PagerViewModel(observableItems, itemsLimit) {
        var self = this;

        self.isLoading = ko.observable(false);        // 読み込み中か否か
        self.count = ko.observable(0);                // アイテム総数
        self.current = ko.observable(1);              // 現在のページ番号
        self.current_input = ko.observable(1);        // 現在のページ番号(入力値)
        self.limit = ko.observable( itemsLimit || 20 ); // 1ページ中の最大アイテム数
        self.pages = ko.computed(function() {         // 総ページ数
            return Math.ceil(self.count() / self.limit())
        });
        self.offset = ko.computed(function() {        // 表示中のアイテムオフセット
            return (self.current() - 1) * self.limit()
        });
        self.items = observableItems;                 // アイテムリスト

        // ページ移動アクション
        self.goTo = function(page) {
            if (page < 1) page = 1;
            if (page > self.pages()) page = self.pages();
            self.current(page);
            self.current_input(page);
        };
        self.goToFirst = function() { self.goTo(1) };
        self.goToPrev = function() { self.goTo(self.current() - 1) };
        self.goToNext = function() { self.goTo(self.current() + 1) };
        self.goToLast = function() { self.goTo(self.pages()) };
        self.goToInputted = function() { self.goTo(self.current_input()) };
    }

    function viewModel() {
        var self = this;
        self.object = ko.observableArray();
        self.pager = new PagerViewModel(self.object, $("#posts_per_page").val());
    }
    var vm = new viewModel();
    ko.applyBindings(vm);

    $.extend({
    ajaxStart: function ajaxStart(value,target,from){
        target = target || $("#result_box");
        from = PARAM.ajax_url;
        if (vm.pager.items.peek().length == 0 && value == 'reload') return false;
        vm.pager.isLoading(true);
        gogo();
        function gogo(){
            setTimeout(function(){
                var action = PARAM.search;
                var data = "&action=" +  action +"&nonce=" + PARAM.nonce + "&paged=" + vm.pager.current();
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
                    success: function(res){
                        vm.object(res.data);
                        vm.pager.count(res.found_posts);
                    },
                    complete: function() {
                        vm.pager.isLoading(false);
                        $("button.ajax").prop("disabled", false);
                    }
                });
            },200);
        }
    }});
    vm.reload = function() {
        vm.pager.offset();
        $.ajaxStart('reload');
    }
    ko.computed(vm.reload);


    $(function(){
        $(".ajax-search-form").validate({submitHandler: function(form) {return false;}});
        $("button.ajax").click(function(){
            if ($(".ajax-search-form").valid()) {
                $(this).prop("disabled", true);
                vm.pager.current(1);
                $.ajaxStart($(this).val());
            }
            return false;
        });
    });


})(jQuery)


