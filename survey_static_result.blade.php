@extends('layouts.main')
@section('content')
<style type="text/css">
	#aione_form_wrapper_abc{
		border:1px solid #e8e8e8;
		margin-bottom: 10px;
		padding: 10px
	}
	.remove_condition i{
		    font-size: 22px;
    display: inline-block;
    margin-top: 54px;
    margin-right: 30px;
    float: right;
	}
	.search-options,.result-options{
		margin-bottom: 10px
	}
	.search-options input[type=submit],
	.result-options input,
	.result-options a{
		float:right;
	}
	.result-options input{
		    float: left;
    background-color: #168dc5;
    color: white;
   
	
    display: inline-block;
    padding: 0 10px;
   
   
   
    font-size: 16px;
    line-height: 30px;
    font-weight: 400;
    font-family: "Open Sans",Arial,Helvetica,sans-serif;
    text-align: center;
    cursor: pointer;
    -webkit-transition: all 150ms ease-out;
    -moz-transition: all 150ms ease-out;
    -o-transition: all 150ms ease-out;
    transition: all 150ms ease-out;	
	}
	/*.result-options input:hover{
		background-color: #168dc5
	}*/

</style>
@php

if(!empty($data)){

	$head = [
	"s_no"=>"S.no",
  "address" => "Address of the accident site",
  "accident_date" => "Date of accident",
  "accident_time" => "Time of accident",
  "accident_type" => "Type of collision",
  "type_of_vehicle"=>"Type of vehicle involved",
  "sub_type_of_road" => "Road features",
  "type_of_injury1" => "Number of fatally injured persons",
  "type_of_injury2" => "Number of grievous injured persons",
  "type_of_injury3" => "Number of minor injured persons" ];
 	// $data = json_decode(json_encode($data->all()),true);  Address of

 	// dump($data[0]);
	//$keys = array_keys($data[0]);
	$keys = array_values($head);
if($id==1){
	$collect = collect($data);
	$no_of_fatalities = $collect->sum('no_of_fatalities');
	$no_of_persons_grievously_injured = $collect->sum('no_of_persons_grievously_injured');
	$no_of_persons_with_minor_injuries = $collect->sum('no_of_persons_with_minor_injuries');
}
if($id==2 || $id==5){
	$collection = collect($data);
	 $no_of_fatalities = $collection->where('type_of_injury',1)->count();
	 $no_of_persons_grievously_injured = $collection->where('type_of_injury',2)->count();
	 $no_of_persons_with_minor_injuries = $collection->where('type_of_injury',3)->count();
}

$th = $option='';
	foreach($keys as $key =>$val){
		 $option[$val] = $val;
		$th .= "<th>$val</th>";
	}

	$dt = Carbon\Carbon::now();

}
$page_title_data = array(
    'show_page_title' => 'yes',
    'show_add_new_button' => 'no',
    'show_navigation' => 'yes',
    'page_title' => 'Survey Report',
    'add_new' => '+ Add Media'
); 
@endphp 
@include('common.pageheader',$page_title_data) 
@include('common.pagecontentstart')
@include('common.page_content_primary_start')
@include('organization.survey._tabs')

 <div  class="field-wrapper field-wrapper-SLUG field-wrapper-type-select ">
	
		{!! Form::open(['route'=>['custom.survey.report',$id],'method'=>'post' ]) !!}

	<div class="aione-row result-options">
				{{-- From <input type="text" name="from" ><br>
				To <input type="text" name="to" ><br> --}}

				
				<div style="float: left;width: 400px">{!! FormGenerator::GenerateForm('static_survey_form_to') !!}</div>
				{!! Form::submit('Search',['value'=>'Filters' , 'class'=>'aione-button aione-button-large aione-button-light aione-button-square add-new-button','style'=>'float:left']) !!}
				{!! Form::submit('Export records as CSV',['name'=>'export','class'=>'aione-button aione-button-large aione-button-light aione-button-square add-new-button','style'=>'float:right']) !!}
				
			</div>	
				
	{!! Form::close() !!}
