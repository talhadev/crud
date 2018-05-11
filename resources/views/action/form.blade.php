<div class="col-md-8 col-md-offset-2">
    <div class="panel panel-default">
        <div class="panel-heading">
            {{$sumbitButtonText}}
        </div>
        <div class="panel-body">        
            
            <div class="row">
                <div class="col-md-12">
                    <div class="col-md-6">
                    
                        <div class="form-group">

                            {!! Form::label('controller', 'Controller Name:') !!}
                            {!! Form::select('controller', $controllers, null, ['class' => 'form-control']) !!}                            

                        </div>
                        <div class="form-group">

                            {!! Form::label('call', 'Call:') !!}
                            {!! Form::select('call', $calls, null, ['class' => 'form-control']) !!}

                        </div>


                    </div>
                    <div class="col-md-6">


                        <div class="form-group has-feedback">

                            {!! Form::label('action', 'Action Name:') !!}
                            {!! Form::text('action', null, ['class' => 'form-control', 'placeholder' => 'actions' ]) !!}
                            <span class="glyphicon glyphicon-pencil form-control-feedback" aria-hidden="true"></span>

                        </div>
                        <div class="form-group has-feedback">

                            {!! Form::label('method', 'Method Name:') !!}
                            {!! Form::text('method', null, ['class' => 'form-control', 'placeholder' => 'Method' ]) !!}
                            <span class="glyphicon glyphicon-pencil form-control-feedback" aria-hidden="true"></span>

                        </div>

                    </div>
                </div>
                
            </div>
            
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




