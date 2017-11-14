<?php

namespace App\Http\Controllers\Organization\survey;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Support\Facades\Schema;
use Carbon\carbon;
use Auth;
use App\Model\Organization\FormBuilder;
use Excel;

class StaticSurveyResultController extends Controller
{
        public function survey_static_result(Request $request ,$id){
      //survey id -> 1 , 2 , 5
          $table_name = "235_survey_results_$id";
         if(!Schema::hasTable($table_name)){
            $error['table_not_exist'] = "No Data Available";
            return view('organization.survey.survey_static_result', compact('error') );
        }else{
              $fatal = $grevious = $minor=null;
              $dt = carbon::now();
              $to = $dt->toDateString();
              $from = $dt->subWeek(2)->toDateString();
              if($request->isMethod('post') && !empty($request['from']) &&  !empty($request['to'])){
                if($request['from'] >  $request['to']){
                     $error['from_greater_to'] = 'from-date must be greater than to-date';
                    return view('organization.survey.survey_static_result', compact('error','id') );
                }
              }
              if($id==1){
                try {
                   if($request->isMethod('post')){
                          $where = [];
                        if(!empty($request['from'])){
                         array_push($where,['accident_date', '>=', date('Y-m-d', strtotime($request['from']))]);
                        }
                        if(!empty($request['to'])){
                         array_push($where,['accident_date', '<=', date('Y-m-d', strtotime($request['to']))]);
                        }
                      }
                  $data = DB::table($table_name)->select([ DB::raw("CONCAT(accident_site_state,' ',accident_site_district, ' ',accident_site_taluk, ' ',accident_site_village ) as address"), 'accident_date', 'accident_time', 'no_of_fatalities' ,'no_of_persons_grievously_injured','no_of_persons_with_minor_injuries','type_of_collision' ,'type_of_vehicle_involved','road_features','type_of_vehicle_involved']);
                    if(!empty($where)){
                       $data = $data->where($where)->get();
                    }else{
                      $data = $data->get();
                    }
                    if($request->has('export')){
                      dd($data);

                    }


                 } catch (\Exception $e) {
                     $error['table_column_not_exist'] = "column not match";
                     return view('organization.survey.survey_static_result', compact('error','id') );
                     // return $e->getMessage();
                  
                }
              }elseif($id==2){
                 try {
                        $u_id = Auth::guard('org')->user()->id;
                        if($request->isMethod('post')){
                          $where = [];
                        if(!empty($request['from'])){
                         array_push($where,['accident_date', '>=', date('Y-m-d', strtotime($request['from']))]);
                        }
                        if(!empty($request['to'])){
                         array_push($where,['accident_date', '<=', date('Y-m-d', strtotime($request['to']))]);
                        }

                      }
                        if(is_admin($u_id)){
                              $data = DB::table($table_name)->select([  DB::raw("CONCAT(accident_site_state,' ',accident_site_district, ' ',accident_site_taluk, ' ',accident_site_village ) as address") , 'accident_date', 'accident_time', 'accident_type', 'sub_type_of_road', 'vehicle_related_details', 'type_of_injury']);
                          if(!empty($where)){
                             $data = $data->where($where)->get();
                          }else{
                            $data = $data->get();
                          }
                        }elseif($area_code = get_user_meta($u_id, 'areacode')){
                            $data = DB::table($table_name)->select([  DB::raw("CONCAT(accident_site_state,' ',accident_site_district, ' ',accident_site_taluk, ' ',accident_site_village ) as address") , 'accident_date', 'accident_time', 'accident_type', 'sub_type_of_road', 'vehicle_related_details', 'type_of_injury'])->where('center_code',$area_code);
                            if(!empty($where)){
                               $data = $data->where($where)->get();
                            }else{
                              $data = $data->get();
                            }
                        }

                    } catch (\Exception $e) {
                     $error['table_column_not_exist'] = "column not match";
                     return view('organization.survey.survey_static_result', compact('error','id') );
                }
            }elseif($id==5){
               try { 
                      $u_id = Auth::guard('org')->user()->id;
                      if($request->isMethod('post')){
                          $where = [];
                        if(!empty($request['from'])){
                         array_push($where,['accident_date', '>=', date('Y-m-d', strtotime($request['from']))]);
                        }
                        if(!empty($request['to'])){
                         array_push($where,['accident_date', '<=', date('Y-m-d', strtotime($request['to']))]);
                        }

                      }
                  
                  if(is_admin($u_id)){
                       
                        $data = DB::table($table_name)->select([DB::raw("CONCAT(accident_site_state,' ', accident_site_district,' ', accident_site_taluk,' ', accident_site_village ) as address"), 'accident_date', 'accident_time', 'accident_type', 'feature_of_road' , 'type_of_injury' ,'vehicle_related_details']);
                        if(!empty($where)){
                          $data = $data->where($where)->get();
                        }else{
                          $data = $data->get();
                        }
                    }elseif($area_code = get_user_meta($u_id, 'areacode')){
                      $data = DB::table($table_name)->select([DB::raw("CONCAT(accident_site_state,' ', accident_site_district,' ', accident_site_taluk,' ', accident_site_village ) as address"), 'accident_date', 'accident_time', 'accident_type', 'feature_of_road' , 'type_of_injury' ,'vehicle_related_details'])->where('center_code',$area_code);

                      if(!empty($where)){
                          
                          $data = $data->where($where)->get();
                         
                        }else{
                          
                          $data = $data->get();
                        }
                    }
                 } catch (\Exception $e) {
                     $error['table_column_not_exist'] = "column not match";
                     return view('organization.survey.survey_static_result', compact('error','id') );
                }
            }
            $last_two_week = DB::table($table_name)->where('accident_date', '<=', $to) ->where('accident_date', '>=', $from)->count();
            $data = json_decode(json_encode($data->all()),true);
              if($request->isMethod('post') && $request['export'] ){
               $file_name ="ocrm_235_survey_results_".$id."_".date('Y-m-d-h-i-s');
                         Excel::create($file_name, function($excel) use($data){
                          $excel->sheet('mySheet', function($sheet) use($data){
                            $sheet->fromArray($data);
                          });
                        })->export('csv');
              }
        $sub_type_of_road = FormBuilder::with(['fieldMeta'=>function($query){
          $query->where('key','field_options');
        }])->where('field_slug','sub_type_of_road')->first()->toArray();
        $option_data = collect(json_decode($sub_type_of_road['field_meta'][0]['value'],true))->pluck('value','key')->toArray();

        return view('organization.survey.survey_static_result',compact('data','option_data' ,'last_two_week','id','fatal','grevious','minor'));
      }
    }
}