</div>
@if(!empty($data))
<div class="aione-table">
	<table id="info">
		<thead>
			<tr>
				<th colspan="4">Summary information</th>
			</tr>
		</thead>
			
		<tbody>
			<tr>
				<td>Reporting date</td>
				<td> {{date('d-m-Y')}}</td>
				<td>Reporting week </td>
				<td>{{$dt->weekOfMonth}}</td>
			</tr>
			<tr>
				<td>Reporting unit name</td>
				<td colspan="3">______________________</td>
			</tr>
			<tr>
				<td>Total number of accident durning last two weeks</td>
				<td colspan="3">{{@$last_two_week}}</td>
			</tr>
			<tr>
				<td>Total number of fatally injured person</td>
				<td colspan="3">{{@$no_of_fatalities}}</td>
			</tr>
			<tr>
				<td>Total number of grieviously injured person</td>
				<td colspan="3">{{@$no_of_persons_grievously_injured}}</td>
			</tr>
			<tr>
				<td>Total number with minor injuries</td>
				<td colspan="3">{{@$no_of_persons_with_minor_injuries}}</td>
			</tr>
		</tbody>
	</table>
</div>






	<div id="table-structure" class="aione-table scrollx">
		<table class="compact">
	        <thead>
				<tr>
					@foreach($keys as $key =>$val)
						<th>
							<span class="aione-tooltip truncate" > {{@$val}} </span>
						</th>
					@endforeach
				</tr>
	        </thead>
	        <tbody>
	        @if($id==1)
	        @php
	       	$collison 		= field_options('type_of_collision');
	       	$road_features 	= field_options('road_features');
	       	$type_of_vehicle_involved 	= field_options('type_of_vehicle_involved');
	        @endphp
	       			@foreach($data as $key =>$val)
	       			<tr>
	       			<td>{{$loop->iteration}}</td>
	       				<td>  {{$val['address']}}</td>
	       				<td> {{$val['accident_date']}}</td>
	       				<td> {{date('H:i:s',strtotime($val['accident_time']))}}</td>
	       				<td> @if(!empty($collison[$val['type_of_collision']]))
	       						{{$collison[$val['type_of_collision']]}}
	       					@endif
	       				</td>
	       				<td>@if(!empty($type_of_vehicle_involved[$val['type_of_vehicle_involved']]))
								{{$type_of_vehicle_involved[$val['type_of_vehicle_involved']]}}
	       					@endif
	       				</td>
	       				<td>@if(!empty($road_features[$val['road_features']]))
								{{$road_features[$val['road_features']]}}
	       					@endif
						</td>
	       				<td> {{ceil($val['no_of_fatalities'])}}</td>
	       				<td> {{ceil($val['no_of_persons_grievously_injured'])}}</td>
	       				<td> {{ceil($val['no_of_persons_with_minor_injuries'])}}</td>
	       			</tr> 
	       			@endforeach 
	        @elseif($id==2)
	         {{-- {{dump($data)}} --}}
		        @php
		         	$type_of_road 	= field_options('sub_type_of_road');
		         	$accident_type 	= field_options('accident_type',76);
		         	$vehicle_type 	= field_options('vehicle_type');
		         	$type_of_injury 	= field_options('type_of_injury');
		         	// dump($type_of_injury);
		         	
 				@endphp
		        @foreach($data as $keys => $vals )
		        @php
		        if(!empty($vals['type_of_injury'])){
		        	if(!empty($type_of_injury[$vals['type_of_injury']])){
		        		unset($injury);
		        			 if($type_of_injury[$vals['type_of_injury']]=="Fatal"){
		        			 	$injury['fatal'][] =$type_of_injury[$vals['type_of_injury']];
		        			 }
		        			 if($type_of_injury[$vals['type_of_injury']]=="Severe/Serious"){
		        			 	$injury['grevious'][] =$type_of_injury[$vals['type_of_injury']];
		        			 }
		        			 if($type_of_injury[$vals['type_of_injury']]=="Slight Injury"){
		        			 	$injury['minor'][] =$type_of_injury[$vals['type_of_injury']];
		        			 }
		        	}
		        }
		        @endphp
					<tr>
						<td>{{$loop->iteration}}</td>
						<td>{{@$vals['address']}}</td>
						<td>{{@$vals['accident_date']}}</td>
	       				<td> {{date('H:i:s',strtotime($vals['accident_time']))}}</td>
	       				<td> @if(!empty($accident_type[$vals['accident_type']]))
	       						{{$accident_type[$vals['accident_type']]}}
	       					@endif
	       				</td>
	       				<td>@php
	       					if(!empty($vals['vehicle_related_details'])){
			       				$vehicle_involve = collect(json_decode($vals['vehicle_related_details'],true));
			       				$vehicle_value = $vehicle_involve->pluck('SID2_GID9_QID89')->all();
			       				$result = array_intersect_key( $vehicle_type , array_flip( $vehicle_value ) );
			       				echo implode(',<br> ', $result);
			       			}
	       				 @endphp
	       				 </td>
	       				 <td>@php
	       				 if(!empty($vals['sub_type_of_road'])){
	       				 		$road_feature = array_filter(json_decode($vals['sub_type_of_road'],true));
	       				 		$intersect = array_intersect_key( $type_of_road , $road_feature );
	       				 		echo implode(',<br>', $intersect);
	       				 	}
							@endphp
						</td>
						<td>
						@if(!empty($injury['fatal']))
							{{count(@$injury['fatal'])}}
							@else
							0
						@endif
						</td>
						<td>
						@if(!empty($injury['grevious']))
							{{count(@$injury['grevious'])}}
							@else
							0
						@endif
						</td>
						<td>
						@if(!empty($injury['minor']))
							{{count(@$injury['minor'])}}
							@else
							0
						@endif
						</td>
					</tr>
				@endforeach
			@elseif($id==5)
			
			@php
				 $accident_type 	= field_options('accident_type',164);
				 $vehicle_type 	= field_options('vehicle_type',177);
				 $feature_of_road 	= field_options('feature_of_road',171);
				 $type_of_injury 	= field_options('type_of_injury',201);
			@endphp
			@foreach($data  as $key =>$val)
				@php
					if(!empty($val['type_of_injury'])){
		        	if(!empty($type_of_injury[$val['type_of_injury']])){
		        		unset($injury);
		        			 if($type_of_injury[$val['type_of_injury']]=="Fatal"){
		        			 	$injury['fatal'][] =$type_of_injury[$val['type_of_injury']];
		        			 }
		        			 if($type_of_injury[$val['type_of_injury']]=="Severe/Serious"){
		        			 	$injury['grevious'][] =$type_of_injury[$val['type_of_injury']];
		        			 }
		        			 if($type_of_injury[$val['type_of_injury']]=="Slight Injury"){
		        			 	$injury['minor'][] =$type_of_injury[$val['type_of_injury']];
		        			 }
		        	}
		        }
				@endphp
			<tr>
				<td>{{$loop->iteration}}</td>
						<td>{{@$val['address']}}</td>
						<td>{{@$val['accident_date']}}</td>
	       				<td> {{date('H:i:s',strtotime($val['accident_time']))}}</td>
	       				<td> @if(!empty($accident_type[$val['accident_type']]))
	       						{{$accident_type[$val['accident_type']]}}
	       					@endif
	       				</td>
	       				<td>@php
	       						if(!empty($val['vehicle_related_details'])){
		       						$vehicle_involve = collect(json_decode($val['vehicle_related_details'],true));
		       						$vehicle_ids = $vehicle_involve->pluck('SID5_GID23_QID177')->toArray();
		       						$intersect_key = array_intersect_key($vehicle_type, array_flip($vehicle_ids));
		       						echo	implode(',<br> ',$intersect_key);
		       					}
	       					@endphp
	       				</td>
	       				<td>@php 
	       						if(!empty($val['feature_of_road'])){
	       							$roads = array_filter(json_decode($val['feature_of_road'],true));
	       							$features = array_intersect_key($feature_of_road, $roads);
	       							echo implode(', <br>', $features);
	       						}
	       				 @endphp
	       				</td>
	       				<td>
						@if(!empty($injury['fatal']))
							{{count(@$injury['fatal'])}}
							@else
							0
						@endif
						</td>
						<td>
						@if(!empty($injury['grevious']))
							{{count(@$injury['grevious'])}}
							@else
							0
						@endif
						</td>
						<td>
						@if(!empty($injury['minor']))
							{{count(@$injury['minor'])}}
							@else
							0
						@endif
						</td>
			</tr>
			@endforeach
			@endif
	        </tbody>
	    </table>
	</div>            
	</div>

	@else

	{{-- @if(!empty($error))
		@foreach($error as $erorKey => $errorVal)
			<h3>{{$errorVal}}</h3>
		@endforeach
	@else --}}
		<div class="aione-message warning">
			No Data Available
		</div>
	{{-- @endif --}}
@endif
<script>
$('#more_condition').on('click',function(event){
	event.preventDefault();
	childs = $("#child").html();
	$("#append").append(childs);
});

function remove_parent(event){
	$(event).parent('div').remove();
}

$(".close").hide();
</script>

@include('common.page_content_primary_end')
@include('common.page_content_secondry_start')
@include('common.page_content_secondry_end')
@include('common.pagecontentend')
@endsection