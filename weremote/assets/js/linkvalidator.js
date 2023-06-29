jQuery( document ).ready( function( $ ) {

    // Ajax for "Validate Links" button
    $( '#validate-links-btn' ).on( 'click', function() {
        var button  = $(this);
        var spinner = $('.spinner');

        button.prop('disabled', true);
        spinner.show();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'validate_links'
            },
            success: function(response) {
                spinner.hide();
                
                if( (response.new_links_count) > 0 ) {
                    $('table.linkvalidator-table tbody').empty();
                    
                    response.links.forEach(function(link) {

                        link.status_color = (link.status === '404 Not Found') ? 'color-danger' : 'color-warning';
                        link.status_icon = (link.status === '404 Not Found') ? 'table-icon icon-danger color-danger' : 'table-icon icon-warning color-warning';
                        var status_title = (link.status === '403 Forbidden') ? 'You do not have permission to access this resource' : '';

                        var row = '<tr>' +
                                  '<td><i class="' + link.status_icon + ' fa fa-exclamation-triangle"></i>' +
                                  '<a target="_blank" href="' + link.link + '">' + link.link + '</a></td>' +
                                  '<td><strong class="' + link.status_color + '" title="'+status_title+'">' + link.status + '</strong></td>' +
                                  '<td><strong>' + link.origin + '</strong></td>' +
                                  '</tr>';
                    
                        $('tbody').append(row);
                    });

                    var message = $('<span class="message" style="margin-left: 1rem; margin-top: 0.3rem;">' + response.new_links_count + ' new links detected</span>');
                    button.after( message );
                    message.delay(3000).fadeOut('slow', function() {
                        $(this).remove();
                        button.prop('disabled', false);
                        
                        $( '#clear-table-btn' ).parent().css('display', 'flex');
                        $( '#clear-table-btn' ).show();
                    });

                } else {

                    var message = $('<span class="message" style="margin-left: 1rem; margin-top: 0.3rem;">' + response.message + '</span>');
                    button.after( message );

                    message.delay(3000).fadeOut('slow', function() {
                        $(this).remove();
                        button.prop('disabled', false);
                        $( '#clear-table-btn' ).parent().css('display', 'flex');
                        $( '#clear-table-btn' ).show();
                    });

                }
            },
            error: function(xhr, status, error) {
                console.log(error);

                button.prop('disabled', false);
                spinner.hide();
            }
        });
    });

    // Ajax for "Clear Table" button
    $( '#clear-table-btn' ).on( 'click', function() {
        var $button  = $(this);
        var $spinner = $('.spinner-clear');

        $button.prop('disabled', true);
        $spinner.show();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'clear_table_ajax',
            },
            success: function(response) {
                if( response.status == "OK" ) {
                    $('table.linkvalidator-table tbody').empty();
                    $button.prop('disabled', false);
                    $spinner.remove();
                    $button.hide();
                }
            },
            error: function(xhr, status, error) {
                console.log(error);
                $button.prop('disabled', false);
                $spinner.remove();
            }
        });
    });
    
});






