@php
	\Carbon\Carbon::setLocale(config('app.locale'));
@endphp
@if (isset($a_notices))
<div class="panel panel-default">
	<div class="panel-heading" style="font-weight: bold; font-size: 1.2em;">{{ trans('panichd::lang.ticket-notices-title') . ($a_notices->count() > 0 ? ' (' . $a_notices->count() . ')' : '') }}</div>
	<div class="panel-body">
		@if ($a_notices->count() > 0)
		<table class="table table-hover">
			<tbody>
			@foreach ($a_notices as $notice)
				<tr>
				<td style="width: 14em;">{{ $notice->id }}<br /><span style="font-weight: bold">{{ $notice->status->name }}</span>
				<div style="margin: 1em 0em 0em 0em; font-weight: bold;"><span class="glyphicon glyphicon-calendar"></span> {!! $notice->getDateForHumans($notice->limit_date) !!}</div>
				
				</td>
				<td><div style="margin: 0em 0em 1em 0em;">{{ link_to_route($setting->grab('main_route').'.show', $notice->subject, $notice->id) }}</div>
				<p>{{ $notice->content }}
				@if ($notice->all_attachments_count>0)
					<br />{{ $notice->all_attachments_count }} <span class="glyphicons glyphicon glyphicon-paperclip tooltip-info attachment" title="{{ trans('panichd::lang.table-info-attachments-total', ['num' => $notice->all_attachments_count]) }}"></span>
				@endif
				{{ $notice->intervention }}</p>
				@foreach ($notice->tags as $tag)
                    <button class="btn btn-default btn-tag" style="pointer-events: none; background-color: {{ $tag->bg_color }}; color: {{ $tag->text_color }}">{{ $tag->name }}</button>
                @endforeach
				</td>
				</tr>				
			@endforeach
			</tbody>
		</table>
		@else
		{{ trans('panichd::lang.ticket-notices-empty') }}
		@endif
	</div>
</div>
@endif