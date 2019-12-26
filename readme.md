# News Crawler

## Install for link click tracking

Add following code to link want to track.

        <script type='text/javascript'>
            function link_click(domain_id){
                jQuery.ajax({
                    url: '<?= admin_url('admin-ajax.php') ?>', // or example_ajax_obj.ajaxurl if using on frontend
                    data: {
                        'action': 'add_link_click_count',
                        'data_id' : domain_id
                    },
                    success:function(data) {
                        // This outputs the result of the ajax request
                        console.log(data);
                    },
                    error: function(errorThrown){
                        console.log(errorThrown);
                    }
                });
            }
        </script>
        
        <a href="[link_address]" target="_blank" onclick="link_click(1);">[link_text]</a>

