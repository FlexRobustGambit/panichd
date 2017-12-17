@extends($master)
@section('page', trans('ticketit::admin.category-create-title'))

@include('panichd::shared.common')
@include('panichd::shared.colorpicker')

@section('content')
	<div class="well bs-component">
        {!! CollectiveForm::open(['route'=> $setting->grab('admin_route').'.category.store', 'method' => 'POST', 'class' => 'form-horizontal']) !!}
            <legend>{{ trans('ticketit::admin.category-create-title') }}</legend>
            @include('panichd::admin.category.form')
			@include('panichd::admin.category.modal-email')
        {!! CollectiveForm::close() !!}
    </div>
	@include('panichd::admin.category.modal-reason')
	@include('panichd::admin.category.modal-tag')	
@stop

@section('footer')
	@include('panichd::admin.category.scripts-create-edit')
@append
