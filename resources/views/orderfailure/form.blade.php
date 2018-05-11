<div class="col-md-8 col-md-offset-2">
    <div class="panel panel-default">
        <div class="panel-heading">
            {{$sumbitButtonText}}  
        </div> 
        <div class="panel-body">        
            
            <div class="row">
                <div class="col-md-12">
                    <div class="col-md-6">
                    
                        <div class="form-group has-feedback">

                            {!! Form::label('order_id', 'Store Order ID:') !!}
                            {!! Form::text('order_id', null, ['class' => 'form-control', 'placeholder' => 'Order ID', 'disabled']) !!}
                            <span class="glyphicon glyphicon-pencil form-control-feedback" aria-hidden="true"></span>

                        </div>

                    </div>
                    <div class="col-md-6">

                        <div class="form-group has-feedback">

                            {!! Form::label('store', 'Store Name:') !!}
                            {!! Form::text('store', $store_name->name, ['class' => 'form-control', 'placeholder' => 'Store ID', 'disabled']) !!}
                            <span class="glyphicon glyphicon-pencil form-control-feedback" aria-hidden="true"></span>

                        </div>

                    </div>
                </div>
                
            </div>

            <div class="row">
                <div class="col-md-12">                
                    <div class="col-md-12">                

                        <div class="form-group has-feedback">

                            {!! Form::label('failure_address', 'Failure Address:') !!}
                            {!! Form::text('failure_address', null, ['class' => 'form-control', 'placeholder' => 'Failure Address']) !!}   
                            <span class="glyphicon glyphicon-pencil form-control-feedback" aria-hidden="true"></span>

                        </div>

                    </div>  
                    
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">                
                    <div class="col-md-12">
                        <div class="form-group">

                            {!! Form::label('failure_city', 'Failure City:') !!}
                            {!! Form::select('failure_city', $cities, null, ['class' => 'form-control']) !!}
                            
                        </div>
                    </div>                    
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">                
                    <div class="col-md-6">
                        <div class="form-group has-feedback">

                            {!! Form::label('telephone', 'Customer Telephone:') !!}
                            {!! Form::text('telephone', null, ['class' => 'form-control', 'placeholder' => 'Telephone']) !!}
                            <span class="glyphicon glyphicon-pencil form-control-feedback" aria-hidden="true"></span>
                            
                        </div>
                    </div>         

                    <div class="col-md-6">
                        <div class="form-group has-feedback">

                            {!! Form::label('email', 'Customer Email:') !!}
                            {!! Form::text('email', null, ['class' => 'form-control', 'placeholder' => 'Email']) !!}
                            <span class="glyphicon glyphicon-pencil form-control-feedback" aria-hidden="true"></span>
                            <span class="help-block" style="font-size: 12px;">space not allowed</span>
                            
                        </div>
                    </div>               
                </div>
            </div>
            
                        @if($failure_order)

                <div class="row">
                    <div class="col-md-12">
                        <div class="col-md-6">
                        
                            <div class="form-group has-feedback">

                                {!! Form::label('price', 'Order Price:') !!}
                                {!! Form::text('price', null, ['class' => 'form-control', 'placeholder' => 'Order Price', 'disabled']) !!}
                                <span class="glyphicon glyphicon-pencil form-control-feedback" aria-hidden="true"></span>

                            </div>

                        </div>
                        <div class="col-md-6">

                            <div class="form-group has-feedback">

                                {!! Form::label('country', 'Order Country:') !!}
                                {!! Form::text('country', null, ['class' => 'form-control', 'placeholder' => 'Order Country', 'disabled']) !!}
                                <span class="glyphicon glyphicon-pencil form-control-feedback" aria-hidden="true"></span>

                            </div>

                        </div>
                    </div>                
                </div>

            @endif
            
        </div>

        <div class="panel-footer">

            <div class="row">
                <div class="col-md-12">
                    <div class="pull-right">

                        {!! Form::submit($sumbitButtonTextProceed, ['class' => 'btn btn-primary', 'name' => 'update_proceed']) !!}
                        {!! Form::submit($sumbitButtonText , ['class' => 'btn btn-primary', 'name' => 'update']) !!}

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>




