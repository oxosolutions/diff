<?php

namespace App\Http\Controllers\Organization\survey;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Organization\forms;
use App\Model\Organization\FormsMeta;
use App\Model\Organization\FormBuilder;
use DB;
use Illuminate\Support\Facades\Schema;
use Excel;
use App\Model\Admin\FormBuilder as GFB;
use Session;
use Carbon\carbon;
use Auth;
use App\Model\Organization\section;
/**
 * @author [Paljinder Singh ] SurveyStatsController all work done by Paljnder Singh
 */
class SurveyStatsController extends Controller
{
    protected  function get_survey_table_name($form_id){
        $table = $replace_ocrm = null;
        $metaTable =  FormsMeta::where(['form_id'=>$form_id,'key'=>'survey_data_table']);
           if($metaTable->exists()){
              $table =   $metaTable->first()->value;
              $replace_ocrm = str_replace('ocrm_', '', $table);
              if(Schema::hasTable($replace_ocrm)){
                  return ['table'=>$table , 'replace_ocrm'=> $replace_ocrm];
                }
            }
        return null;
    }
    public function stats($id){
     $data['status']=  $data['count']['completed'] = $data['count']['sections'] = $data['count']['fields'] = $data['count']['incomplete'] = 0;
      $field = [];
      $data['status'] = null;
      $survey_data = forms::with(['section.fields.fieldMeta','formsMeta'])->where('id',$id);
      if($survey_data->exists()){
        $survey_data = $survey_data->first();
        if(count($survey_data['section'])>0){
          foreach ($survey_data['section'] as $key => $value){
            if(!empty($value['fields'])){
              $field[] = count($value['fields']);
            }
          }
          if(!empty($field)){
             $data['count']['fields']   = array_sum($field);
          }else{
              $data['status'] = 'error';
              $data['errors'][] =  __('survey.survey_question_miss');
          }
          $data['count']['sections'] = count($survey_data['section']);
        }else{
          $data['status'] = 'error';
          $data['errors'][] =  __('survey.survey_section_miss');
        }
        $response_table = $this->get_survey_table_name($id);
        if(empty($response_table)){
            $data['errors'][] = __('survey.survey_results_table_missing');
          }else{

              $table_name = $response_table['table'];
              $data['date_by'] = DB::select("select date(created_at) as date , count(id) as total, sum(case when survey_status = 'completed' then 1 else 0 end) as completed, sum(case when survey_status = 'incompleted' then 1 else 0 end) as uncompleted, count(*) as totals from ".$table_name." group by date(created_at)");

            $data['user_by'] = DB::select("select survey_submitted_by as user_id, count(id) as total, sum(case when survey_status ='completed' then 1 else 0 end) as completed , sum(case when survey_status ='incompleted' then 1 else 0 end ) as uncompleted  FROM `".$table_name."` group by survey_submitted_by ");
            $data['user_submit_from'] = DB::select("select survey_submitted_by as user_id , count(id) as total, sum( case when survey_submitted_from = 'app' then 1 else 0 end) as application , sum(case when survey_submitted_from='web' then 1 else 0 end) as web FROM `".$table_name."` group by survey_submitted_by");

            $data['date_submit_from'] = DB::select("select date(created_at) as date , count(id) as total, sum( case when survey_submitted_from = 'app' then 1 else 0 end) as application , sum(case when survey_submitted_from='web' then 1 else 0 end) as web FROM `".$table_name."` group by date(created_at)");
               $table_name = $response_table['replace_ocrm'];
              $data['count']['completed'] = DB::table($table_name)->where('survey_status','completed')->count();
              $data['count']['incomplete'] = DB::table($table_name)->where('survey_status','incompleted')->count();
          }
       }else{
        $data['status'] = 'error';
        $data['errors'][] =  __('survey.survey_not_exit');
      }
      if($data['status']!='error'){
         $data['status'] = 'success';
      }
      return view('organization.survey.survey_stats',compact('data'));

    }

