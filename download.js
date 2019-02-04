$(document).ready(function () 
{
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $('#download-master').on('click', function(e) 
    {
        if($(this).is(':checked',true))  
        {
            $(".multiple_download").prop('checked', true);  
        } else {  
            $(".multiple_download").prop('checked',false);  
        }  
    });

    $('.download-all').on('click', function(e) {

        var allVals = [];  
        $(".multiple_download:checked").each(function() {  
            allVals.push($(this).attr('data-id'));
        });  

        if(allVals.length <=0)  
        {  
            alert("Please select row to download.");  
        }  
        else 
        {  
            var join_selected_values = allVals.join(",");
            $.ajax({
                url: $(this).data('url'),
                type: 'POST',
                data:'_token = <?php echo csrf_token() ?>',
                data: 'ids='+join_selected_values,
                success: function (data) 
                {
                    $('.zip-file').multiDownload();
                },
                error: function (data) {
                    alert(data.responseText);
                }
            }); 
        }  
    });

   
});
