<div class="row">
    <div class="col-md-12"> 

        <div class="col-md-2 col-md-offset-5" style="display: none;">
            <img src="loader.gif">
        </div> 
        <div class="clearfix"></div>

        <div class="alert alert-success" style="display: none;"></div>
        <div class="alert alert-danger" style="display: none;"></div>

        <div class="form-group">
            {!! Form::hidden('store_id', null, ['class' => 'form-control', 'id' => 'store_id', 'placeholder' => 'store ID']) !!}            
        </div>

        <div class="form-group has-feedback">
            {!! Form::textarea('order_status', null, ['class' => 'form-control', 'id' => 'order_status', 'placeholder' => 'Order Status', 'rows' => 4]) !!}
            <span class="glyphicon glyphicon-pencil form-control-feedback" aria-hidden="true"></span>
        </div>  

        <div class="form-group has-feedback">
            {!! Form::textarea('short_desc', null, ['class' => 'form-control', 'id' => 'short_desc', 'placeholder' => 'Short desccription', 'rows' => 2]) !!}
            <span class="glyphicon glyphicon-pencil form-control-feedback" aria-hidden="true"></span>
        </div>       
        
        {!! Form::submit($sumbitButtonText , ['class' => 'btn btn-primary pull-right', 'id' => 'btn_settings']) !!}
        
    </div>
</div>