    protected function field_option_check($question_fields){
        $collection = collect($question_fields);
        $collections = $collection->whereIn('field_type',['radio','select','checkbox']);
        $fieldOption  = $collections->mapWithKeys(function($item){
          $message =[];
          $option = collect($item['field_meta'])->where('key','field_options')->first();
            // dump($item['field_slug'] , $option);
          if(!empty($option['value'])){
            $opt_val =  json_decode($option['value'],true);
            //dump($opt_val);
            foreach ($opt_val as $key => $value) {
                if(empty($value['key']) || empty($value['value'])){
                    $message['waring'] = 'May option key or val empty';
                }
                 if(empty($value['go_to_question'])){
                    $message['error'] = "Go to next question value does't exist";
                 }elseif(!isset($value['go_to_question'])){
                    $message['error'] = "Go to next question Not added";
                 }
            }
           }else{
            $message ='not exist Options Values';
           }
          if(!empty($message)){
            return [ $item['field_slug']=> ['field_type'=>$item['field_type'],'question'=>$item['field_title'], 'error'=>$message]];
           }
          return [$item['field_slug']=>null];
        });
      return $fieldOption;
    }

    protected function count_section_question($survey_data){
      if(count($survey_data['section'])>0){
      foreach ($survey_data['section'] as $key => $value){
            if(!empty($value['fields'])){
              $field[] = count($value['fields']);
            }
          }
          if(!empty($field)){
             $data['count']['fields']   = array_sum($field);
          }else{
              $data['status'] = 'error';
              $data['errors'][] =  __('survey.survey_question_miss');
          }
          $data['count']['sections'] = count($survey_data['section']);
        }else{
          $data['status'] = 'error';
          $data['errors'][] =  __('survey.survey_section_miss');
        }
        return $data;

    }
    public function survey_structure($id){
        $id =intval($id);
        $survey_data = forms::with(['formsMeta','section'=>function($query){
                                $query->orderBy('order','asc');
                              },
                              'section.fields'=>function($query_field){
                                   $query_field->orderBy('order','asc');
                              },
                               'section.fields.fieldMeta'])->where('id',$id);
        if($survey_data->exists()){
          $survey_data = $survey_data->first()->toArray();
          $data = $this-> count_section_question($survey_data);
          $count_form_slug = forms::where('form_slug',$survey_data['form_slug'])->count();
          $setting_questions = GFB::orderBy('order','asc')->whereIn('form_id',[93,76])->get()->keyBy('field_slug')->toArray(); //pluck('field_title', 'field_slug');
          return view('organization.survey.survey_structure',compact('id','data','survey_data','setting_questions','count_form_slug') );
       }else{
              $not_valid_id = "This survey id ($id) is not valid.";
        return view('organization.survey.survey_structure', compact('not_valid_id'));
       }
    }
    public function survey_result(Request $request, $id)
    {  $condition_data =null;
       $metaTable =  FormsMeta::where(['form_id'=>$id,'key'=>'survey_data_table']);
       if($metaTable->exists()){
          $table =   $metaTable->first()->value;
          $table_name = str_replace('ocrm_', '', $table);

          if(!Schema::hasTable($table_name)){
            return view('organization.survey.survey_result');
          }

          $table_column = Schema::getColumnListing($table_name);
          $columns = array_combine($table_column,$table_column);
          if($request->isMethod('post')){

            // dd($request->all());
            if(isset($request['condition_field']) && !empty(array_filter($request['condition_field'])) && !empty(array_filter($request['condition_field_value'])) ){
                    $filter_field['condition_field']        = $request['condition_field'];
                    $filter_field['condition_field_value']  = $request['condition_field_value'];
                    $filter_field['operator'] = $request['operator'];
            }
            $this->validations($request);
              $data = $this->filter_on_suvey_result($request, $table_name, $columns);
             if(!empty($request['export'])){ 
                $condition = json_decode($data['filter_data']->get(), true);
              }
              if(!empty($condition['condition_data'])){
                 $condition_data = $condition['condition_data'];
                 unset($condition['condition_data']);
              }
             if(!empty($request['export'])){
                 $survey_slug  = forms::select('form_slug')->where('id',$id)->first()->form_slug;
                  $file_name = $survey_slug.'_'.generate_filename();
                 Excel::create($file_name, function($excel) use($condition){
                  $excel->sheet('mySheet', function($sheet) use($condition){
                    $sheet->fromArray($condition);
                  });
                })->export('csv');
                 
                }
                $data = $data['filter_data']->paginate(100);
          }else{
              $data =  DB::table($table_name)->paginate(100);
          }
        
          $formQuestion = FormBuilder::select('field_slug','field_title')->where('form_id',$id)->get()->mapWithKeys(function($items){
            return [$items['field_slug'] =>$items['field_title']];
          })->toArray(); 
        }else{
             $formQuestion = $columns = $data = null;
        }
     return view('organization.survey.survey_result',compact('id','columns', 'data','formQuestion','condition_data','table','filter_field'));
    }
    protected function validations($req){
      $customMessages = [
    'fields.required' => 'Select atleast one field to view data.',
    ];
      return $this->validate($req,['fields'=>'required'], $customMessages );
    }
    protected function filter_on_suvey_result($request , $table_name){
          $condition =null;
          $filled_codition =[]; 
          if(isset($request['condition_field'])  && !empty(array_filter($request['condition_field'])) && !empty(array_filter($request['condition_field_value'])) ){
                $where = [];
                foreach ($request['condition_field'] as $key => $value) {
                  if(isset($request['operator'][$key]) && isset($request['condition_field_value'][$key]) ){
                    $condition_field_value = $request['condition_field_value'][$key];
                    $operator = $request['operator'][$key];
                      if($operator=='like'){
                        $final = [$value , $operator,  $condition_field_value.'%'];
                      }else{
                           $final = [$value , $operator,  $condition_field_value];
                      }
                    array_push($filled_codition, ['condition_field'=> $value , 'operator'=>$operator, 'condition_field_value'=>$condition_field_value]);
                    array_push($where, $final);
                  }
              }
          }
         if(!empty($where)){
            $data = DB::table($table_name)->select($request['fields'])->where($where);//->get();
         }else{
            if(!empty($request['fields'])){
                $select_field = array_filter($request['fields']);
                $data = DB::table($table_name)->select($select_field);//->get();
            }else{
               $data = DB::table($table_name);//->get();
            }
         }
            return ['filter_data'=>$data, 'filter_fields'=> $filled_codition];
    }

