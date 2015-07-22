// <![CDATA[
jQuery(document).ready(function() {
    jQuery('.hide-if-no-js-cft').show();
    jQuery('.hide-if-js-cft').hide();

    inlineEditPost.addEvents = function(r) {
        r.each(function() {
            var row = jQuery(this);
            jQuery('a.editinline', row).click(function() {
                inlineEditPost.edit(this);
                post_id = jQuery(this).parent().parent().parent().parent().attr('id').replace('post-','');
                inlineEditPost.cft_load(post_id);
                return false;
            });
        });
    }

    inlineEditPost.save = function(id) {
        if( typeof(id) == 'object' )
            id = this.getId(id);

        jQuery('table.widefat .inline-edit-save .waiting').show();

        var params = {
            action: 'inline-save',
            post_type: 'typenow',
            post_ID: id,
            edit_date: 'true'
    };

    var fields = jQuery('#edit-'+id+' :input').fieldSerialize();
    params = fields + '&' + jQuery.param(params);

    // make ajax request
    jQuery.post('admin-ajax.php', params,
        function(r) {
            jQuery('table.widefat .inline-edit-save .waiting').hide();

            if (r) {
                if ( -1 != r.indexOf('<tr') ) {
                    jQuery(inlineEditPost.what+id).remove();
                    jQuery('#edit-'+id).before(r).remove();

                    var row = jQuery(inlineEditPost.what+id);
                    row.hide();

                    if ( 'draft' == jQuery('input[name="post_status"]').val() )
                        row.find('td.column-comments').hide();

                    row.find('.hide-if-no-js').removeClass('hide-if-no-js');
                    jQuery('.hide-if-no-js-cft').show();
                    jQuery('.hide-if-js-cft').hide();

                    inlineEditPost.addEvents(row);
                    row.fadeIn();
                } else {
                    r = r.replace( /<.[^<>]*?>/g, '' );
                    jQuery('#edit-'+id+' .inline-edit-save').append('<span class="error">'+r+'</span>');
                }
            } else {
                jQuery('#edit-'+id+' .inline-edit-save').append('<span class="error">'+inlineEditL10n.error+'</span>');
            }
        }
        , 'html');
    return false;
}

jQuery('.editinline').click(function () {post_id = jQuery(this).parent().parent().parent().parent().attr('id').replace('post-',''); inlineEditPost.cft_load(post_id);});
inlineEditPost.cft_load = function (post_id) {
    jQuery.ajax({type: 'GET', url: '?page=custom-field-template/custom-field-template.php&cft_mode=ajaxload&post='+post_id, success: function(html) {jQuery('#cft').html(html);}});
};
});
//-->