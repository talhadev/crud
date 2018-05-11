<div class="row">
    <div class="col-md-10 col-md-offset-1">
        <div class="col-md-5">
            <div class="form-group">
                {!! Form::select('fltr_controller', $controller, null, ['class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-md-5">
            <div class="form-group has-feedback">
                {!! Form::text('fltr_action', null, ['class' => 'form-control', 'placeholder' => 'action name' ]) !!}
                <span class="glyphicon glyphicon-filter form-control-feedback" aria-hidden="true"></span>
            </div>
        </div>
        <div class="col-md-2">
            {!! Form::submit($sumbitButtonText , ['class' => 'btn btn-primary']) !!}
        </div>
    </div>
</div>
