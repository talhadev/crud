<div class="col-md-10 col-md-offset-1">
    <div class="panel panel-default">
        <div class="panel-heading">
            {{$sumbitButtonText}}
        </div>
        <div class="panel-body">        
            
            <div class="row">
                <div class="col-md-12">
                    <div class="col-md-6">
                    
                        <div class="form-group has-feedback">

                            {!! Form::label('technify_store_id', 'Technify Store ID:') !!}
                            {!! Form::text('technify_store_id', null, ['class' => 'form-control', 'placeholder' => 'Technify Store ID']) !!}
                            <span class="glyphicon glyphicon-pencil form-control-feedback" aria-hidden="true"></span>

                        </div>

                    </div>
                    <div class="col-md-6">

                        <div class="form-group has-feedback">

                            {!! Form::label('name', 'Store Name:') !!}
                            {!! Form::text('name', null, ['class' => 'form-control', 'placeholder' => 'Store Name']) !!}
                            <span class="glyphicon glyphicon-pencil form-control-feedback" aria-hidden="true"></span>

                        </div>

                    </div>
                </div>
                
            </div>

            <div class="row">
                <div class="col-md-12">                
                    <div class="col-md-6">                

                        <div class="form-group has-feedback">

                            {!! Form::label('email', 'Email:') !!}
                            {!! Form::email('email', null, ['class' => 'form-control', 'placeholder' => 'Email', (isset($store)) ? 'readonly' : '' ]) !!}
                            <span class="glyphicon glyphicon-pencil form-control-feedback" aria-hidden="true"></span>

                        </div>

                    </div>  

                    <div class="col-md-6">                

                        <div class="form-group has-feedback">

                            {!! Form::label('telephone', 'Telephone:') !!}
                            {!! Form::text('telephone', null, ['class' => 'form-control', 'placeholder' => 'Telephone']) !!}
                            <span class="glyphicon glyphicon-pencil form-control-feedback" aria-hidden="true"></span>

                        </div>

                    </div>  
                    
                </div>
            </div>

            <div class="row">
                <div class="col-md-12"> 

                    <div class="col-md-6">                
                        <div class="form-group">
                            {!! Form::label('platform', 'Platform:') !!}
                            {!! Form::select('platforms', $platform, null, ['class' => 'form-control']) !!}   
                        </div>
                    </div>  
                    
                    <div class="col-md-6">
                        <div class="form-group">

                            {!! Form::label('module_active', 'Module Active:') !!}
                            {!! Form::select('module_active', $module_active, null, ['class' => 'form-control']) !!} 

                        </div>
                    </div>

                </div>
            </div>

            <div class="row">
                <div class="col-md-12">                
                    <div class="col-md-12">                

                        <div class="form-group has-feedback">

                            {!! Form::label('address', 'Warehouse Address:') !!}
                            {!! Form::text('address', null, ['class' => 'form-control', 'placeholder' => 'Address']) !!}   
                            <span class="glyphicon glyphicon-pencil form-control-feedback" aria-hidden="true"></span>

                        </div>

                    </div>  
                    
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">                
                    <div class="col-md-12">
                        <div class="form-group has-feedback">

                            {!! Form::label('store_url', 'Store url:') !!}
                            {!! Form::text('store_url', null, ['class' => 'form-control', 'placeholder' => 'Store url']) !!}
                            <span class="glyphicon glyphicon-pencil form-control-feedback" aria-hidden="true"></span>

                        </div>
                    </div>                    
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="col-md-12">
                        <div class="form-group has-feedback">

                            {!! Form::label('endpoint', 'End Point:') !!}
                            {!! Form::text('endpoint', null, ['class' => 'form-control', 'placeholder' => 'End Point']) !!}
                            <span class="glyphicon glyphicon-pencil form-control-feedback" aria-hidden="true"></span>

                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">                
                    <div class="col-md-12">
                        <div class="form-group has-feedback">

                            {!! Form::label('auth', 'Auth:') !!}
                            {!! Form::textarea('auth', null, ['class' => 'form-control', 'placeholder' => 'Auth', 'rows' => '3']) !!}
                            <span class="glyphicon glyphicon-pencil form-control-feedback" aria-hidden="true"></span>

                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">                
                    <div class="col-md-12">
                        <div class="form-group has-feedback">

                            {!! Form::label('support_email', 'Support email:') !!}
                            {!! Form::text('support_email', null, ['class' => 'form-control', 'placeholder' => 'Support email']) !!}
                            <span class="glyphicon glyphicon-pencil form-control-feedback" aria-hidden="true"></span>
                            <span class="help-block" style="font-size: 12px;">one or more email comma seprated</span>

                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">                
                    <div class="col-md-12">                

                        <div class="form-group">
                            {!! Form::label('isadmin', 'Is Admin:') !!}
                            {!! Form::checkbox('isadmin', 0, (isset($store) && $store->isadmin == 1) ? true : false, ['id' => 'isadmin', 'onchange' => 'isAdminChange(this)']) !!} 
                            <span class="help-block" style="font-size: 10px;">only for this store user is admin</span>  
                        </div>

                    </div>  
                    
                </div>
            </div>

            @if(isset($store))
                <div class="row">
                    <div class="col-md-12">                
                        <div class="col-md-6">
                            <div class="form-group has-feedback">

                                {!! Form::label('uuid', 'Uuid:') !!}
                                {!! Form::text('uuid', null, ['class' => 'form-control', 'placeholder' => 'uuid', 'disabled']) !!}
                                <span class="glyphicon glyphicon-pencil form-control-feedback" aria-hidden="true"></span>

                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group has-feedback">

                                {!! Form::label('password', 'Password:') !!}
                                {!! Form::text('password', null, ['class' => 'form-control', 'placeholder' => 'password', 'disabled']) !!}
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

                        {!! Form::submit($sumbitButtonText , ['class' => 'btn btn-primary']) !!}

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>



<script type="text/javascript">
    function isAdminChange(value) {
        if(value.checked) {
            $('#isadmin').val(1);
        } else {
            $('#isadmin').val(0);
        }
    }
</script>
