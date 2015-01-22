 // toplink
    $('#top').hide();
    $(window).scroll(function() {
        if ($(window).scrollTop() >= 500) {
            $('#top').fadeIn(300);
        } else {
            $('#top').fadeOut(300);
        }
    });
    $('#top').each(function() {
        $(this).click(function() {
            $('html,body').animate({
                scrollTop: 0
            }, 'slow');
            return false;
        });
    });
	
	
	function displayResult(item) {
        $('.alert').show().html('You selected <strong>' + item.value + '</strong>: <strong>' + item.text + '</strong>');
    }
				
$("#search").typeahead({
    onSelect: function(item) {
        console.log(item);
    },
    ajax: {
        url: "/path/to/source",
        timeout: 500,
        displayField: "title",
        triggerLength: 3,
        method: "get",
        loadingClass: "loading-circle",
        preDispatch: function (query) {
            //showLoadingMask(true);
			console.log('begin search');
            return {
                search: query
            }
        },
        preProcess: function (data) {
            //showLoadingMask(false);
			console.log('end search');
            if (data.success === false) {
				console.log('end with error');
                // Hide the list, there was some error
                return false;
            }			
			if (data.fail === true) {
				console.log('end with error');
			}
            // We good!
            return data.mylist;
        }
    }
});