    public function reports(Request $request, $id){
      // Temporarily increase Execution time
      ini_set('max_execution_time', 300); //300 seconds = 5 minutes
      // Temporarily increase memory limit
      ini_set('memory_limit','512M');
        $filter_fields = null;
        $table = Session::get('organization_id')."_survey_results_".$id;
        if(!Schema::hasTable($table)){
           $error = "survey_results_table_missing";
          return view('organization.survey.survey_reports',compact('error'));
        }
        $table_column = Schema::getColumnListing($table);
        $column_fields = $columns = array_combine($table_column,$table_column);
        $options_val = $data=[];

        $field = FormBuilder::with(['section.sectionMeta','fieldMeta'=>function($query){
            $query->where('key','question_id');
         }])->where('form_id',$id)->get()->toArray();

        $slug_question_id = collect($field)->mapWithKeys(function($item){
            $section_type =null;
              if(isset($item['section']['section_meta']) && count($item['section']['section_meta']) >0){
                  if($item['section']['section_meta'][0]['key']=='section_type'){
                    $section_type = $item['section']['section_meta'][0]['value'];
                    if(isset($item['section']['section_meta'][0]['key']) && $item['section']['section_meta'][0]['key']=='section_type'){    
                        $section_type = $item['section']['section_meta'][0]['value'];                  
                         }   
                  }
              }
            return [$item['field_slug']=>[$item['field_type'] , $item['field_meta'][0]['value'], $item['id'], $item], $item['section']['section_slug'] =>$section_type];
        });

        $index = 0;
        $sec_repeater =  section::with(['sectionMeta'=>function($query){
        },'fields'])->where('form_id',$id)->get();

        foreach ($sec_repeater as $key => $value) {// dump($key , $value['sectionMeta']);
          if( isset($value['sectionMeta'])  &&  count($value['sectionMeta']) >0  &&  $value['sectionMeta'][0]['value']=='repeater'){{
                $repeater_data[$index]['section_slug'] = $value['section_slug'];
                foreach ($value['fields'] as $field_key => $field_value) {
                   $repeater_data[$index]['field_slug'][] =    $field_value['field_slug'];
                }
            $index++;
           }
        }
        if(!empty($repeater_data)){
        $repeater_data = collect($repeater_data)->keyBy('section_slug')->toArray();
          
        }
        $columns  = collect($columns)->map(function($items, $key)use($slug_question_id) {
          if(!empty($slug_question_id[$key])){
              $items = $items.' ('.$slug_question_id[$key][1].')';
          }
          return $items;
       });

        if($request->isMethod('post')){
          // dump($request->all());
          if(empty($request['fields'])){
            $request['fields'] = array_keys($column_fields);
          }
            $req = $request;//->toArray();
            // if(isset($request['condition_field'] )){
            //   $condition_field = array_filter($request['condition_field']);
            // }else{
            //   $condition_field =null; orm
            // }
                 $filter = $this->filter_on_suvey_result($request, $table);
                 $filter_fields = $filter['filter_fields'];
                 if(!empty($filter['filter_data'])){
                   if(isset($request['export'])){
                      $query = $filter['filter_data']->simplePaginate(20000);
                    }else{
                      $query = $filter['filter_data']->simplePaginate(100);
                    }
                    $data = json_decode(json_encode($query->items()),true);
                  }
                if(!empty($req['fields'])){
                   foreach($req['fields'] as $key => $val){
                    if(!empty($slug_question_id[$val]))
                    {
                      if($slug_question_id[$val][0]=='checkbox' || $slug_question_id[$val][0] =='multi_select'){
                        $options_val[$val] =  field_options($val, $slug_question_id[$val][2]);
                      }
                    }
                  }
                }else{
                  foreach ($slug_question_id as $key => $value) {
                    if(in_array($value[0], ['checkbox' , 'multi_select'])){
                       $options_val[$key] =  field_options($key, $value[2]);
                    }
                  }
                }
// option value
              if(empty($data['error'])){
                if(!empty($repeater_data) && !empty($options_val)){
                  $data = $this->set_repeater_options_data($data, $repeater_data , $options_val );
                }
              }
                  if(isset($request['export'])){
                    if(empty($data['error'])){
                      $survey_slug  = forms::select('form_slug')->where('id',$id)->first()->form_slug;
                      $file_name = $survey_slug.'_'.generate_filename(); //$this->generate_csv_file_name($id);
                       Excel::create($file_name, function($excel) use($data){
                        $excel->sheet('mySheet', function($sheet) use($data){
                          $sheet->fromArray($data);
                        });
                      })->export('csv');
                    }
                }
         }else{
          $query = DB::table($table)->simplePaginate(100);
          $data = json_decode(json_encode($query->items()),true);
            foreach ($slug_question_id as $key => $value) {
                if(in_array($value[0], ['checkbox' , 'multi_select'])){
                   $options_val[$key] =  field_options($key, $value[2]);
                }
              }
             if(!empty($repeater_data) && !empty($options_val)){
               $data = $this->set_repeater_options_data($data, $repeater_data , $options_val);
             }
              
         }
        $links = $query->links();
        $firstItem = $query->firstItem();
        $lastItem = $query->lastItem();
        // $total = $query->total(); 
        if(!empty($repeater_data)){
          $repeater_keys  = array_keys($repeater_data);
        }else{
          $repeater_keys=[];
        }
          if(!empty($options_val)){
            $option_keys  = array_keys($options_val);
          }else{
            $option_keys =[];
          }
        $repeater_options_value = array_merge($repeater_keys ,  $option_keys);
        if(!empty($repeater_options_value)){
          $combine = array_combine($repeater_options_value, $repeater_options_value);
          $condition_fields = $columns->diffKeys($combine);
        }else{
          $condition_fields = $columns; 
        }
      return view('organization.survey.survey_reports',compact('data','id','columns','table' ,'repeater_options_value' , 'links' ,'firstItem', 'lastItem' ,'condition_fields', 'filter_fields'));
    }

    protected function set_repeater_options_data($data, $repeater_data=Null , $options_val=Null){
     
      
      foreach ($data as $key => $value) {
              foreach ($value as $nextKey => $nextValue) {
                if(isset($repeater_data[$nextKey])){
                    $rep = json_decode($value[$nextKey],true);
                    if(!is_array($rep)){    
                       $rep =[];   
                      }
                      foreach ($repeater_data as $rkey => $rvalue) {
                       foreach ($rvalue['field_slug'] as $kkey => $vvalue) {
                        unset($data[$key][$nextKey]);
                        $data[$key][$nextKey.'_'.$vvalue]  = implode(',', array_column($rep, $vvalue));
                       }
                      }
                }elseif(isset($options_val[$nextKey])){
                    unset($data[$key][$nextKey]);
                    $option_data = json_decode($nextValue, true);
                    if(!is_array($option_data)){
                        $option_data = [];
                    }
                  foreach($options_val[$nextKey] as $optionKey =>$optionVal){
                    if(in_array($optionKey, $option_data)){
                      $data[$key][$nextKey.'_'.$optionKey] ='yes';
                    }else{
                       $data[$key][$nextKey.'_'.$optionKey] ='no';
                    }
                  }
                }else{
                  $field_val = $data[$key][$nextKey];
                  unset($data[$key][$nextKey]);
                  $data[$key][$nextKey] = $field_val;
                }
              }
            }
      return $data;
    }
   
  

    public function survey_static_community_based(Request $request){

          $table_name = "235_survey_results_1";
//           accident_date
// accident_time 
// no_of_fatalities 
// no_of_persons_grievously_injured 
// no_of_persons_with_minor_injuries
// type_of_collision 
// type_of_vehicle_involved 
// road_features 


          $data = DB::table($table_name)->select([ DB::raw("CONCAT(accident_site_state,' ',accident_site_district, ' ',accident_site_taluk, ' ',accident_site_village ) as address"),   'accident_date', 'accident_time', 'no_of_fatalities' ,'no_of_persons_grievously_injured','no_of_persons_with_minor_injuries','type_of_collision' ,'type_of_vehicle_involved','road_features'])->get();
          // dd($data);
          $data = json_decode(json_encode($data->all()),true);
         
          if($request->isMethod('post') && $request['export'] ){
           $file_name ='ocrm_235_survey_results_2_'.date('Y-m-d-h-i-s');
                     Excel::create($file_name, function($excel) use($data){
                      $excel->sheet('mySheet', function($sheet) use($data){
                        $sheet->fromArray($data);
                      });
                    })->export('csv');
          }
    
    $option_data=[];
        return view('organization.survey.survey_static_result',compact('data','option_data'));
    }
 public function survey_static_surveillance(Request $request){

        echo   $table_name = "235_survey_results_5";

//           accident_date
// accident_time 
// no_of_fatalities 
// no_of_persons_grievously_injured 
// no_of_persons_with_minor_injuries
// type_of_collision 
// type_of_vehicle_involved 
// road_features 
          


          $data = DB::table($table_name)->select([DB::raw("CONCAT(accident_site_state,' ', accident_site_district,' ', accident_site_taluk,' ', accident_site_village ) as address"), 'accident_date', 'accident_time', 'accident_type', 'feature_of_road', 'vehicle_type', 'type_of_injury' ])->get();
          // dd($data);
          $data = json_decode(json_encode($data->all()),true);
         
          if($request->isMethod('post') && $request['export'] ){
          return $file_name ='ocrm_235_survey_results_5_'.date('Y-m-d-h-i-s');
                     Excel::create($file_name, function($excel) use($data){
                      $excel->sheet('mySheet', function($sheet) use($data){
                        $sheet->fromArray($data);
                      });
                    })->download('pdf');
          }
    
    $option_data=[];
        return view('organization.survey.survey_static_result',compact('data','option_data'));
    }




    
}
